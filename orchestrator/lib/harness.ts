import { execSync } from "child_process";

export interface HarnessInput {
  start: string;
  hours: number;
  schedule_id: number;
}

export function runHarness(input: HarnessInput): string | null {
  const raw = execSync(
    `docker compose exec -T web php legacy/harness/sla_capture.php '${JSON.stringify(input)}'`
  ).toString();
  const { output } = JSON.parse(raw) as { output: string | null };
  return output ?? null;
}
