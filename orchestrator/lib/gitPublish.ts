import { execFileSync } from "child_process";
import type { SeamManifest, ParityReport } from "./types";

export type PublishPaths = {
  ticketId: string;
  branch: string;
  /** Extra paths to stage (e.g. facadeFile, extractionTarget). */
  paths?: string[];
  harnessScript?: string;
};

function git(args: string[], inherit = false): string {
  return execFileSync("git", args, {
    encoding: "utf-8",
    stdio: inherit ? "inherit" : ["ignore", "pipe", "pipe"],
  }).trim();
}

/** Base branch for PRs; required for per-ticket strangler branches. */
export function getBaseBranch(): string {
  const base = process.env.GITHUB_DEMO_BRANCH;
  if (!base) {
    throw new Error(
      "GITHUB_DEMO_BRANCH is required for per-ticket strangler branches"
    );
  }
  return base;
}

export function stranglerBranchName(ticketId: string): string {
  return `strangler/${ticketId}`;
}

/**
 * Create or resume a per-ticket branch from the demo base branch.
 * Pushes an empty branch early when new so cloud agents can use startingRef.
 */
export function ensureStranglerBranch(ticketId: string): string {
  const base = getBaseBranch();
  const branch = stranglerBranchName(ticketId);

  execFileSync("git", ["fetch", "origin", base], { stdio: "inherit" });

  let remoteExists = false;
  try {
    git(["rev-parse", "--verify", `origin/${branch}`]);
    remoteExists = true;
  } catch {
    remoteExists = false;
  }

  if (remoteExists) {
    execFileSync("git", ["checkout", "-B", branch, `origin/${branch}`], {
      stdio: "inherit",
    });
    try {
      execFileSync("git", ["pull", "--ff-only", "origin", branch], {
        stdio: "inherit",
      });
    } catch {
      // Local-only resume; push will reconcile on publish.
    }
  } else {
    execFileSync("git", ["checkout", "-B", branch, `origin/${base}`], {
      stdio: "inherit",
    });
    execFileSync("git", ["push", "-u", "origin", branch], { stdio: "inherit" });
  }

  return branch;
}

/** Paths staged for a ticket — avoids peer fixtures and global parity.json. */
export function publishPathsForTicket(
  ticketId: string,
  extraPaths: string[] = [],
  harnessScript?: string
): string[] {
  const paths = new Set<string>([
    `orchestrator/fixtures/${ticketId}`,
    ...extraPaths.filter(Boolean),
  ]);
  if (harnessScript) {
    paths.add(harnessScript);
  }
  return [...paths];
}

/**
 * Commit and push strangler artifacts on the per-ticket branch.
 * No-ops when the working tree has nothing to publish for the staged paths.
 */
export function publishArtifactsForPr(options: PublishPaths): {
  published: boolean;
  sha?: string;
} {
  const { ticketId, branch, paths = [], harnessScript } = options;
  const toAdd = publishPathsForTicket(ticketId, paths, harnessScript);

  for (const p of toAdd) {
    try {
      execFileSync("git", ["add", "--", p], { stdio: "pipe" });
    } catch {
      // Path may not exist yet for partial resumes; ignore.
    }
  }

  const status = execFileSync("git", ["status", "--porcelain"], {
    encoding: "utf-8",
  }).trim();
  if (!status) {
    return { published: false };
  }

  const message = `chore(${ticketId}): publish strangler artifacts for PR`;
  execFileSync("git", ["commit", "-m", message], { stdio: "inherit" });
  execFileSync("git", ["push", "-u", "origin", branch], { stdio: "inherit" });
  const sha = execFileSync("git", ["rev-parse", "HEAD"], {
    encoding: "utf-8",
  }).trim();
  return { published: true, sha };
}

export function buildPrBody(manifest: SeamManifest, report: ParityReport): string {
  return [
    "## Summary",
    "",
    `Seam manifest for ticket ${manifest.ticketId}:`,
    `- Entry point: ${manifest.entryPoint}`,
    `- Core logic: ${manifest.coreLogic}`,
    `- Consumers: ${manifest.consumers.join(", ")}`,
    `- Input: ${manifest.inputShape}`,
    `- Output: ${manifest.outputShape}`,
    `- Side effects: ${manifest.sideEffects.join("; ")}`,
    `- Constraints: ${manifest.constraints.join("; ")}`,
    "",
    "## Parity verification",
    "",
    `${report.passed}/${report.totalCases} golden fixture cases passed.`,
    "",
    "## Note",
    "",
    "This is a delegating extraction, not a reimplementation. The new service at",
    `${manifest.extractionTarget} wraps existing logic from ${manifest.coreLogic}`,
    "rather than reimplementing it.",
  ].join("\n");
}

/** Open (or return existing) PR with explicit base/head branches. */
export function openPullRequest(options: {
  ticketId: string;
  branch: string;
  title: string;
  body: string;
}): { prUrl: string } {
  const base = getBaseBranch();
  const { branch, title, body } = options;

  try {
    const existing = execFileSync(
      "gh",
      ["pr", "view", branch, "--json", "url", "-q", ".url"],
      { encoding: "utf-8", stdio: ["ignore", "pipe", "pipe"] }
    ).trim();
    if (existing) {
      return { prUrl: existing };
    }
  } catch {
    // No existing PR for this head branch.
  }

  const output = execFileSync(
    "gh",
    ["pr", "create", "--base", base, "--head", branch, "--title", title, "--body", body],
    { encoding: "utf-8", stdio: ["ignore", "pipe", "inherit"] }
  ).trim();

  const urlMatch = output.match(/https:\/\/github\.com\/\S+/);
  return { prUrl: urlMatch?.[0] ?? output };
}
