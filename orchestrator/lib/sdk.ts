import { Agent } from "@cursor/sdk";
import type { Run, RunResult, SDKMessage, SDKToolUseMessage, ToolUseBlock } from "@cursor/sdk";

export function createCloudAgent(model: string = "composer-2.5") {
  return Agent.create({
    apiKey: process.env.CURSOR_API_KEY!,
    model: { id: model },
    cloud: {
      repos: [{ url: process.env.GITHUB_REPO_URL!, startingRef: process.env.GITHUB_DEMO_BRANCH! }],
    },
  });
}

export function createLocalAgent(model: string = "composer-2.5") {
  return Agent.create({
    apiKey: process.env.CURSOR_API_KEY!,
    model: { id: model },
    local: { cwd: process.cwd() },
  });
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

function handleStreamEvent(event: SDKMessage, label: string): void {
  const prefix = `[${label}] `;

  switch (event.type) {
    case "assistant": {
      let wrotePrefix = false;
      let wroteText = false;

      for (const block of event.message.content) {
        if (block.type === "text") {
          const text = block.text;
          if (!text || looksLikeJsonOutput(text)) continue;
          if (!wrotePrefix) {
            process.stderr.write(prefix);
            wrotePrefix = true;
          }
          process.stderr.write(text);
          wroteText = true;
        } else if (block.type === "tool_use") {
          const detail = summarizeToolUseBlock(block);
          if (detail) process.stderr.write(`${prefix}${detail}\n`);
        }
      }

      if (wroteText && wrotePrefix) {
        const lastText = [...event.message.content]
          .reverse()
          .find((block): block is { type: "text"; text: string } => block.type === "text");
        if (lastText && !lastText.text.endsWith("\n")) {
          process.stderr.write("\n");
        }
      }
      break;
    }
    case "tool_call": {
      const detail = summarizeToolCall(event);
      if (detail) process.stderr.write(`${prefix}${detail}\n`);
      break;
    }
    case "status": {
      if (event.message) {
        process.stderr.write(`${prefix}${event.status}: ${event.message}\n`);
      }
      break;
    }
    case "task": {
      if (event.text) {
        process.stderr.write(`${prefix}${event.text}\n`);
      }
      break;
    }
  }
}

/** Stream human-friendly progress to stderr, then return the terminal result. */
export async function streamRunWithProgress(run: Run, label: string): Promise<RunResult> {
  for await (const event of run.stream()) {
    handleStreamEvent(event, label);
  }
  return run.wait();
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
