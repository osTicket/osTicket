import { Agent } from "@cursor/sdk";
import type {
  Run,
  RunResult,
  SDKAgent,
  SDKMessage,
  SDKToolUseMessage,
  ToolUseBlock,
} from "@cursor/sdk";

export type AgentRunOptions = {
  model?: string;
  name?: string;
};

export function createCloudAgent(options: AgentRunOptions = {}) {
  const { model = "composer-2.5", name } = options;
  return Agent.create({
    apiKey: process.env.CURSOR_API_KEY!,
    model: { id: model },
    ...(name ? { name } : {}),
    cloud: {
      repos: [{ url: process.env.GITHUB_REPO_URL!, startingRef: process.env.GITHUB_DEMO_BRANCH! }],
    },
  });
}

export function createLocalAgent(options: AgentRunOptions = {}) {
  const { model = "composer-2.5", name } = options;
  return Agent.create({
    apiKey: process.env.CURSOR_API_KEY!,
    model: { id: model },
    ...(name ? { name } : {}),
    local: { cwd: process.cwd() },
  });
}

/** Create a local agent, run `fn`, then release executor leases via asyncDispose. */
export async function withLocalAgent<T>(
  fn: (agent: SDKAgent) => Promise<T>,
  options: AgentRunOptions = {}
): Promise<T> {
  const agent = await createLocalAgent(options);
  try {
    return await fn(agent);
  } finally {
    await agent[Symbol.asyncDispose]();
  }
}

/** Create a cloud agent, run `fn`, then release SDK resources via asyncDispose. */
export async function withCloudAgent<T>(
  fn: (agent: SDKAgent) => Promise<T>,
  options: AgentRunOptions = {}
): Promise<T> {
  const agent = await createCloudAgent(options);
  try {
    return await fn(agent);
  } finally {
    await agent[Symbol.asyncDispose]();
  }
}

function isTerminalStreamStatus(status: string): boolean {
  return status === "FINISHED"
    || status === "ERROR"
    || status === "CANCELLED"
    || status === "EXPIRED";
}

function looksLikeJsonOutput(text: string): boolean {
  const trimmed = text.trim();
  return trimmed.startsWith("{")
    || trimmed.startsWith("[")
    || trimmed.startsWith("```json");
}

function extractJsonPayload(raw: string): string {
  const stripped = raw.replace(/^```(?:json)?\s*/i, "").replace(/```\s*$/i, "").trim();
  try {
    JSON.parse(stripped);
    return stripped;
  } catch {
    const match = stripped.match(/\{[\s\S]*\}$/);
    return match ? match[0] : stripped;
  }
}

function readArg(args: unknown, ...keys: string[]): string | undefined {
  if (!args || typeof args !== "object") return undefined;
  for (const key of keys) {
    const value = (args as Record<string, unknown>)[key];
    if (typeof value === "string" && value.length > 0) return value;
  }
  return undefined;
}

function summarizeToolUseBlock(block: ToolUseBlock): string | undefined {
  return summarizeToolName(block.name, block.input);
}

function summarizeToolCall(event: SDKToolUseMessage): string | undefined {
  if (event.status !== "running") return undefined;
  return summarizeToolName(event.name, event.args);
}

function summarizeToolName(name: string, args: unknown): string | undefined {
  const path = readArg(args, "path", "target_file", "file_path");
  const pattern = readArg(args, "pattern");
  const command = readArg(args, "command");

  switch (name) {
    case "Read":
    case "read_file":
      return path ? `reading ${path}` : "reading file";
    case "Grep":
    case "grep":
      return pattern ? `searching for ${pattern}` : "searching codebase";
    case "Write":
    case "write":
      return path ? `writing ${path}` : "writing file";
    case "StrReplace":
    case "search_replace":
      return path ? `editing ${path}` : "editing file";
    case "Shell":
    case "run_terminal_cmd":
      return command ? `running: ${command.slice(0, 80)}` : "running command";
    default:
      return name;
  }
}

interface StreamState {
  inText: boolean;
  endedWithNewline: boolean;
}

const streamStates = new Map<string, StreamState>();

function getStreamState(label: string): StreamState {
  let state = streamStates.get(label);
  if (!state) {
    state = { inText: false, endedWithNewline: true };
    streamStates.set(label, state);
  }
  return state;
}

function endTextLine(label: string): void {
  const state = getStreamState(label);
  if (state.inText && !state.endedWithNewline) {
    process.stderr.write("\n");
  }
  state.inText = false;
  state.endedWithNewline = true;
}

function writePrefixedLine(label: string, text: string): void {
  endTextLine(label);
  process.stderr.write(`[${label}] ${text}\n`);
}

function writeAssistantText(label: string, text: string): void {
  const state = getStreamState(label);
  if (!state.inText) {
    process.stderr.write(`[${label}] `);
    state.inText = true;
  }
  process.stderr.write(text);
  state.endedWithNewline = text.endsWith("\n");
}

function flushStream(label: string): void {
  endTextLine(label);
  streamStates.delete(label);
}

function handleStreamEvent(event: SDKMessage, label: string): void {
  switch (event.type) {
    case "assistant": {
      for (const block of event.message.content) {
        if (block.type === "text") {
          const text = block.text;
          if (!text || looksLikeJsonOutput(text)) continue;
          writeAssistantText(label, text);
        } else if (block.type === "tool_use") {
          const detail = summarizeToolUseBlock(block);
          if (detail) writePrefixedLine(label, detail);
        }
      }
      break;
    }
    case "tool_call": {
      const detail = summarizeToolCall(event);
      if (detail) writePrefixedLine(label, detail);
      break;
    }
    case "status": {
      if (event.message) {
        writePrefixedLine(label, `${event.status}: ${event.message}`);
      }
      break;
    }
    case "task": {
      if (event.text) {
        writePrefixedLine(label, event.text);
      }
      break;
    }
  }
}

/**
 * Stream human-friendly progress to stderr, then return the terminal result.
 *
 * Starts run.wait() immediately and races it against stream consumption so a
 * stuck stream iterator cannot block returning once the run has finished.
 */
export async function streamRunWithProgress(run: Run, label: string): Promise<RunResult> {
  const waitPromise = run.wait();
  const iterator = run.stream()[Symbol.asyncIterator]();

  try {
    while (true) {
      const raced = await Promise.race([
        iterator.next().then((r) => ({ kind: "event" as const, r })),
        waitPromise.then((result) => ({ kind: "wait" as const, result })),
      ]);

      if (raced.kind === "wait") {
        await iterator.return?.().catch(() => {});
        return raced.result;
      }

      const { value, done } = raced.r;
      if (done) break;

      handleStreamEvent(value, label);

      if (value.type === "status" && isTerminalStreamStatus(value.status)) {
        break;
      }
    }
  } catch (err) {
    await iterator.return?.().catch(() => {});
    throw err;
  } finally {
    flushStream(label);
  }

  return waitPromise;
}

/** Stream a run to completion and return the terminal result. */
export async function streamAndWait(run: Run, label: string = "agent"): Promise<RunResult> {
  return streamRunWithProgress(run, label);
}

export function parseJsonResult<T>(raw: string | undefined, fallback: T): T {
  if (!raw) return fallback;
  const payload = extractJsonPayload(raw);
  try {
    return JSON.parse(payload) as T;
  } catch {
    return fallback;
  }
}
