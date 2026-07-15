import {
  buildPrBody,
  openPullRequest,
  stranglerBranchName,
} from "../lib/gitPublish";
import { requireExtractionTarget, requireFacadeFile } from "../lib/manifest";
import type { SeamManifest, ParityReport } from "../lib/types";

export async function prAgent(manifest: SeamManifest, report: ParityReport) {
  requireExtractionTarget(manifest);
  requireFacadeFile(manifest);
  const branch = stranglerBranchName(manifest.ticketId);
  const title = `chore(${manifest.ticketId}): strangler extraction`;
  const body = buildPrBody(manifest, report);
  return openPullRequest({
    ticketId: manifest.ticketId,
    branch,
    title,
    body,
  });
}
