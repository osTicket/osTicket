import { logRunEnd, logRunStart, streamAndWait, withCloudAgent } from "../lib/sdk";
import { requireExtractionTarget, requireFacadeFile } from "../lib/manifest";
import type { SeamManifest, ParityReport } from "../lib/types";

export async function prAgent(manifest: SeamManifest, report: ParityReport) {
  const extractionTarget = requireExtractionTarget(manifest);
  const facadeFile = requireFacadeFile(manifest);
  return withCloudAgent(
    async (agent) => {
    const run = await agent.send(`
Open a pull request for the strangler extraction for ticket ${manifest.ticketId}.

The PR should include the delegating extraction changes:
- ${extractionTarget} (new extracted service)
- ${facadeFile} (patched facade delegating to the service)

Use the following PR description:

## Summary

Seam manifest for ticket ${manifest.ticketId}:
- Entry point: ${manifest.entryPoint}
- Core logic: ${manifest.coreLogic}
- Consumers: ${manifest.consumers.join(", ")}
- Input: ${manifest.inputShape}
- Output: ${manifest.outputShape}
- Side effects: ${manifest.sideEffects.join("; ")}
- Constraints: ${manifest.constraints.join("; ")}

## Parity verification

${report.passed}/${report.totalCases} golden fixture cases passed.

## Note

This is a delegating extraction, not a reimplementation. The new service at
${extractionTarget} wraps existing logic from ${manifest.coreLogic}
rather than reimplementing it.

Create the PR with gh pr create and ensure it is opened against the base branch.
  `);

    logRunStart("pr-agent");
    const result = await streamAndWait(run, "pr-agent");
    logRunEnd("pr-agent", result.status);
    if (result.status === "error") {
      throw new Error(result.error?.message ?? "PR agent run failed");
    }
    return { prUrl: result.git?.branches?.[0]?.prUrl ?? "" };
    },
    { model: "composer-2.5", name: `pr-agent · ${manifest.ticketId}` }
  );
}
