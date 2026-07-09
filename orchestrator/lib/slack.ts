import * as fs from "fs";
import * as path from "path";
import type { Fixture, ParityReport } from "./types";

function listFixtureNames(ticketId: string): string[] {
  const dir = `orchestrator/fixtures/${ticketId}`;
  return fs
    .readdirSync(dir)
    .filter((f) => f.endsWith(".json"))
    .map(
      (f) =>
        (JSON.parse(fs.readFileSync(path.join(dir, f), "utf-8")) as Fixture)
          .name
    )
    .sort();
}

export function passedFixtureNames(ticketId: string, report: ParityReport): string[] {
  const failed = new Set(report.mismatches.map((m) => m.name));
  return listFixtureNames(ticketId).filter((name) => !failed.has(name));
}

export function paritySummary(report: ParityReport): string {
  if (report.failed === 0) {
    return `${report.passed}/${report.totalCases} fixtures passed`;
  }
  return `${report.passed}/${report.totalCases} fixtures passed (${report.failed} failed)`;
}

export function buildPrOpenedSlackMessage(
  ticketId: string,
  report: ParityReport,
  prUrl: string
): string {
  const passed = passedFixtureNames(ticketId, report);
  const fixtureList = passed.map((name) => `• ${name}`).join("\n");
  const prLink = prUrl
    ? `<${prUrl}|View pull request>`
    : "_PR URL not returned by agent_";

  return [
    `*${ticketId}* — strangler PR opened`,
    "",
    `Parity: *${paritySummary(report)}*`,
    "",
    "Passed fixtures:",
    fixtureList,
    "",
    prLink,
  ].join("\n");
}

export async function notifyPrOpened(
  ticketId: string,
  report: ParityReport,
  prUrl: string
): Promise<void> {
  const webhookUrl = process.env.SLACK_WEBHOOK_URL;
  if (!webhookUrl) {
    console.warn("SLACK_WEBHOOK_URL not set — skipping Slack notification");
    return;
  }

  const text = buildPrOpenedSlackMessage(ticketId, report, prUrl);
  const res = await fetch(webhookUrl, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      blocks: [{ type: "section", text: { type: "mrkdwn", text } }],
    }),
  });

  if (!res.ok) {
    throw new Error(`Slack webhook failed: ${res.status} ${await res.text()}`);
  }
}
