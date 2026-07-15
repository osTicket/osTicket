import * as fs from "fs";
import * as path from "path";
import { listGoldenFixtureFiles } from "../lib/fixtures";
import { runHarness } from "../lib/harness";
import { fixtureHarnessInput, loadManifest, requireHarnessScript } from "../lib/manifest";
import { logAgentLine } from "../lib/terminal";
import type { Fixture } from "../lib/types";
import { fixtureDir } from "./fixtureGenerator";

export async function baselineCapture(ticketId: string): Promise<void> {
  const manifest = loadManifest(ticketId);
  const harnessScript = requireHarnessScript(manifest);
  const dir = fixtureDir(ticketId);
  if (!fs.existsSync(dir)) {
    throw new Error(`Fixture directory not found: ${dir}`);
  }

  const files = listGoldenFixtureFiles(dir);
  let captured = 0;

  for (const file of files) {
    const filePath = path.join(dir, file);
    const fixture: Fixture = JSON.parse(fs.readFileSync(filePath, "utf-8"));

    // null means "not yet captured" from fixtureGenerator; re-run harness to fill in.
    if (typeof fixture.expected === "string") {
      continue;
    }

    const output = runHarness(harnessScript, fixtureHarnessInput(fixture));

    fixture.expected = output;
    fs.writeFileSync(filePath, JSON.stringify(fixture, null, 2) + "\n");
    logAgentLine("baseline-capture", `${fixture.name}: expected ${output ?? "null"}`);
    captured++;
  }

  if (captured === 0) {
    logAgentLine("baseline-capture", "all fixtures already have expected values");
  } else {
    logAgentLine("baseline-capture", `captured ${captured} baseline(s)`);
  }
}
