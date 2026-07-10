import { logRunEnd, logRunStart, streamRunWithProgress, withLocalAgent } from "../lib/sdk";
import { requireExtractionTarget, requireFacadeFile } from "../lib/manifest";
import type { SeamManifest } from "../lib/types";

export async function strangler(manifest: SeamManifest) {
  const facadeFile = requireFacadeFile(manifest);
  const extractionTarget = requireExtractionTarget(manifest);
  return withLocalAgent(
    async (agent) => {
    const run = await agent.send(`
Seam manifest for ticket ${manifest.ticketId}:
${JSON.stringify(manifest, null, 2)}

Patch ${facadeFile} at ${manifest.entryPoint} to delegate to
${extractionTarget}.

You make the smallest possible patch to ${facadeFile}. Preserve the
existing entry-point method signature exactly. Preserve all facade-level behavior
(schedule resolution, globals, caching, caller-visible contracts) that the
manifest attributes to the entry point — only the core logic delegation moves
into ${extractionTarget}.

Requirements:
1. Add an include/require for ${extractionTarget} if needed.
2. Keep facade orchestration (argument resolution, fallbacks, guard checks) in
   ${facadeFile} when the manifest places them at the entry point.
3. Replace inline core logic with a call to the extracted service.
4. Do not change the entry-point signature, return shape, or mutation contract.
5. Do not modify ${extractionTarget} or any file other than
   ${facadeFile}.

CRITICAL constraints from investigation:
${manifest.constraints.join("\n")}

Known side effects to preserve, do not introduce NEW ones:
${manifest.sideEffects.join("\n")}
  `);

    logRunStart("strangler");
    const result = await streamRunWithProgress(run, "strangler");
    logRunEnd("strangler", result.status);
    if (result.status === "error") {
      throw new Error(result.error?.message ?? "Strangler run failed");
    }
    return result;
    },
    { model: "composer-2.5", name: `strangler · ${manifest.ticketId}` }
  );
}
