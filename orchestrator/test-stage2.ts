import "dotenv/config";
import * as fs from "fs";
import { cartographer } from "./agents/cartographer";
import { extractor } from "./agents/extractor";

const ticketId = process.argv[2] || "MOD-25";
const SERVICE_PATH = "include/Services/SlaGracePeriodCalculator.php";

async function main() {
  const manifest = await cartographer(
    ticketId,
    "Extract SLA grace period calculation into a testable, delegating service",
    true
  );
  console.log("Running extractor with cached manifest for", manifest.ticketId);

  const result = await extractor(manifest);
  console.log("Extractor status:", result.status);

  if (!fs.existsSync(SERVICE_PATH)) {
    throw new Error(`${SERVICE_PATH} was not created by the extractor agent`);
  }

  const php = fs.readFileSync(SERVICE_PATH, "utf-8");
  console.log("\n--- Created file ---\n");
  console.log(php);

  if (!php.includes("addWorkingHours")) {
    throw new Error("File does not delegate to addWorkingHours");
  }

  const reimplemented = ["initOccurrences", "while ($_seconds", "workhours", "holidays["];
  for (const pattern of reimplemented) {
    if (php.includes(pattern)) {
      throw new Error(`File appears to reimplement date-walking logic (found: ${pattern})`);
    }
  }

  console.log("\nVerification passed: delegates to addWorkingHours, no reimplemented logic detected");
}

main().catch((err) => {
  console.error("Stage 2 failed:", err);
  process.exit(1);
});
