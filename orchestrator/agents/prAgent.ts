import { streamAndWait, withCloudAgent } from "../lib/sdk";
import type { SeamManifest, ParityReport } from "../lib/types";

export async function prAgent(manifest: SeamManifest, report: ParityReport) {
  return withCloudAgent(async (agent) => {
    const run = await agent.send(`
Open a pull request for the SLA grace-period strangler extraction.

The PR should include the delegating extraction changes:
- include/Services/SlaGracePeriodCalculator.php (new service)
- include/class.sla.php (patched to delegate date-math)

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

This is a delegating extraction, not a reimplementation. The new
SlaGracePeriodCalculator wraps the existing BusinessHours::addWorkingHours
logic rather than reimplementing date-math.

Create the PR with gh pr create and ensure it is opened against the base branch.
  `);

    process.stderr.write(`[pr-agent] run ${run.id} started\n`);
    const result = await streamAndWait(run, "pr-agent");
    process.stderr.write(`[pr-agent] run finished (${result.status})\n`);
    if (result.status === "error") {
      throw new Error(result.error?.message ?? "PR agent run failed");
    }
    return { prUrl: result.git?.branches?.[0]?.prUrl ?? "" };
  });
}
