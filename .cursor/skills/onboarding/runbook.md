# Runbook

Confirm commands and env names against the tree. Never print secret values.

## Hands-off production path (preferred)

Prod trigger is the Cursor Automation **Linear Ready -> Strangler Pipeline**
(Linear status change in Modernize / SLA Modernization). It runs on a **Cursor
cloud VM** built from [`.cursor/environment.json`](../../environment.json) +
[`.cursor/Dockerfile`](../../Dockerfile).

Flow:

1. Move one or more `MOD-*` tickets to **Ready** (2–3 concurrent runs are supported)
2. Each Automation sets its ticket **In Progress** and runs
   `npx tsx orchestrator/pipeline.ts <TICKET> --criteria "<description>"`
3. Pipeline checks out `strangler/<TICKET>` from `GITHUB_DEMO_BRANCH` and pushes early
4. Cloud `start` script brings up Compose + bootstrap so baseline/verifier work
5. On parity pass: commit/push artifacts on the ticket branch → PR → Slack → Linear **In Review**
6. On fail: Linear failure comment; ticket stays **In Progress**; no PR

### Multi-ticket concurrency

Each Ready ticket gets its own cloud VM and `strangler/MOD-*` branch. The base
branch (`GITHUB_DEMO_BRANCH`, e.g. `demo/sla-strangler`) is the PR target only —
the orchestrator does not push to it.

Move several tickets to Ready at once; each finishes independently with its own
PR. If two tickets touch the same facade file, merge their PRs one at a time and
rebase the second onto the updated base.

GitHub prerequisites: see [`.github/MULTI_TICKET_SETUP.md`](../../.github/MULTI_TICKET_SETUP.md).

You do **not** need `listener.ts`, local Docker, or a laptop left on for this
path. Mirror secrets from local `.env` into the Cloud Agents / Automation
environment (names only in docs — never commit values).

**If you previously saved an interactive cloud snapshot for this repo**, delete
it in the Cloud Agents dashboard so Dockerfile-based builds are used.

### Cloud environment files

| Path | Role |
|------|------|
| `.cursor/environment.json` | `build` / `install` / `start` for cloud agents |
| `.cursor/Dockerfile` | Node 22 + Docker CE / Compose (DinD) |
| `.cursor/install.sh` | `npm ci` |
| `.cursor/start.sh` | Start Docker daemon + `scripts/ci-docker-bootstrap.sh` |

Install `PIPELINE_PAUSE_FOR_REVIEW=1` only if you want the old demo pause
log lines; hands-off default proceeds straight to the PR agent after a pass.

## Local optional (debug / UI)

- Node.js 22+; `npm ci` / `npm install`
- Docker + Docker Compose if you want parity **on this machine** or the product UI
- Port `:8080` is **optional product UI** — not required for hands-off cloud runs
- PHP 8.4 + `mysqli` only if you run harness outside Compose (CI installs it; cloud uses container PHP)

## Environment variables

Document **names only**. Typical keys in `.env` (gitignored) — mirror the same
names into Cloud Agent secrets:

| Name | Used for |
|------|----------|
| `CURSOR_API_KEY` | Cursor SDK agents |
| `GITHUB_REPO_URL` | Cloud agent repo URL |
| `GITHUB_DEMO_BRANCH` | **Required** — PR base branch; per-ticket work branches are `strangler/MOD-*` |
| `LINEAR_API_KEY` | Ticket status / comments |
| `SLACK_WEBHOOK_URL` | PR-opened notification (optional; warns and skips if unset) |
| `LEGACY_APP_URL` | Legacy app URL for local demo context |
| `PIPELINE_PAUSE_FOR_REVIEW` | Set to `1` for demo pause log only (default hands-off skips) |

Load via `dotenv/config` in pipeline/listener entrypoints.

## Local Docker bootstrap (optional)

```bash
docker compose up -d
# db: MySQL 8 on 3306 (root/osticket, db osticket)
# web: PHP 8.4 Apache on 8080, repo mounted at /var/www/html
```

CI and cloud `start` use `scripts/ci-docker-bootstrap.sh` to wait for MySQL with
an authenticated `SELECT 1` check, import schema if needed, and create
`include/ost-config.php` when missing.

`include/ost-config.php` is gitignored — local/CI/cloud generate it; do not
commit secrets.

## Day-to-day commands

### First-time local setup (optional)

```bash
npm ci
# Create .env with required keys (see table above; file is gitignored)
docker compose up -d
bash scripts/ci-docker-bootstrap.sh
```

### Run the full migration pipeline for a ticket (local debug)

```bash
npx tsx orchestrator/pipeline.ts MOD-26 --criteria "Extract SLA::priorityEscalation into a service"
```

Resume from a later stage (reuse earlier artifacts):

```bash
npx tsx orchestrator/pipeline.ts MOD-26 --criteria "..." --from-stage 3
```

### Deprecated: local Linear poller

Prefer the Cursor Automation. Keep only for offline debug:

```bash
npx tsx orchestrator/listener.ts
```

Do **not** run the listener while the Automation is Active — double starts.
The listener processes one pipeline at a time in a single checkout; use
Automation for concurrent Ready tickets.

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
| Publish before PR | `orchestrator/lib/gitPublish.ts` (per-ticket branch + scoped paths) |
| Multi-ticket GitHub setup | `.github/MULTI_TICKET_SETUP.md` |
| Fixtures | `orchestrator/fixtures/MOD-*/` |
| Extracted services | `include/Services/` |
| Facade patches | Paths in manifest `facadeFile` (often `include/class.sla.php`) |
| Linear / Slack | `orchestrator/lib/linear.ts`, `slack.ts` |
| Cloud VM env | `.cursor/environment.json`, `.cursor/Dockerfile`, `.cursor/start.sh` |
| Process rules | `.cursor/rules/*.mdc` |

## Suggested first week for a new engineer

1. Read `.cursor/rules/repo-context.mdc` and this skill's references.
2. Skim `orchestrator/pipeline.ts` and one agent (e.g. `verifier.ts`).
3. Open one manifest + its fixture dir + harness script; run
   `capture-and-verify` for that ticket (local Docker optional).
4. Trace one extraction: service in `include/Services/` ↔ facade method.
5. Run `ci-parity-check.ts` locally with Docker up, or rely on CI.
6. Only then change orchestrator or add a new seam — manifest-first.

## Package notes

- `package.json` name: `osticket-strangler-demo`
- Dependencies: `@cursor/sdk`, `dotenv`, `tsx`, `typescript`, MCP SDK
- No meaningful `npm test` script yet — parity is the real gate
