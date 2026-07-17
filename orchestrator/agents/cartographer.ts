import { logRunEnd, logRunStart, parseJsonResult, streamRunWithProgress, withCloudAgent } from "../lib/sdk";
import { applyHarnessDefaults, manifestPath } from "../lib/manifest";
import { logAgentLine } from "../lib/terminal";
import type { SeamManifest } from "../lib/types";
import * as fs from "fs";

export async function cartographer(
  ticketId: string,
  acceptanceCriteria: string,
  fromCache: boolean = process.argv.includes("--from-cache") ||
    process.argv.includes("--from-stage")
): Promise<SeamManifest> {
  const statePath = manifestPath(ticketId);
  if (fromCache && fs.existsSync(statePath)) {
    logAgentLine("cartographer", `Using cached manifest for ${ticketId}.`);
    return applyHarnessDefaults(JSON.parse(fs.readFileSync(statePath, "utf-8")));
  }

  return withCloudAgent(
    async (agent) => {
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

From the acceptance criteria above, identify and trace:

1. **Facade entry point** — the class/method/file callers use today (the seam
   surface that will eventually delegate to extracted code).
2. **Core logic** — the functions/classes that perform the real work, including
   every function in the delegation chain below the facade.
3. **Consumers** — every call site of the entry point and how return values are used.
4. **Input/output contract** — parameter types, in-place mutation vs new objects,
   return shape, by-reference parameters.
5. **Side effects** — DB reads/writes, global state ($cfg etc.), lazy-loading,
   caching, file I/O. List each one precisely; do not summarize them away.
6. **Constraints** — behavioral rules that must be preserved during extraction
   (precedence order, fallbacks, guards, timezone handling, etc.).

Also determine pipeline tooling paths for later stages:

- **facadeFile** — PHP file path (e.g. include/class.sla.php) containing the
  entry-point method to patch in the strangler stage.
- **extractionTarget** — suggested path for the new extracted service class
  (e.g. include/Services/SomeCalculator.php).
- **harnessScript** — path to a legacy/harness/*.php script for parity testing.
  For MOD-25 ONLY: always set to "legacy/harness/sla_capture.php" (the existing
  proven harness — do not suggest a new script for this ticket).
  For all other tickets: use an existing harness only if it fully exercises this
  seam; otherwise suggest a new path like legacy/harness/<seam-name>_capture.php
  that can test facade paths, fallbacks, and edge branches (the harness builder
  stage creates the script automatically from this manifest).
- **harnessInputShape** — plain-English description of the JSON input fields the
  harness expects (field names, types, and what each controls). For MOD-25, match
  sla_capture.php: start, hours, schedule_id.

Respond with ONLY valid JSON matching:
{
  "entryPoint": string,
  "coreLogic": string,
  "consumers": string[],
  "inputShape": string,
  "outputShape": string,
  "sideEffects": string[],
  "constraints": string[],
  "facadeFile": string,
  "extractionTarget": string,
  "harnessScript": string,
  "harnessInputShape": string
}

List every side effect you find precisely — they determine how the next stage
is allowed to build the extraction.
  `);

    logRunStart("cartographer");
    const result = await streamRunWithProgress(run, "cartographer");
    logRunEnd("cartographer", result.status);
    if (result.status === "error") {
      throw new Error(result.error?.message ?? "Cartographer run failed");
    }
    const manifest = applyHarnessDefaults({
      ticketId,
      ...parseJsonResult(result.result, {} as Partial<SeamManifest>),
    } as SeamManifest);
    fs.mkdirSync("orchestrator/.state", { recursive: true });
    fs.writeFileSync(statePath, JSON.stringify(manifest, null, 2));
    return manifest;
    },
    { model: "claude-sonnet-5", name: `cartographer · ${ticketId}` }
  );
}
