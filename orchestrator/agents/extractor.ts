import * as fs from "fs";
import { streamRunWithProgress, withLocalAgent } from "../lib/sdk";
import { requireExtractionTarget } from "../lib/manifest";
import type { SeamManifest } from "../lib/types";

export async function extractor(manifest: SeamManifest) {
  const extractionTarget = requireExtractionTarget(manifest);
  return withLocalAgent(async (agent) => {
    const run = await agent.send(`
Seam manifest for ticket ${manifest.ticketId}:
${JSON.stringify(manifest, null, 2)}

Create ${extractionTarget}:

Extract the core logic documented in coreLogic from the seam described above.
This is a DELEGATING extraction — call existing legacy code; do not reimplement
business logic that already exists elsewhere in the codebase.

Requirements:
1. Create a focused service class at ${extractionTarget}.
2. Preserve the input/output contract described in inputShape and outputShape.
3. Delegate to the existing implementation in coreLogic rather than copying or
   reimplementing date-walking, DB access, or other complex logic inline.
4. Do not move facade concerns into this class — things the manifest attributes
   to the entry point (schedule resolution, global $cfg reads, caller-specific
   orchestration) stay in ${manifest.facadeFile ?? "the facade file"} for the strangler stage.
5. Do not modify any file other than ${extractionTarget}.

CRITICAL constraints from investigation:
${manifest.constraints.join("\n")}

Known side effects to respect, do not introduce NEW ones:
${manifest.sideEffects.join("\n")}
  `);

    process.stderr.write(`[extractor] run ${run.id} started\n`);
    const result = await streamRunWithProgress(run, "extractor");
    process.stderr.write(`[extractor] run finished (${result.status})\n`);
    if (result.status === "error") {
      throw new Error(result.error?.message ?? "Extractor run failed");
    }
    if (!fs.existsSync(extractionTarget)) {
      throw new Error(
        `${extractionTarget} was not created by the extractor agent`
      );
    }
    return result;
  });
}
