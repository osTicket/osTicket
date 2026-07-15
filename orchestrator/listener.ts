import "dotenv/config";
import {
  findReadyTicket,
  getLinearTicket,
  STATUS_IN_PROGRESS,
  updateTicketStatus,
} from "./lib/linear";
import { runPipeline } from "./pipeline";

const POLL_INTERVAL_MS = 5000;
const processed = new Set<string>();
let pipelineBusy = false;

async function poll(): Promise<void> {
  // Claim the lock before any await so overlapping setInterval ticks cannot
  // both pass the guard and interleave ensureStranglerBranch checkouts.
  if (pipelineBusy) {
    return;
  }
  pipelineBusy = true;

  try {
    const ticketId = await findReadyTicket();
    if (!ticketId || processed.has(ticketId)) {
      return;
    }

    processed.add(ticketId);
    await updateTicketStatus(ticketId, STATUS_IN_PROGRESS);
    console.log(`Starting pipeline for ticket ${ticketId}`);
    const ticket = await getLinearTicket(ticketId);
    await runPipeline(ticketId, ticket.description);
  } catch (err) {
    console.error("Poll failed:", err);
  } finally {
    pipelineBusy = false;
  }
}

console.log(
  "Listener started — polling Linear for Ready tickets every 5s (one pipeline at a time)"
);

setInterval(() => {
  void poll();
}, POLL_INTERVAL_MS);

void poll();
