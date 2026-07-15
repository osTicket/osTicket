import { execFileSync } from "child_process";

export type PublishPaths = {
  ticketId: string;
  /** Extra paths to stage (e.g. facadeFile, extractionTarget). */
  paths?: string[];
};

/**
 * Commit and push strangler artifacts so a nested cloud PR agent sees them.
 * No-ops when the working tree has nothing to publish for the staged paths.
 */
export function publishArtifactsForPr(options: PublishPaths): {
  published: boolean;
  sha?: string;
} {
  const { ticketId, paths = [] } = options;
  const defaults = [
    "legacy/harness",
    "orchestrator/fixtures",
    "include/Services",
  ];
  const toAdd = [...defaults, ...paths.filter(Boolean)];

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

  const message = `chore(${ticketId}): publish strangler artifacts for PR agent`;
  execFileSync("git", ["commit", "-m", message], { stdio: "inherit" });
  execFileSync("git", ["push", "-u", "origin", "HEAD"], { stdio: "inherit" });
  const sha = execFileSync("git", ["rev-parse", "HEAD"], {
    encoding: "utf-8",
  }).trim();
  return { published: true, sha };
}
