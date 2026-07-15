import { execSync } from "child_process";
import { Agent } from "@cursor/sdk";
import type {
  Run,
  RunResult,
  SDKAgent,
  SDKMessage,
  SDKToolUseMessage,
  ToolUseBlock,
} from "@cursor/sdk";
import {
  formatAgentLabel,
  logRunEnd,
  logRunStart,
  styled,
} from "./terminal";

export type AgentRunOptions = {
  model?: string;
  name?: string;
};

export { logRunEnd, logRunStart, writeStageBanner } from "./terminal";

const LARGE_FENCE_LINE_THRESHOLD = 40;

const VAGUE_TOOL_SUMMARIES = new Set([
  "reading file",
  "searching codebase",
  "writing file",
  "editing file",
  "running command",
  "finding files",
]);

/** Current git branch for cloud startingRef (per-ticket strangler/* after pipeline start). */
export function getCurrentBranch(): string {
  try {
    return execSync("git rev-parse --abbrev-ref HEAD", {
      encoding: "utf-8",
      stdio: ["ignore", "pipe", "ignore"],
    }).trim();
  } catch {
    const fallback = process.env.GITHUB_DEMO_BRANCH;
    if (!fallback) {
      throw new Error(
        "Could not determine current git branch (git rev-parse failed) and GITHUB_DEMO_BRANCH is not set"
      );
    }
    return fallback;
  }
}

