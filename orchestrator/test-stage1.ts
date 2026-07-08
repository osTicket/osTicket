import "dotenv/config";
import { cartographer } from "./agents/cartographer";

const ticketId = process.argv[2] || "MOD-25";

process.stderr.write(`Starting cartographer for ${ticketId}...\n`);

cartographer(ticketId, "Extract SLA grace period calculation into a testable, delegating service")
  .then(result => console.log(JSON.stringify(result, null, 2)))
  .catch(err => console.error("Stage 1 failed:", err));
