import { execFileSync } from "child_process";
import * as fs from "fs";
import * as path from "path";
import { hasGoldenFixtures } from "./fixtures";
import type { SeamManifest } from "./types";

const FIXTURES_ROOT = "orchestrator/fixtures";
const MANIFESTS_DIR = "orchestrator/manifests";
const STATE_DIR = "orchestrator/.state";

/** Directories that always belong to the parity scope. */
const PARITY_SCOPE_PREFIXES = [
  "orchestrator/fixtures/",
  "orchestrator/manifests/",
  "include/Services/",
  "legacy/harness/",
];

function normalizePath(filePath: string): string {
  return filePath.replace(/\\/g, "/");
}

function loadManifestFile(manifestFile: string): SeamManifest | null {
  try {
    return JSON.parse(fs.readFileSync(manifestFile, "utf-8")) as SeamManifest;
  } catch {
    return null;
  }
}

function listCommittedManifestFiles(): string[] {
  if (!fs.existsSync(MANIFESTS_DIR)) {
    return [];
  }
  return fs
    .readdirSync(MANIFESTS_DIR)
    .filter((name) => name.endsWith("-manifest.json"))
    .map((name) => path.join(MANIFESTS_DIR, name));
}

/** Build parity scope from static roots plus any committed manifest paths. */
export function buildParityScopePaths(): string[] {
  const scope = new Set<string>(PARITY_SCOPE_PREFIXES);

  for (const manifestFile of listCommittedManifestFiles()) {
    const manifest = loadManifestFile(manifestFile);
    if (!manifest) {
      continue;
    }
    for (const value of [
      manifest.facadeFile,
      manifest.extractionTarget,
      manifest.harnessScript,
    ]) {
      if (value) {
        scope.add(normalizePath(value));
      }
    }
    scope.add(`${FIXTURES_ROOT}/${manifest.ticketId}/`);
    scope.add(path.join(MANIFESTS_DIR, `${manifest.ticketId}-manifest.json`));
  }

  return [...scope];
}

export function isParityRelevantPath(
  filePath: string,
  scopePaths: string[] = buildParityScopePaths()
): boolean {
  const normalized = normalizePath(filePath);
  return scopePaths.some((scopePath) => {
    const normalizedScope = normalizePath(scopePath);
    if (normalizedScope.endsWith("/")) {
      return normalized.startsWith(normalizedScope);
    }
    return normalized === normalizedScope || normalized.startsWith(`${normalizedScope}/`);
  });
}

export function hasParityRelevantChanges(changedFiles: string[]): boolean {
  return changedFiles.some((file) => isParityRelevantPath(file));
}

/** Changed files for the current PR/branch; null when git diff is unavailable. */
export function getChangedFiles(): string[] | null {
  const base = process.env.PARITY_DIFF_BASE?.trim();
  const candidates = base
    ? [`${base}...HEAD`]
    : ["origin/main...HEAD", "origin/develop...HEAD", "HEAD~1...HEAD"];

  for (const diffRange of candidates) {
    try {
      const output = execFileSync("git", ["diff", "--name-only", diffRange], {
        encoding: "utf-8",
        stdio: ["ignore", "pipe", "pipe"],
      }).trim();
      if (!output) {
        return [];
      }
      return output.split("\n").filter(Boolean);
    } catch {
      // try next diff range
    }
  }

  return null;
}

export function discoverTicketIds(): string[] {
  if (!fs.existsSync(FIXTURES_ROOT)) {
    return [];
  }

  return fs
    .readdirSync(FIXTURES_ROOT, { withFileTypes: true })
    .filter((entry) => entry.isDirectory())
    .map((entry) => entry.name)
    .filter((ticketId) => /^MOD-\d+$/.test(ticketId))
    .filter((ticketId) => {
      const dir = path.join(FIXTURES_ROOT, ticketId);
      return hasGoldenFixtures(dir);
    })
    .sort();
}

export function resolveManifestPath(ticketId: string): string | null {
  const statePath = path.join(STATE_DIR, `${ticketId}-manifest.json`);
  if (fs.existsSync(statePath)) {
    return statePath;
  }

  const committedPath = path.join(MANIFESTS_DIR, `${ticketId}-manifest.json`);
  if (fs.existsSync(committedPath)) {
    return committedPath;
  }

  return null;
}

export function ensureManifestInState(ticketId: string): string | null {
  const resolved = resolveManifestPath(ticketId);
  if (!resolved) {
    return null;
  }

  const statePath = path.join(STATE_DIR, `${ticketId}-manifest.json`);
  if (resolved !== statePath) {
    fs.mkdirSync(STATE_DIR, { recursive: true });
    fs.copyFileSync(resolved, statePath);
    console.log(`Copied manifest from ${resolved}`);
  }

  return statePath;
}

export type ParityScopeDecision = {
  action: "run" | "skip";
  reason: string;
  verifiableTicketIds: string[];
};

/** Decide whether CI should run parity (including Docker bootstrap). */
export function evaluateCiParity(): ParityScopeDecision {
  if (process.env.PARITY_FORCE === "1") {
    const verifiable = discoverTicketIds().filter((id) => resolveManifestPath(id));
    return {
      action: verifiable.length > 0 ? "run" : "skip",
      reason:
        verifiable.length > 0
          ? "PARITY_FORCE=1 with manifest(s) available"
          : "PARITY_FORCE=1 but no manifest in checkout",
      verifiableTicketIds: verifiable,
    };
  }

  const changed = getChangedFiles();
  const ticketIds = discoverTicketIds();
  const verifiable = ticketIds.filter((id) => resolveManifestPath(id) !== null);

  if (process.env.CI && changed !== null && changed.length === 0) {
    return {
      action: "skip",
      reason: "no changed files in PR diff",
      verifiableTicketIds: [],
    };
  }

  if (changed !== null && changed.length > 0 && !hasParityRelevantChanges(changed)) {
    return {
      action: "skip",
      reason: "no MOD seam-related file changes in PR diff",
      verifiableTicketIds: [],
    };
  }

  if (verifiable.length === 0) {
    return {
      action: "skip",
      reason:
        "no cartographer manifest in checkout (runtime-only in demo; parity enforced in pipeline before PR)",
      verifiableTicketIds: [],
    };
  }

  if (changed === null && !process.env.CI) {
    return {
      action: "run",
      reason: "local run with manifest(s) available",
      verifiableTicketIds: verifiable,
    };
  }

  return {
    action: "run",
    reason: "seam-related changes and manifest(s) available",
    verifiableTicketIds: verifiable,
  };
}
