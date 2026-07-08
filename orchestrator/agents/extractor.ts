import * as fs from "fs";
import { createLocalAgent, streamRunWithProgress } from "../lib/sdk";
import type { SeamManifest } from "../lib/types";

const SERVICE_PATH = "include/Services/SlaGracePeriodCalculator.php";

export async function extractor(manifest: SeamManifest) {
  const agent = await createLocalAgent();

  const run = await agent.send(`
Seam: ${JSON.stringify(manifest)}

Create include/Services/SlaGracePeriodCalculator.php:

1. A single public method, calculate(DateTime $date, float $graceHours,
   ?BusinessHoursSchedule $schedule, array &$timeline = []): DateTime
2. Inside, DELEGATE to the existing BusinessHours::addWorkingHours for the
   actual calculation. Do not reimplement its logic.
3. When $schedule is null or addWorkingHours returns false, fall back to the
   same wall-clock behavior as the original addGracePeriod: round($graceHours
   * 3600) seconds added via DateInterval directly to $date.
4. On the successful working-hours path, populate the by-reference $timeline
   parameter the same way the original code does today, don't drop it.
5. Do not touch include/class.businesshours.php at all.
6. Do not add the $cfg global read or the database schedule lookup into this
   class, those stay in the facade, built in the next stage. This class
   should be a pure wrapper: given a schedule (already resolved), delegate
   the math, return the result.

CRITICAL constraints from investigation: ${manifest.constraints.join("\n")}
Known side effects to respect, do not introduce NEW ones: ${manifest.sideEffects.join("\n")}
  `);

  process.stderr.write(`[extractor] run ${run.id} started\n`);
  const result = await streamRunWithProgress(run, "extractor");
  process.stderr.write(`[extractor] run finished (${result.status})\n`);
  if (result.status === "error") {
    throw new Error(result.error?.message ?? "Extractor run failed");
  }
  if (!fs.existsSync(SERVICE_PATH)) {
    throw new Error(`${SERVICE_PATH} was not created by the extractor agent`);
  }
  return result;
}
