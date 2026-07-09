import "dotenv/config";
import { findReadyTicket, getLinearTicket } from "./lib/linear";
import { runPipeline } from "./pipeline";

const POLL_INTERVAL_MS = 5000;
const processed = new Set<string>();

async function poll(): Promise<void> {
  try {
    const ticketId = await findReadyTicket();
    if (!ticketId || processed.has(ticketId)) {
      return;
    }

    processed.add(ticketId);
    console.log(`Starting pipeline for ticket ${ticketId}`);
    const ticket = await getLinearTicket(ticketId);
    await runPipeline(ticketId, ticket.description);
  } catch (err) {
    console.error("Poll failed:", err);
  }
}

console.log("Listener started — polling Linear for Ready tickets every 5s");

setInterval(() => {
  void poll();
}, POLL_INTERVAL_MS);

void poll();
