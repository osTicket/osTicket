import { styleText } from "node:util";

const tty = process.stderr.isTTY ?? false;

export function styled(kind: string, text: string): string {
  if (!tty) return text;
  switch (kind) {
    case "banner":
      return styleText(["bold", "cyan"], text);
    case "label":
      return styleText("dim", text);
    case "tool":
      return styleText("gray", text);
    case "success":
      return styleText("green", text);
    case "error":
      return styleText("red", text);
    case "info":
      return styleText("white", text);
    default:
      return text;
  }
}

export function writeStageBanner(stage: string, ticketId?: string): void {
  const suffix = ticketId ? ` · ${ticketId}` : "";
  const line = `── ${stage}${suffix} ──`;
  process.stderr.write(styled("banner", line) + "\n");
}

export function formatAgentLabel(label: string): string {
  return styled("label", `[${label}]`);
}

export function logRunStart(label: string): void {
  process.stderr.write(`${formatAgentLabel(label)} ${styled("info", "started")}\n`);
}

export function logRunEnd(label: string, status: string): void {
  const kind = status === "error" ? "error" : "success";
  process.stderr.write(
    `${formatAgentLabel(label)} ${styled(kind, status)}\n`
  );
}

export function logAgentLine(label: string, text: string, kind: "info" | "tool" = "info"): void {
  const body = kind === "tool" ? styled("tool", text) : text;
  process.stderr.write(`${formatAgentLabel(label)} ${body}\n`);
}

export function logPipelineLine(text: string): void {
  process.stderr.write(styled("info", text) + "\n");
}
