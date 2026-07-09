import "dotenv/config";
import * as fs from "fs";
import { cartographer } from "./agents/cartographer";
import { extractor } from "./agents/extractor";
import { strangler } from "./agents/strangler";
import { loadManifest, requireExtractionTarget, requireFacadeFile } from "./lib/manifest";

const ticketId = process.argv[2];
if (!ticketId) {
  console.error("Usage: test-stage3.ts MOD-<id>");
  process.exit(1);
}

async function main() {
  const manifest = await cartographer(ticketId, "", true);
  console.log("Running extractor with cached manifest for", manifest.ticketId);

  const extractorResult = await extractor(manifest);
  console.log("Extractor status:", extractorResult.status);

  console.log("Running strangler with cached manifest for", manifest.ticketId);

  const stranglerResult = await strangler(manifest);
  console.log("Strangler status:", stranglerResult.status);

  const cached = loadManifest(ticketId);
  const facadeFile = requireFacadeFile(cached);
  const extractionTarget = requireExtractionTarget(cached);
  const php = fs.readFileSync(facadeFile, "utf-8");
  console.log(`\n--- Patched facade: ${facadeFile} ---\n`);
  console.log(php.slice(0, 2000) + (php.length > 2000 ? "\n...(truncated)" : ""));

  const serviceName = extractionTarget.split("/").pop()?.replace(".php", "");
  if (serviceName && !php.includes(serviceName)) {
    throw new Error(`${facadeFile} does not reference ${serviceName}`);
  }

  console.log(`\nVerification passed: ${facadeFile} patched to use ${extractionTarget}`);
}

main().catch((err) => {
  console.error("Stage 3 failed:", err);
  process.exit(1);
});
