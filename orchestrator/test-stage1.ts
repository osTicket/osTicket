import "dotenv/config";
import { cartographer } from "./agents/cartographer";

function parseArgs(): { ticketId: string; acceptanceCriteria: string } {
  const args = process.argv.slice(2);
  let ticketId: string | undefined;
  let acceptanceCriteria: string | undefined;

  for (let i = 0; i < args.length; i++) {
    if (args[i] === "--criteria" && args[i + 1]) {
      acceptanceCriteria = args[i + 1];
      i++;
    } else if (args[i].startsWith("MOD-")) {
      ticketId = args[i];
    }
  }

  if (!ticketId) {
    console.error("Usage: test-stage1.ts MOD-<id> --criteria <text>");
    process.exit(1);
  }
  if (!acceptanceCriteria) {
    console.error(`Ticket ${ticketId} requires --criteria`);
    process.exit(1);
  }

  return { ticketId, acceptanceCriteria };
}

const { ticketId, acceptanceCriteria } = parseArgs();

process.stderr.write(`Starting cartographer for ${ticketId}...\n`);

cartographer(ticketId, acceptanceCriteria)
  .then(result => console.log(JSON.stringify(result, null, 2)))
  .catch(err => console.error("Stage 1 failed:", err));
