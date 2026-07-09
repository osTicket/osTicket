import * as fs from "fs";
import * as path from "path";
import { runHarness } from "../lib/harness";
import { fixtureDir } from "./fixtureGenerator";
import type { Fixture, ParityReport } from "../lib/types";

export async function verifier(ticketId: string): Promise<ParityReport> {
  const dir = fixtureDir(ticketId);
  if (!fs.existsSync(dir)) {
    throw new Error(`Fixture directory not found: ${dir}`);
  }
  const files = fs.readdirSync(dir).filter(f => f.endsWith(".json"));
  const mismatches: ParityReport["mismatches"] = [];

  for (const file of files) {
    const fixture: Fixture = JSON.parse(fs.readFileSync(path.join(dir, file), "utf-8"));
    if (fixture.expected === undefined) {
      mismatches.push({ name: fixture.name, expected: null, actual: "SKIPPED — no expected value captured yet" });
      continue;
    }
    try {
      const output = runHarness({
        start: fixture.start,
        hours: fixture.graceHours,
        schedule_id: fixture.scheduleId,
      });
      if (output !== fixture.expected) {
        mismatches.push({ name: fixture.name, expected: fixture.expected, actual: output ?? "null" });
      }
    } catch (err) {
      mismatches.push({ name: fixture.name, expected: fixture.expected, actual: `error: ${(err as Error).message}` });
    }
  }

  const report: ParityReport = {
    totalCases: files.length,
    passed: files.length - mismatches.length,
    failed: mismatches.length,
    mismatches,
    gatePassed: mismatches.length === 0,
  };
  fs.writeFileSync("orchestrator/fixtures/parity.json", JSON.stringify(report, null, 2));
  return report;
}
