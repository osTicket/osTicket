import { streamRunWithProgress, withLocalAgent } from "../lib/sdk";
import type { SeamManifest } from "../lib/types";

export async function strangler(manifest: SeamManifest) {
  return withLocalAgent(async (agent) => {
    const run = await agent.send(`
Seam: ${JSON.stringify(manifest)}

Patch include/class.sla.php to delegate date-math to the existing
include/Services/SlaGracePeriodCalculator.php.

You make the smallest possible patch to include/class.sla.php. Preserve the
existing method signature of addGracePeriod exactly. Preserve the existing
schedule precedence (requested, then local, then $cfg default) exactly as it
works today, that logic stays in this file, it does not move into the new
calculator class. Only the actual date-math delegation moves.

Requirements:
1. Add an include_once for include/Services/SlaGracePeriodCalculator.php.
2. Keep schedule resolution in addGracePeriod: explicit $schedule argument,
   then $this->getSchedule(), then $cfg->getDefaultSchedule().
3. Replace the inline BusinessHours / DateInterval date-math with a call to
   SlaGracePeriodCalculator::calculate($date, $this->getGracePeriod(),
   $resolvedScheduleOrNull, $timeline).
4. Do not change addGracePeriod's signature, return shape, or in-place
   DateTime mutation contract.
5. Do not touch include/class.businesshours.php, include/class.schedule.php,
   include/Services/SlaGracePeriodCalculator.php, or any file other than
   include/class.sla.php.

CRITICAL constraints from investigation: ${manifest.constraints.join("\n")}
Known side effects to preserve, do not introduce NEW ones: ${manifest.sideEffects.join("\n")}
  `);

    process.stderr.write(`[strangler] run ${run.id} started\n`);
    const result = await streamRunWithProgress(run, "strangler");
    process.stderr.write(`[strangler] run finished (${result.status})\n`);
    if (result.status === "error") {
      throw new Error(result.error?.message ?? "Strangler run failed");
    }
    return result;
  });
}
