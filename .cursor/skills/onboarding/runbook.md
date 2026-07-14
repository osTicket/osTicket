# Runbook

Confirm commands and env names against the tree. Never print secret values.

## Prerequisites

- Node.js 22+ (CI uses 22); `npm ci` / `npm install`
- Docker + Docker Compose (MySQL 8 + PHP 8.4 Apache)
- PHP 8.4 with `mysqli` for harness runs outside the web container (as in CI)
- Cursor API key and (for full pipeline) Linear / Slack / GitHub settings

## Environment variables

Document **names only**. Typical keys in `.env` (gitignored):

| Name | Used for |
|------|----------|
| `CURSOR_API_KEY` | Cursor SDK agents |
| `GITHUB_REPO_URL` | Cloud agent repo URL |
| `GITHUB_DEMO_BRANCH` | Optional fallback if `git rev-parse` fails; cloud `startingRef` normally comes from the current checkout |
| `LINEAR_API_KEY` | Ticket poll / status / comments |
| `SLACK_WEBHOOK_URL` | PR-opened notification (optional; warns and skips if unset) |
| `LEGACY_APP_URL` | Legacy app URL for local demo context |

Load via `dotenv/config` in pipeline/listener entrypoints.

## Local Docker bootstrap

```bash
docker compose up -d
# db: MySQL 8 on 3306 (root/osticket, db osticket)
# web: PHP 8.4 Apache on 8080, repo mounted at /var/www/html
```

CI uses `scripts/ci-docker-bootstrap.sh` to wait for MySQL with an authenticated
`SELECT 1` check (not just `mysqladmin ping`), import schema if needed, and
create `include/ost-config.php` when missing.

`include/ost-config.php` is gitignored — local/CI generate it; do not commit secrets.

## Day-to-day commands

### First-time setup

```bash
npm ci
# Create .env with required keys (see table above; file is gitignored)
docker compose up -d
bash scripts/ci-docker-bootstrap.sh   # optional; useful to mirror CI DB/config
```
### Run the full migration pipeline for a ticket

```bash
npx tsx orchestrator/pipeline.ts MOD-26 --criteria "Extract SLA::priorityEscalation into a service"
```

Resume from a later stage (reuse earlier artifacts):

```bash
npx tsx orchestrator/pipeline.ts MOD-26 --criteria "..." --from-stage 3
```

### Auto-pick work from Linear

```bash
npx tsx orchestrator/listener.ts
```

### Re-capture baselines and verify one seam

```bash
npx tsx orchestrator/capture-and-verify.ts MOD-25
```

### Run what CI runs

```bash
npx tsx orchestrator/ci-parity-check.ts
```

## CI

Workflow: `.github/workflows/parity-check.yml`

- Triggers: PRs to `demo/sla-strangler` or `develop`; `workflow_dispatch`
- Steps: checkout → Node 22 → PHP 8.4 + mysqli → `npm ci` →
  `scripts/ci-docker-bootstrap.sh` → `npx tsx orchestrator/ci-parity-check.ts`

Parity failure fails the job. Do not weaken the check.

## Where to change what

| Goal | Look here |
|------|-----------|
| Pipeline order / gate | `orchestrator/pipeline.ts` |
| Stage behavior | `orchestrator/agents/<stage>.ts` |
| Manifest schema | `orchestrator/lib/types.ts` |
| Manifest I/O helpers | `orchestrator/lib/manifest.ts` |
| SDK agent wiring | `orchestrator/lib/sdk.ts` |
| Harness execution | `orchestrator/lib/harness.ts`, `legacy/harness/*.php` |
| Fixtures | `orchestrator/fixtures/MOD-*/` |
| Extracted services | `include/Services/` |
| Facade patches | Paths in manifest `facadeFile` (often `include/class.sla.php`) |
| Linear / Slack | `orchestrator/lib/linear.ts`, `slack.ts` |
| Process rules | `.cursor/rules/*.mdc` |

## Suggested first week for a new engineer

1. Read `.cursor/rules/repo-context.mdc` and this skill's references.
2. Skim `orchestrator/pipeline.ts` and one agent (e.g. `verifier.ts`).
3. Open one manifest + its fixture dir + harness script; run
   `capture-and-verify` for that ticket.
4. Trace one extraction: service in `include/Services/` ↔ facade method.
5. Run `ci-parity-check.ts` locally with Docker up.
6. Only then change orchestrator or add a new seam — manifest-first.

## Package notes

- `package.json` name: `osticket-strangler-demo`
- Dependencies: `@cursor/sdk`, `dotenv`, `tsx`, `typescript`, MCP SDK
- No meaningful `npm test` script yet — parity is the real gate
