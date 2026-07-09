import * as fs from "fs";
import type { Fixture, SeamManifest } from "./types";

/** MOD-25 uses the existing hand-verified harness; do not require a new script. */
export const MOD_25_HARNESS_SCRIPT = "legacy/harness/sla_capture.php";
export const MOD_25_HARNESS_INPUT_SHAPE =
  "JSON object with: start (string, ISO 8601 datetime); hours (number, grace period hours — use 0 for guard cases); schedule_id (integer, BusinessHoursSchedule id — 1 for normal business-hours schedule, 5 for empty-schedule fallback testing)";

export function manifestPath(ticketId: string): string {
  return `orchestrator/.state/${ticketId}-manifest.json`;
}

export function loadManifest(ticketId: string): SeamManifest {
  const path = manifestPath(ticketId);
  if (!fs.existsSync(path)) {
    throw new Error(
      `No manifest found for ${ticketId} at ${path}. Run stage 1 (cartographer) first.`
    );
  }
  return applyHarnessDefaults(
    JSON.parse(fs.readFileSync(path, "utf-8")) as SeamManifest
  );
}

/** Pin known harness paths for tickets with existing proven fixtures. */
export function applyHarnessDefaults(manifest: SeamManifest): SeamManifest {
  if (manifest.ticketId === "MOD-25") {
    return {
      ...manifest,
      harnessScript: MOD_25_HARNESS_SCRIPT,
      harnessInputShape: MOD_25_HARNESS_INPUT_SHAPE,
    };
  }
  return manifest;
}

export function requireExtractionTarget(manifest: SeamManifest): string {
  if (!manifest.extractionTarget) {
    throw new Error(
      `Manifest for ${manifest.ticketId} is missing extractionTarget. Re-run cartographer.`
    );
  }
  return manifest.extractionTarget;
}

export function requireFacadeFile(manifest: SeamManifest): string {
  if (!manifest.facadeFile) {
    throw new Error(
      `Manifest for ${manifest.ticketId} is missing facadeFile. Re-run cartographer.`
    );
  }
  return manifest.facadeFile;
}

export function requireHarnessScript(manifest: SeamManifest): string {
  if (!manifest.harnessScript) {
    throw new Error(
      `Manifest for ${manifest.ticketId} is missing harnessScript. Re-run cartographer.`
    );
  }
  return manifest.harnessScript;
}

/** Build harness payload from a fixture (supports generic `input` or legacy SLA fields). */
export function fixtureHarnessInput(fixture: Fixture): Record<string, unknown> {
  if (fixture.input) {
    return fixture.input;
  }
  return {
    start: fixture.start,
    hours: fixture.graceHours,
    schedule_id: fixture.scheduleId,
  };
}
