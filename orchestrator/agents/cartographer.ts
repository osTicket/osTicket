import { createCloudAgent, parseJsonResult, streamRunWithProgress } from "../lib/sdk";
import type { SeamManifest } from "../lib/types";
import * as fs from "fs";

function manifestPath(ticketId: string): string {
  return `orchestrator/.state/${ticketId}-manifest.json`;
}

export async function cartographer(
  ticketId: string,
  acceptanceCriteria: string,
  fromCache: boolean = process.argv.includes("--from-cache") ||
    process.argv.includes("--from-stage")
): Promise<SeamManifest> {
  const statePath = manifestPath(ticketId);
  if (fromCache && fs.existsSync(statePath)) {
    process.stderr.write(`Using cached manifest for ${ticketId}.\n`);
    return JSON.parse(fs.readFileSync(statePath, "utf-8"));
  }

  const agent = await createCloudAgent();

  const run = await agent.send(`
Ticket ${ticketId}: ${acceptanceCriteria}

You investigate legacy PHP code to confirm and document a strangler-fig seam
before any extraction begins. You do not write implementation code.

Your final output is a single JSON object matching the seam-manifest schema.
You trace real call sites using file reads, not assumptions. If a function
you're asked to investigate calls another function, you follow that call
and read it too, before concluding your manifest is complete.

While investigating, briefly narrate progress in short plain-English lines
(e.g. which files you are reading and what you found). Put that narration in
normal assistant text before the final JSON.

When investigation is complete, your FINAL message must contain ONLY valid
JSON matching the seam-manifest schema. No prose before or after the JSON in
that final message.

Investigate the SLA grace-period seam:
1. Read include/class.sla.php, function addGracePeriod. Trace it precisely:
   does it read the global $cfg? Does it call getSchedule(), and does that
   perform a database lookup? Does it mutate the DateTime object it's given
   in place, or return a new one?
2. addGracePeriod does not call BusinessHours::addWorkingHours directly; it
   calls $schedule->addWorkingHours(), where $schedule is a
   BusinessHoursSchedule. Trace BusinessHoursSchedule::addWorkingHours in
   include/class.schedule.php first, then follow it into
   BusinessHours::addWorkingHours in include/class.businesshours.php. Trace
   every branch: partial days, holidays, backtracking. Does IT write to the
   database or read global state, separately from addGracePeriod?
3. Find every call site of addGracePeriod in include/class.ticket.php and
   confirm what consumes its return value (due-date calc, overdue flagging).

Respond with ONLY valid JSON matching:
{
  "entryPoint": string, "coreLogic": string, "consumers": string[],
  "inputShape": string, "outputShape": string,
  "sideEffects": string[], "constraints": string[]
}

List every side effect you find precisely, do not summarize them away, they
determine how the next stage is allowed to build the extraction.
  `);

  process.stderr.write(`[cartographer] run ${run.id} started\n`);
  const result = await streamRunWithProgress(run, "cartographer");
  process.stderr.write(`[cartographer] run finished (${result.status})\n`);
  if (result.status === "error") {
    throw new Error(result.error?.message ?? "Cartographer run failed");
  }
  const manifest = { ticketId, ...parseJsonResult(result.result, {} as Partial<SeamManifest>) } as SeamManifest;
  fs.writeFileSync(statePath, JSON.stringify(manifest, null, 2));
  return manifest;
}
