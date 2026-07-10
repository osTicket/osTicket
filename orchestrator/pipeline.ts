import "dotenv/config";
import {
  STATUS_IN_PROGRESS,
  STATUS_IN_REVIEW,
  addIssueComment,
  buildInReviewComment,
  updateTicketStatus,
} from "./lib/linear";
import { notifyPrOpened } from "./lib/slack";
import { writeStageBanner } from "./lib/sdk";
import { logPipelineLine } from "./lib/terminal";
import { baselineCapture } from "./agents/baselineCapture";
import { cartographer } from "./agents/cartographer";
import { fixtureGenerator } from "./agents/fixtureGenerator";
import { harnessBuilder } from "./agents/harnessBuilder";
import { extractor } from "./agents/extractor";
import { strangler } from "./agents/strangler";
import { verifier } from "./agents/verifier";
import { prAgent } from "./agents/prAgent";

export async function runPipeline(
  ticketId: string,
  acceptanceCriteria: string,
  fromStage = 1
): Promise<void> {
  writeStageBanner("cartographer", ticketId);
  const manifest = await cartographer(
    ticketId,
    acceptanceCriteria,
    fromStage > 1
  );
  logPipelineLine(
    `Manifest ready · ${manifest.sideEffects.length} side effects · facade ${manifest.facadeFile ?? "(unknown)"}`
  );

  if (fromStage <= 2) {
    writeStageBanner("harness-builder", ticketId);
    await harnessBuilder(manifest);
    writeStageBanner("fixture-generator", ticketId);
    await fixtureGenerator(manifest);
    writeStageBanner("baseline-capture", ticketId);
    await baselineCapture(ticketId);
  }

  if (fromStage <= 3) {
    writeStageBanner("extractor", ticketId);
    await extractor(manifest);
  }

  if (fromStage <= 4) {
    writeStageBanner("strangler", ticketId);
    await strangler(manifest);
  }

  writeStageBanner("verifier", ticketId);
  const report = await verifier(ticketId);
  console.log(
    `Parity: ${report.passed}/${report.totalCases} passed, ${report.failed} failed`
  );

  if (!report.gatePassed) {
    console.error("=".repeat(72));
    console.error(`PARITY GATE FAILED for ${ticketId} — pipeline halted, no PR opened`);
    console.error(
      `Ticket remains in "${STATUS_IN_PROGRESS}" (no Blocked state in this workspace)`
    );
    console.error(`${report.failed} mismatch(es) of ${report.totalCases} cases:`);
    for (const m of report.mismatches) {
      console.error(`  ${m.name}: expected ${m.expected}, got ${m.actual}`);
    }
    console.error("=".repeat(72));
    return;
  }

  console.log(
    "Parity gate passed. Pausing for human review before opening a PR."
  );
  console.log(
    "(In a full production version, Agent.resume would pause here for approval. " +
      "This is unverified — the pause is manual for the live demo.)"
  );

  writeStageBanner("pr-agent", ticketId);
  const { prUrl } = await prAgent(manifest, report);
  console.log(`PR URL: ${prUrl}`);
  await notifyPrOpened(ticketId, report, prUrl);
  await addIssueComment(ticketId, buildInReviewComment(manifest, report, prUrl));
  await updateTicketStatus(ticketId, STATUS_IN_REVIEW);
  console.log(`Linear ticket ${ticketId} moved to ${STATUS_IN_REVIEW}`);
}

function parseArgs(): {
  ticketId: string;
  acceptanceCriteria: string;
  fromStage: number;
} {
  const args = process.argv.slice(2);
  let fromStage = 1;
  let ticketId: string | undefined;
  let acceptanceCriteria: string | undefined;

  for (let i = 0; i < args.length; i++) {
    if (args[i] === "--from-stage" && args[i + 1]) {
      fromStage = parseInt(args[i + 1], 10);
      i++;
    } else if (args[i] === "--criteria" && args[i + 1]) {
      acceptanceCriteria = args[i + 1];
      i++;
    } else if (args[i].startsWith("MOD-")) {
      ticketId = args[i];
    }
  }

  if (!ticketId) {
    console.error("Usage: pipeline.ts MOD-<id> --criteria <text> [--from-stage N]");
    process.exit(1);
  }
  if (!acceptanceCriteria && fromStage <= 1) {
    console.error(`Ticket ${ticketId} requires --criteria for a fresh run (fromStage <= 1).`);
    process.exit(1);
  }

  return {
    ticketId: ticketId as string,
    acceptanceCriteria: acceptanceCriteria ?? "",
    fromStage,
  };
}

const isMain = process.argv[1]?.includes("pipeline") ?? false;

if (isMain) {
  const { ticketId, acceptanceCriteria, fromStage } = parseArgs();

  runPipeline(ticketId, acceptanceCriteria, fromStage).catch((err) => {
    console.error("Pipeline failed:", err);
    process.exit(1);
  });
}
