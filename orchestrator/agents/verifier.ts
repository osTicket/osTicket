import * as fs from "fs";
import * as path from "path";
import { listGoldenFixtureFiles, PARITY_REPORT_FILENAME } from "../lib/fixtures";
import { runHarness } from "../lib/harness";
import { fixtureHarnessInput, loadManifest, requireHarnessScript } from "../lib/manifest";
import { fixtureDir } from "./fixtureGenerator";
import type { Fixture, ParityReport } from "../lib/types";

export async function verifier(ticketId: string): Promise<ParityReport> {
  const manifest = loadManifest(ticketId);
  const harnessScript = requireHarnessScript(manifest);
  const dir = fixtureDir(ticketId);
  if (!fs.existsSync(dir)) {
    throw new Error(`Fixture directory not found: ${dir}`);
  }
  const files = listGoldenFixtureFiles(dir);
  const mismatches: ParityReport["mismatches"] = [];

  if (files.length === 0) {
    // parity.json alone is not a golden case — never pass with 0/0.
    mismatches.push({
      name: "(no golden fixtures)",
      expected: "≥1 golden fixture JSON file",
      actual: "0 golden fixtures (parity-only or empty directory)",
    });
  }

  for (const file of files) {
    const fixture: Fixture = JSON.parse(fs.readFileSync(path.join(dir, file), "utf-8"));
    if (fixture.expected === undefined) {
      mismatches.push({ name: fixture.name, expected: null, actual: "SKIPPED — no expected value captured yet" });
      continue;
    }
    try {
      const output = runHarness(harnessScript, fixtureHarnessInput(fixture));
      if (output !== fixture.expected) {
        mismatches.push({ name: fixture.name, expected: fixture.expected, actual: output ?? "null" });
      }
    } catch (err) {
      mismatches.push({ name: fixture.name, expected: fixture.expected, actual: `error: ${(err as Error).message}` });
    }
  }

  const report: ParityReport = {
    totalCases: files.length,
    passed: Math.max(0, files.length - mismatches.length),
    failed: mismatches.length,
    mismatches,
    // Sacred gate: require at least one golden case and zero mismatches.
    gatePassed: files.length > 0 && mismatches.length === 0,
  };
  fs.writeFileSync(
    path.join(dir, PARITY_REPORT_FILENAME),
    JSON.stringify(report, null, 2)
  );
  return report;
}
