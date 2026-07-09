import "dotenv/config";
import * as fs from "fs";
import { cartographer } from "./agents/cartographer";
import { extractor } from "./agents/extractor";
import { loadManifest, requireExtractionTarget, requireFacadeFile } from "./lib/manifest";

const ticketId = process.argv[2];
if (!ticketId) {
  console.error("Usage: test-stage2.ts MOD-<id>");
  process.exit(1);
}

async function main() {
  const manifest = await cartographer(ticketId, "", true);
  console.log("Running extractor with cached manifest for", manifest.ticketId);

  const result = await extractor(manifest);
  console.log("Extractor status:", result.status);

  const cached = loadManifest(ticketId);
  const extractionTarget = requireExtractionTarget(cached);
  if (!fs.existsSync(extractionTarget)) {
    throw new Error(`${extractionTarget} was not created by the extractor agent`);
  }

  const php = fs.readFileSync(extractionTarget, "utf-8");
  console.log("\n--- Created file ---\n");
  console.log(php);
  console.log(`\nVerification passed: ${extractionTarget} exists`);
}

main().catch((err) => {
  console.error("Stage 2 failed:", err);
  process.exit(1);
});
