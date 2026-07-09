import * as fs from "fs";
import * as path from "path";
import { parseJsonResult, streamRunWithProgress, withCloudAgent } from "../lib/sdk";
import { requireHarnessScript } from "../lib/manifest";
import type { Fixture, SeamManifest } from "../lib/types";

export function fixtureDir(ticketId: string): string {
  return `orchestrator/fixtures/${ticketId}`;
}

export async function fixtureGenerator(manifest: SeamManifest): Promise<Fixture[]> {
  const harnessScript = requireHarnessScript(manifest);
  const dir = fixtureDir(manifest.ticketId);
  fs.mkdirSync(dir, { recursive: true });

  return withCloudAgent(async (agent) => {
    const run = await agent.send(`
Seam manifest for ticket ${manifest.ticketId}:
${JSON.stringify(manifest, null, 2)}

Propose 4-6 fixture input definitions that exercise the real distinct behavioral
branches described in coreLogic and sideEffects above — not generic placeholders,
but cases grounded in this ticket's actual logic paths.

Harness script: ${harnessScript}
Harness input shape: ${manifest.harnessInputShape ?? "(see inputShape in manifest)"}

Each fixture must cover a different branch (guards, fallbacks, normal path, edge
cases — only where this manifest documents them).

For each fixture provide:
- name: kebab-case identifier unique within this ticket
- branch: one-line description of which behavioral branch this exercises
- input: JSON object with the fields the harness expects (per harnessInputShape)

Do NOT include an expected field — baseline outputs are captured automatically
by the pipeline after fixture generation.

Respond with ONLY a valid JSON array of fixture objects. No prose before or after.
  `);

    process.stderr.write(`[fixture-generator] run ${run.id} started\n`);
    const result = await streamRunWithProgress(run, "fixture-generator");
    process.stderr.write(`[fixture-generator] run finished (${result.status})\n`);
    if (result.status === "error") {
      throw new Error(result.error?.message ?? "Fixture generator run failed");
    }

    const fixtures = parseJsonResult<Fixture[]>(result.result, []);
    if (fixtures.length < 4 || fixtures.length > 6) {
      throw new Error(
        `Fixture generator returned ${fixtures.length} fixtures; expected 4-6`
      );
    }

    for (const fixture of fixtures) {
      delete fixture.expected;
      const filePath = path.join(dir, `${fixture.name}.json`);
      fs.writeFileSync(filePath, JSON.stringify(fixture, null, 2) + "\n");
      process.stderr.write(`[fixture-generator] wrote ${filePath}\n`);
    }

    return fixtures;
  });
}
