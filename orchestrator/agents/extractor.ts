import * as fs from "fs";
import { logRunEnd, logRunStart, streamRunWithProgress, withLocalAgent } from "../lib/sdk";
import { requireExtractionTarget } from "../lib/manifest";
import type { SeamManifest } from "../lib/types";

export async function extractor(manifest: SeamManifest) {
  const extractionTarget = requireExtractionTarget(manifest);
  return withLocalAgent(
    async (agent) => {
    const run = await agent.send(`
Seam manifest for ticket ${manifest.ticketId}:
${JSON.stringify(manifest, null, 2)}

Create ${extractionTarget}:

Extract the core logic documented in coreLogic from the seam described above.
Preserve exact behavior — do not reimplement with different operators, guards,
or fallbacks than what coreLogic documents.

Requirements:
1. Create a focused service class at ${extractionTarget}.
2. Preserve the input/output contract described in inputShape and outputShape.
3. Do not move facade concerns into this class — things the manifest attributes
   to the entry point (schedule resolution, global $cfg reads, caller-specific
   orchestration) stay in ${manifest.facadeFile ?? "the facade file"} for the strangler stage.
4. Do not modify any file other than ${extractionTarget}.

DELEGATION RULES — avoid infinite recursion after the strangler stage:

The strangler stage will patch ${manifest.facadeFile ?? "the facade file"} so the
entry-point method calls your new service. Your service must NEVER call back to
that same entry-point method on the facade object (e.g. do not call
$sla->priorityEscalation() from a resolver that priorityEscalation() will delegate to).

Choose the pattern that matches coreLogic:

A) Sub-method delegation — when coreLogic calls OTHER classes or methods
   (not the facade entry point itself), delegate to those implementations.
   Reference: include/Services/SlaGracePeriodCalculator.php delegates to
   BusinessHoursSchedule::addWorkingHours, not to SLA::addGracePeriod().

B) Inline expression lift — when coreLogic IS the entry-point method body
   (self-contained inline expression with no sub-delegation), lift that exact
   expression into the service. Preserve the exact operators and operands from
   coreLogic and constraints (e.g. logical AND vs bitwise AND). Access facade
   state via the passed instance (e.g. $sla->flags), not by calling the
   entry-point method.

CRITICAL constraints from investigation:
${manifest.constraints.join("\n")}

Known side effects to respect, do not introduce NEW ones:
${manifest.sideEffects.join("\n")}
  `);

    logRunStart("extractor");
    const result = await streamRunWithProgress(run, "extractor");
    logRunEnd("extractor", result.status);
    if (result.status === "error") {
      throw new Error(result.error?.message ?? "Extractor run failed");
    }
    if (!fs.existsSync(extractionTarget)) {
      throw new Error(
        `${extractionTarget} was not created by the extractor agent`
      );
    }
    return result;
    },
    { model: "claude-sonnet-5", name: `extractor · ${manifest.ticketId}` }
  );
}
