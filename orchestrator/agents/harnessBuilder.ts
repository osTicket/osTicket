import * as fs from "fs";
import * as path from "path";
import { streamRunWithProgress, withLocalAgent } from "../lib/sdk";
import { requireHarnessScript } from "../lib/manifest";
import type { SeamManifest } from "../lib/types";

const REFERENCE_HARNESS = `<?php
require_once __DIR__ . '/../../main.inc.php';

$input = json_decode($argv[1], true);
$schedule = BusinessHoursSchedule::lookup($input['schedule_id']);
$bh = new BusinessHours($schedule);
$date = new DateTime($input['start']);
$result = $bh->addWorkingHours($date, $input['hours']);

echo json_encode([
    'input' => $input,
    'output' => $result ? $result->format('Y-m-d\\TH:i:s') : null,
]);
`;

export async function harnessBuilder(manifest: SeamManifest): Promise<void> {
  const harnessScript = requireHarnessScript(manifest);
  if (fs.existsSync(harnessScript)) {
    process.stderr.write(
      `[harness-builder] harness exists at ${harnessScript}, skipping\n`
    );
    return;
  }

  fs.mkdirSync(path.dirname(harnessScript), { recursive: true });

  await withLocalAgent(async (agent) => {
    const run = await agent.send(`
Seam manifest for ticket ${manifest.ticketId}:
${JSON.stringify(manifest, null, 2)}

Create a parity harness PHP script at exactly: ${harnessScript}

Model it closely on the existing reference harness (legacy/harness/sla_capture.php):

${REFERENCE_HARNESS}

Requirements:
1. Bootstrap through main.inc.php via require_once __DIR__ . '/../../main.inc.php';
   Do NOT include isolated class files directly.
2. Parse JSON from $argv[1] into $input, with fields matching harnessInputShape.
3. Invoke the real legacy code described in entryPoint and coreLogic — call the
   actual facade method on a properly constructed object, not a reimplementation.
4. Echo json_encode(['input' => $input, 'output' => $result]) where $result is
   the raw return value from the invoked method (preserve scalar types; do not
   cast booleans unless the reference harness does).
5. Write ONLY ${harnessScript} — do not modify any other file.

Harness input shape:
${manifest.harnessInputShape ?? manifest.inputShape}

Entry point:
${manifest.entryPoint}

Core logic:
${manifest.coreLogic}

Output shape (for formatting $result if needed):
${manifest.outputShape}
    `);

    process.stderr.write(`[harness-builder] run ${run.id} started\n`);
    const result = await streamRunWithProgress(run, "harness-builder");
    process.stderr.write(`[harness-builder] run finished (${result.status})\n`);
    if (result.status === "error") {
      throw new Error(result.error?.message ?? "Harness builder run failed");
    }
  });

  if (!fs.existsSync(harnessScript)) {
    throw new Error(
      `${harnessScript} was not created by the harness builder agent`
    );
  }

  process.stderr.write(`[harness-builder] wrote ${harnessScript}\n`);
}
