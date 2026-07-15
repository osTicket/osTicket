import { verifier } from "./agents/verifier";
import {
  discoverTicketIds,
  ensureManifestInState,
  evaluateCiParity,
} from "./lib/parityScope";
import type { ParityReport } from "./lib/types";

function printReport(ticketId: string, report: ParityReport): void {
  console.log("=".repeat(72));
  console.log(`PARITY REPORT — ${ticketId}`);
  console.log(
    `Summary: ${report.passed}/${report.totalCases} passed, ${report.failed} failed`
  );
  console.log(`Gate: ${report.gatePassed ? "PASSED" : "FAILED"}`);

  if (report.mismatches.length > 0) {
    console.log("\nMismatches:");
    for (const mismatch of report.mismatches) {
      console.log(`  • ${mismatch.name}`);
      console.log(`      expected: ${mismatch.expected}`);
      console.log(`      actual:   ${mismatch.actual}`);
    }
  } else {
    console.log("\nAll fixture cases matched their expected values.");
  }

  console.log("=".repeat(72));
  console.log(JSON.stringify(report, null, 2));
}

async function main(): Promise<void> {
  const evaluateOnly = process.argv.includes("--evaluate");

  const decision = evaluateCiParity();
  if (evaluateOnly) {
    process.stdout.write(decision.action);
    return;
  }

  if (decision.action === "skip") {
    console.log(`Parity check skipped: ${decision.reason}`);
    return;
  }

  const ticketIds = discoverTicketIds();
  if (ticketIds.length === 0) {
    console.log("No MOD-* fixture folders found — nothing to verify.");
    return;
  }

  console.log(`Parity scope: ${decision.reason}`);
  console.log(`Tickets to verify: ${decision.verifiableTicketIds.join(", ")}\n`);

  const results: { ticketId: string; report: ParityReport }[] = [];

  for (const ticketId of decision.verifiableTicketIds) {
    const manifestPath = ensureManifestInState(ticketId);
    if (!manifestPath) {
      console.log(
        `Skipping ${ticketId}: no manifest in checkout (runtime cartographer output; pipeline parity is authoritative).`
      );
      continue;
    }

    const report = await verifier(ticketId);
    printReport(ticketId, report);
    results.push({ ticketId, report });
    console.log("");
  }

  if (results.length === 0) {
    console.log(
      "Parity check skipped: seam changes detected but no manifest available in checkout."
    );
    return;
  }

  const failed = results.filter((r) => !r.report.gatePassed);

  console.log("=".repeat(72));
  console.log("OVERALL PARITY SUMMARY");
  for (const { ticketId, report } of results) {
    console.log(
      `  ${ticketId}: ${report.passed}/${report.totalCases} passed — ${report.gatePassed ? "PASSED" : "FAILED"}`
    );
  }
  console.log("=".repeat(72));

  if (failed.length > 0) {
    console.error(
      `\nParity gate failed for: ${failed.map((r) => r.ticketId).join(", ")}`
    );
    process.exit(1);
  }
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
