import { execSync } from "child_process";

export function runHarness(
  script: string,
  input: Record<string, unknown>
): string | null {
  const raw = execSync(
    `docker compose exec -T web php ${script} '${JSON.stringify(input)}'`
  ).toString();
  const { output } = JSON.parse(raw) as { output: string | null };
  return output ?? null;
}
