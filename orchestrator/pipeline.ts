import "dotenv/config";
import { cartographer } from "./agents/cartographer";
import { extractor } from "./agents/extractor";
import { strangler } from "./agents/strangler";
import { verifier } from "./agents/verifier";
import { prAgent } from "./agents/prAgent";

export async function runPipeline(
  ticketId: string,
  acceptanceCriteria: string,
  fromStage = 1
): Promise<void> {
  const manifest = await cartographer(
    ticketId,
    acceptanceCriteria,
    fromStage > 1
  );
  console.log("Manifest side effects:", manifest.sideEffects);

  if (fromStage <= 2) {
    await extractor(manifest);
  }

  if (fromStage <= 3) {
    await strangler(manifest);
  }

  const report = await verifier();
  console.log(
    `Parity: ${report.passed}/${report.totalCases} passed, ${report.failed} failed`
  );

  if (!report.gatePassed) {
    console.error("Parity gate failed — mismatches:");
    for (const m of report.mismatches) {
      console.error(`  ${m.name}: expected ${m.expected}, got ${m.actual}`);
    }
    return;
  }

  console.log(
    "Parity gate passed. Pausing for human review before opening a PR."
  );
  console.log(
    "(In a full production version, Agent.resume would pause here for approval. " +
      "This is unverified — the pause is manual for the live demo.)"
  );

  const { prUrl } = await prAgent(manifest, report);
  console.log(`PR URL: ${prUrl}`);
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

const { ticketId, acceptanceCriteria, fromStage } = parseArgs();

runPipeline(ticketId, acceptanceCriteria, fromStage).catch((err) => {
  console.error("Pipeline failed:", err);
  process.exit(1);
});
