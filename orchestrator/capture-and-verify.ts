import { baselineCapture } from "./agents/baselineCapture";
import { verifier } from "./agents/verifier";

const ticketId = process.argv[2] || "MOD-25";

async function main() {
  await baselineCapture(ticketId);
  const report = await verifier(ticketId);
  console.log(JSON.stringify(report, null, 2));
  if (!report.gatePassed) process.exit(1);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
