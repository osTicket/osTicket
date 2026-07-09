import * as fs from "fs";
import * as path from "path";
import { verifier } from "./agents/verifier";
import type { ParityReport } from "./lib/types";

const FIXTURES_ROOT = "orchestrator/fixtures";

function discoverTicketIds(): string[] {
  if (!fs.existsSync(FIXTURES_ROOT)) {
    return [];
  }

  return fs
    .readdirSync(FIXTURES_ROOT, { withFileTypes: true })
    .filter((entry) => entry.isDirectory())
    .map((entry) => entry.name)
    .filter((ticketId) => {
      const dir = path.join(FIXTURES_ROOT, ticketId);
      const fixtureFiles = fs.readdirSync(dir).filter((f) => f.endsWith(".json"));
      if (fixtureFiles.length === 0) {
        console.log(`Skipping ${ticketId}: no fixture files in ${dir}`);
        return false;
      }
      console.log(`Found ${fixtureFiles.length} fixture(s) for ${ticketId}`);
      return true;
    })
    .sort();
}

function ensureManifest(ticketId: string): void {
  const stateDir = "orchestrator/.state";
  const statePath = path.join(stateDir, `${ticketId}-manifest.json`);
  const committedPath = path.join(
    "orchestrator/manifests",
    `${ticketId}-manifest.json`
  );

  fs.mkdirSync(stateDir, { recursive: true });

  if (!fs.existsSync(statePath) && fs.existsSync(committedPath)) {
    fs.copyFileSync(committedPath, statePath);
    console.log(`Copied manifest from ${committedPath}`);
  }
}

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
  const ticketIds = discoverTicketIds();

  if (ticketIds.length === 0) {
    console.log("No non-empty ticket fixture folders found — nothing to verify.");
    return;
  }

  console.log(`Tickets to verify: ${ticketIds.join(", ")}\n`);

  const results: { ticketId: string; report: ParityReport }[] = [];

  for (const ticketId of ticketIds) {
    ensureManifest(ticketId);
    const report = await verifier(ticketId);
    printReport(ticketId, report);
    results.push({ ticketId, report });
    console.log("");
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