export function createCloudAgent(options: AgentRunOptions = {}) {
  const { model = "composer-2.5", name } = options;
  const startingRef = getCurrentBranch();
  return Agent.create({
    apiKey: process.env.CURSOR_API_KEY!,
    model: { id: model },
    ...(name ? { name } : {}),
    cloud: {
      repos: [{ url: process.env.GITHUB_REPO_URL!, startingRef }],
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

function shortenPath(rawPath: string): string {
  return rawPath
    .replace(/^\/workspace\//, "")
    .replace(/^\/Users\/[^/]+\/projects\/osticket-strangler-demo\//, "");
}

function humanizeToolName(name: string): string {
  return name.replace(/_/g, " ");
}

function isVagueToolSummary(summary: string): boolean {
  return VAGUE_TOOL_SUMMARIES.has(summary);
}

function summarizeToolName(name: string, args: unknown): string | undefined {
  const path = readArg(args, "path", "target_file", "file_path");
  const pattern = readArg(args, "pattern", "query", "glob_pattern");
  const command = readArg(args, "command");
  const glob = readArg(args, "glob_pattern", "glob");
  const shortPath = path ? shortenPath(path) : undefined;

  switch (name) {
    case "Read":
    case "read":
    case "read_file":
      return shortPath ? `reading ${shortPath}` : "reading file";
    case "Grep":
    case "grep":
    case "grep_search":
    case "codebase_search":
      return pattern ? `searching for ${pattern}` : "searching codebase";
    case "Write":
    case "write":
    case "edit_file":
      return shortPath ? `writing ${shortPath}` : "writing file";
    case "StrReplace":
    case "search_replace":
      return shortPath ? `editing ${shortPath}` : "editing file";
    case "Glob":
    case "glob":
    case "glob_file_search":
    case "file_search":
      return glob || pattern ? `finding files: ${glob ?? pattern}` : "finding files";
    case "Shell":
    case "shell":
    case "run_terminal_cmd":
      return command ? `running: ${command.slice(0, 80)}` : "running command";
    default:
      return humanizeToolName(name);
  }
}

function summarizeToolUseBlock(block: ToolUseBlock): string | undefined {
  return summarizeToolName(block.name, block.input);
}

function summarizeToolCall(event: SDKToolUseMessage): string | undefined {
  if (event.status !== "running") return undefined;
  return summarizeToolName(event.name, event.args);
}

type SuppressMode = "none" | "json" | "json_fence" | "code_fence";

interface StreamState {
  inText: boolean;
  endedWithNewline: boolean;
  textBuffer: string;
  suppressMode: SuppressMode;
  fenceContent: string;
  fenceLang: string;
  lastToolLine?: string;
  pendingVagueTool?: string;
}

const streamStates = new Map<string, StreamState>();

function getStreamState(label: string): StreamState {
  let state = streamStates.get(label);
  if (!state) {
    state = {
      inText: false,
      endedWithNewline: true,
      textBuffer: "",
      suppressMode: "none",
      fenceContent: "",
      fenceLang: "",
    };
    streamStates.set(label, state);
  }
  return state;
}

function tryConsumeJson(text: string): { consumed: number } | null {
  const start = text.search(/[{[]/);
  if (start === -1) return null;

  const opener = text[start];
  const closer = opener === "{" ? "}" : "]";
  let depth = 0;
  let inString = false;
  let escaped = false;

  for (let i = start; i < text.length; i++) {
    const ch = text[i];
    if (inString) {
      if (escaped) {
        escaped = false;
      } else if (ch === "\\") {
        escaped = true;
      } else if (ch === '"') {
        inString = false;
      }
      continue;
    }

    if (ch === '"') {
      inString = true;
      continue;
    }
    if (ch === opener) depth++;
    if (ch === closer) {
      depth--;
      if (depth === 0) return { consumed: i + 1 };
    }
  }

  return null;
}

function finalizeCodeFence(state: StreamState): string {
  const content = state.fenceContent;
  const lang = state.fenceLang;
  const lineCount = content.split("\n").length;
  state.fenceContent = "";
  state.fenceLang = "";
  state.suppressMode = "none";

  if (lineCount > LARGE_FENCE_LINE_THRESHOLD) {
    return `\n${styled("tool", `showing code snippet (${lineCount} lines)`)}\n`;
  }

  const langTag = lang ? lang : "";
  return `\`\`\`${langTag}\n${content}\`\`\``;
}

function drainTextBuffer(state: StreamState): string {
  let output = "";

  while (state.textBuffer.length > 0) {
    if (state.suppressMode === "json_fence" || state.suppressMode === "code_fence") {
      const closeIdx = state.textBuffer.indexOf("```");
      if (closeIdx === -1) {
        if (state.suppressMode === "code_fence") {
          state.fenceContent += state.textBuffer;
        }
        state.textBuffer = "";
        break;
      }

      if (state.suppressMode === "code_fence") {
        state.fenceContent += state.textBuffer.slice(0, closeIdx);
        state.textBuffer = state.textBuffer.slice(closeIdx + 3);
        output += finalizeCodeFence(state);
        continue;
      }

      state.textBuffer = state.textBuffer.slice(closeIdx + 3);
      state.suppressMode = "none";
      continue;
    }

    if (state.suppressMode === "json") {
      const trimmed = state.textBuffer.trimStart();
      const lead = state.textBuffer.length - trimmed.length;
      if (lead > 0) {
        output += state.textBuffer.slice(0, lead);
        state.textBuffer = trimmed;
      }

      const consumed = tryConsumeJson(state.textBuffer);
      if (!consumed) break;

      state.textBuffer = state.textBuffer.slice(consumed.consumed);
      state.suppressMode = "none";
      continue;
    }

    const fenceIdx = state.textBuffer.indexOf("```");
    const jsonMatch = state.textBuffer.match(/(?:^|\n)\s*([{[])/);

    let nextSpecial = state.textBuffer.length;
    if (fenceIdx !== -1) nextSpecial = Math.min(nextSpecial, fenceIdx);
    if (jsonMatch?.index !== undefined && jsonMatch[1]) {
      const bracketOffset = jsonMatch[0].lastIndexOf(jsonMatch[1]);
      nextSpecial = Math.min(nextSpecial, jsonMatch.index + bracketOffset);
    }

    if (nextSpecial > 0) {
      output += state.textBuffer.slice(0, nextSpecial);
      state.textBuffer = state.textBuffer.slice(nextSpecial);
    }

    if (state.textBuffer.startsWith("```")) {
      const fenceMatch = state.textBuffer.match(/^```(\w*)\n?/);
      if (!fenceMatch) break;

      const lang = (fenceMatch[1] ?? "").toLowerCase();
      state.textBuffer = state.textBuffer.slice(fenceMatch[0].length);

      if (lang === "json") {
        state.suppressMode = "json_fence";
      } else {
        state.suppressMode = "code_fence";
        state.fenceContent = "";
        state.fenceLang = lang;
      }
      continue;
    }

    const trimmed = state.textBuffer.trimStart();
    if (trimmed.startsWith("{") || trimmed.startsWith("[")) {
      const lead = state.textBuffer.length - trimmed.length;
      if (lead > 0) {
        output += state.textBuffer.slice(0, lead);
        state.textBuffer = trimmed;
      }
      state.suppressMode = "json";
      continue;
    }

    if (nextSpecial === state.textBuffer.length && state.textBuffer.length > 0) {
      output += state.textBuffer;
      state.textBuffer = "";
    }

    break;
  }

  return output;
}

function endTextLine(label: string): void {
  const state = getStreamState(label);
  if (state.textBuffer.length > 0) {
    const remainder = drainTextBuffer(state);
    if (remainder) {
      if (!state.inText) {
        process.stderr.write(`${formatAgentLabel(label)} `);
        state.inText = true;
      }
      process.stderr.write(remainder);
    }
  }
  if (state.inText && !state.endedWithNewline) {
    process.stderr.write("\n");
  }
  state.inText = false;
  state.endedWithNewline = true;
  state.textBuffer = "";
  state.suppressMode = "none";
  state.fenceContent = "";
  state.fenceLang = "";
}

function writePrefixedLine(label: string, text: string, kind: "info" | "tool" = "info"): void {
  endTextLine(label);
  const body = kind === "tool" ? styled("tool", `  ${text}`) : text;
  process.stderr.write(`${formatAgentLabel(label)} ${body}\n`);
}

function shouldEmitToolLine(state: StreamState, summary: string): boolean {
  if (isVagueToolSummary(summary)) {
    state.pendingVagueTool = summary;
    return false;
  }

  if (state.pendingVagueTool && summary !== state.pendingVagueTool) {
    delete state.pendingVagueTool;
  }

  if (state.lastToolLine === summary) return false;

  state.lastToolLine = summary;
  delete state.pendingVagueTool;
  return true;
}

function writeToolLine(label: string, summary: string): void {
  const state = getStreamState(label);
  if (!shouldEmitToolLine(state, summary)) return;
  writePrefixedLine(label, summary, "tool");
}

function writeAssistantText(label: string, text: string): void {
  const state = getStreamState(label);
  state.textBuffer += text;
  const printable = drainTextBuffer(state);
  if (!printable) return;

  if (!state.inText) {
    process.stderr.write(`${formatAgentLabel(label)} `);
    state.inText = true;
  }
  process.stderr.write(printable);
  state.endedWithNewline = printable.endsWith("\n");
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
          if (!text) continue;
          writeAssistantText(label, text);
        } else if (block.type === "tool_use") {
          const detail = summarizeToolUseBlock(block);
          if (detail && !isVagueToolSummary(detail)) {
            writeToolLine(label, detail);
          }
        }
      }
      break;
    }
    case "tool_call": {
      const detail = summarizeToolCall(event);
      if (detail) writeToolLine(label, detail);
      break;
    }
    case "status": {
      if (event.message && event.status !== "FINISHED") {
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
