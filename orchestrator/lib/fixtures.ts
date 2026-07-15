import * as fs from "fs";

/** Written by the verifier into each ticket fixture dir — not a golden case. */
export const PARITY_REPORT_FILENAME = "parity.json";

/**
 * List golden fixture JSON filenames in a ticket fixture directory.
 * Excludes the verifier's parity report so it is never treated as a case.
 */
export function listGoldenFixtureFiles(dir: string): string[] {
  return fs
    .readdirSync(dir)
    .filter((f) => f.endsWith(".json") && f !== PARITY_REPORT_FILENAME)
    .sort();
}

/** True when the directory contains at least one golden fixture (not only parity.json). */
export function hasGoldenFixtures(dir: string): boolean {
  return listGoldenFixtureFiles(dir).length > 0;
}
