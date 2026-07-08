import "dotenv/config";
import * as fs from "fs";
import { cartographer } from "./agents/cartographer";
import { extractor } from "./agents/extractor";
import { strangler } from "./agents/strangler";

const ticketId = process.argv[2] || "MOD-1";
const SLA_PATH = "include/class.sla.php";

async function main() {
  const manifest = await cartographer(
    ticketId,
    "Extract SLA grace period calculation into a testable, delegating service",
    true
  );
  console.log("Running extractor with cached manifest for", manifest.ticketId);

  const extractorResult = await extractor(manifest);
  console.log("Extractor status:", extractorResult.status);

  console.log("Running strangler with cached manifest for", manifest.ticketId);

  const stranglerResult = await strangler(manifest);
  console.log("Strangler status:", stranglerResult.status);

  const php = fs.readFileSync(SLA_PATH, "utf-8");
  console.log("\n--- Patched addGracePeriod region ---\n");

  const match = php.match(/function addGracePeriod[\s\S]*?^    \}/m);
  console.log(match?.[0] ?? "(could not extract addGracePeriod block)");

  if (!php.includes("SlaGracePeriodCalculator")) {
    throw new Error(`${SLA_PATH} does not reference SlaGracePeriodCalculator`);
  }
  if (!php.includes("calculate(")) {
    throw new Error(`${SLA_PATH} does not call calculate()`);
  }
  if (!php.includes("getDefaultSchedule()")) {
    throw new Error(`${SLA_PATH} no longer preserves $cfg default schedule fallback`);
  }
  if (php.includes("$schedule->addWorkingHours")) {
    throw new Error(`${SLA_PATH} still delegates inline to $schedule->addWorkingHours`);
  }

  console.log("\nVerification passed: class.sla.php patched to use SlaGracePeriodCalculator");
}

main().catch((err) => {
  console.error("Stage 3 failed:", err);
  process.exit(1);
});
