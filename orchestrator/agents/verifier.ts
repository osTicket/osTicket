import { execSync } from "child_process";
import * as fs from "fs";
import * as path from "path";
import type { Fixture, ParityReport } from "../lib/types";

export async function verifier(): Promise<ParityReport> {
  const fixtureDir = "orchestrator/fixtures/golden";
  const files = fs.readdirSync(fixtureDir).filter(f => f.endsWith(".json"));
  const mismatches: ParityReport["mismatches"] = [];

  for (const file of files) {
    const fixture: Fixture = JSON.parse(fs.readFileSync(path.join(fixtureDir, file), "utf-8"));
    if (fixture.expected === null) {
      mismatches.push({ name: fixture.name, expected: null, actual: "SKIPPED — no expected value captured yet" });
      continue;
    }
    try {
      const raw = execSync(
        `docker compose exec -T web php legacy/harness/sla_capture.php '${JSON.stringify({
          start: fixture.start, hours: fixture.graceHours, schedule_id: fixture.scheduleId,
        })}'`
      ).toString();
      const { output } = JSON.parse(raw);
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
