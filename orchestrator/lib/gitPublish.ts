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

function currentBranchName(): string {
  try {
    return git(["rev-parse", "--abbrev-ref", "HEAD"]);
  } catch {
    return "";
  }
}

function workingTreeDirty(): boolean {
  return (
    execFileSync("git", ["status", "--porcelain"], { encoding: "utf-8" }).trim()
      .length > 0
  );
}

/**
 * Create or resume a per-ticket branch from the demo base branch.
 * Pushes an empty branch early when new so cloud agents can use startingRef.
 *
 * Preserves uncommitted local work when already on the ticket branch (resume /
 * `--from-stage`), and fetches the strangler head explicitly so fresh checkouts
 * see an existing remote branch.
 */
export function ensureStranglerBranch(ticketId: string): string {
  const base = getBaseBranch();
  const branch = stranglerBranchName(ticketId);

  execFileSync("git", ["fetch", "origin", base], { stdio: "inherit" });

  // Fetch the ticket head so origin/<branch> exists locally on shallow/single-branch clones.
  let remoteExists = false;
  try {
    execFileSync("git", ["fetch", "origin", branch], { stdio: "inherit" });
    git(["rev-parse", "--verify", `origin/${branch}`]);
    remoteExists = true;
  } catch {
    remoteExists = false;
  }

  const alreadyOnBranch = currentBranchName() === branch;
  const dirty = workingTreeDirty();

  if (alreadyOnBranch) {
    // Resume / --from-stage: never reset the tree over local seam artifacts.
    if (remoteExists && !dirty) {
      try {
        execFileSync("git", ["pull", "--ff-only", "origin", branch], {
          stdio: "inherit",
        });
      } catch {
        // Diverged local commits; publish will push or surface the conflict.
      }
    } else if (!remoteExists) {
      execFileSync("git", ["push", "-u", "origin", branch], {
        stdio: "inherit",
      });
    }
    return branch;
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

/**
 * Look up an open PR for a head branch via server-side list query.
 * Prefer this over `gh pr view <branch>`, which often false-negatives
 * (including when that branch is currently checked out).
 */
function findOpenPrUrl(headBranch: string): string | undefined {
  try {
    const raw = execFileSync(
      "gh",
      [
        "pr",
        "list",
        "--head",
        headBranch,
        "--state",
        "open",
        "--limit",
        "1",
        "--json",
        "url",
      ],
      { encoding: "utf-8", stdio: ["ignore", "pipe", "pipe"] }
    ).trim();
    const rows = JSON.parse(raw) as Array<{ url?: string }>;
    const url = rows[0]?.url?.trim();
    return url || undefined;
  } catch {
    return undefined;
  }
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

  const existing = findOpenPrUrl(branch);
  if (existing) {
    return { prUrl: existing };
  }

  try {
    const output = execFileSync(
      "gh",
      [
        "pr",
        "create",
        "--base",
        base,
        "--head",
        branch,
        "--title",
        title,
        "--body",
        body,
      ],
      { encoding: "utf-8", stdio: ["ignore", "pipe", "inherit"] }
    ).trim();

    const urlMatch = output.match(/https:\/\/github\.com\/\S+/);
    return { prUrl: urlMatch?.[0] ?? output };
  } catch (err) {
    // Resume race / residual false-negative: PR already exists after parity.
    const recovered = findOpenPrUrl(branch);
    if (recovered) {
      return { prUrl: recovered };
    }
    throw err;
  }
}
