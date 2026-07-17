# Pipeline

Confirm stage order against `orchestrator/pipeline.ts` before briefing.

## Stage order

| # | Stage | Agent file | Runtime | Purpose |
|---|-------|------------|---------|---------|
| 1 | cartographer | `agents/cartographer.ts` | Cloud | Investigate legacy PHP; write seam manifest JSON (no implementation) |
| 2a | harnessBuilder | `agents/harnessBuilder.ts` | Local | Create/reuse `legacy/harness/<seam>_capture.php` |
| 2b | fixtureGenerator | `agents/fixtureGenerator.ts` | Local | Propose fixture cases under `orchestrator/fixtures/<ticket>/` |
| 2c | baselineCapture | `agents/baselineCapture.ts` | Local (harness) | Run harness; fill real `expected` values |
| 3 | extractor | `agents/extractor.ts` | Local | Create thin service at `extractionTarget` |
| 4 | strangler | `agents/strangler.ts` | Local | Smallest facade patch at `facadeFile` |
| 5 | verifier | `agents/verifier.ts` | Local (harness) | Compare harness output to fixture expecteds → `ParityReport` |
| 6 | prAgent | `agents/prAgent.ts` | Local (`gh`) | Open PR **only if** `gatePassed` |

`fromStage` in `runPipeline(ticketId, criteria, fromStage)` skips earlier work
(e.g. `--from-stage 3` reuses harness/fixtures/baseline).

Cartographer can load a cached manifest from `orchestrator/.state/` when
`--from-stage` / `--from-cache` implies reuse.

## Gate behavior (sacred)

After verifier:

**Pass** (`report.gatePassed === true`):

1. Hands-off by default: checkout `strangler/<ticket>` from `GITHUB_DEMO_BRANCH`,
   publish scoped artifacts on that branch, then open PR with `gh`
   (set `PIPELINE_PAUSE_FOR_REVIEW=1` only for legacy demo pause **logs**)
2. `prAgent` opens PR against the base branch (`--base` / `--head` explicit)
3. Slack `notifyPrOpened`
4. Linear comment + status → **In Review**

**Fail**:

- Halt pipeline
- Leave ticket **In Progress**
- **No** PR, **no** Slack success notify, **no** In Review
- Print mismatches (`expected` vs `actual`)
- Linear comment via `buildParityFailedComment`

Never skip, weaken, or bypass the verifier. Never invent expecteds or edit
fixtures just to pass.

## What each stage must / must not do

### Cartographer

- Trace real call sites by reading files
- Emit one JSON object matching `SeamManifest`
- Set `facadeFile`, `extractionTarget`, `harnessScript`, `harnessInputShape`
- **No** implementation code

### Harness builder

- Bootstrap via `main.inc.php` (never isolated class includes)
- Prefer reusing an existing harness if it covers the seam
- Scripts are tools, not product code

### Fixture generator + baseline capture

- Fixtures need **real** `expected` values from running legacy code
- Baseline capture writes those values; humans/agents must not guess them

### Extractor

- Thin service under `include/Services/`
- Lifted logic or forward delegation only — never call the facade entry point

### Strangler

- Preserve method signature and facade-level side effects / orchestration
- Smallest possible patch; only files named in the manifest

### Verifier

- Runs harness per fixture; builds `ParityReport`
- Sole authority for `gatePassed`

### PR agent

- Runs only after gate pass
- Uses `gh pr create --base <GITHUB_DEMO_BRANCH> --head strangler/<ticket>`
- Scoped to facade + extraction target + ticket fixtures from manifest

## Multi-ticket Ready

Move 2–3 tickets to **Ready** concurrently (Cursor Automation). Each run:

1. Creates or resumes `strangler/MOD-*` from the base branch
2. Publishes only that ticket's paths
3. Opens its own PR

Do not run `listener.ts` alongside Automation. Overlapping facade PRs: merge one
at a time and rebase the rest. See `.github/MULTI_TICKET_SETUP.md`.

## Entry points

```bash
# Prod: Cursor Automation on Linear Ready (cloud VM + Compose) — no listener

# Full pipeline for a ticket (local debug or cloud agent shell)
npx tsx orchestrator/pipeline.ts MOD-<id> --criteria "<acceptance text>" [--from-stage N]

# Deprecated local Linear poller (do not run alongside the Automation)
npx tsx orchestrator/listener.ts

# Capture baselines then verify one ticket
npx tsx orchestrator/capture-and-verify.ts [MOD-id]

# CI: verify every orchestrator/fixtures/MOD-* suite
npx tsx orchestrator/ci-parity-check.ts

# Stage smoke scripts (confirm flags in file)
npx tsx orchestrator/test-stage1.ts
npx tsx orchestrator/test-stage2.ts
npx tsx orchestrator/test-stage3.ts
```

## Listener flow

`orchestrator/listener.ts` is a **legacy local poller**. Prefer the Cursor
Automation (Linear status → Ready). If you use the listener for debug:

1. Poll Linear for a Ready ticket
2. Mark In Progress
3. `runPipeline(ticketId, ticket.description)`
4. Deduplicate with an in-memory `processed` set
5. **One pipeline at a time** — `pipelineBusy` gate prevents overlapping polls

Do not run listener + Automation at the same time. Use Automation for concurrent Ready tickets.

## Manifest + state locations

| Path | Role |
|------|------|
| `orchestrator/manifests/MOD-*-manifest.json` | Committed source of truth for known seams |
| `orchestrator/.state/MOD-*-manifest.json` | Runtime cache (gitignored); cartographer/CI may copy committed → state |
| `orchestrator/fixtures/MOD-*/` | One JSON file per case |
| `orchestrator/fixtures/MOD-*/parity.json` | Per-ticket parity report from verifier |

`ci-parity-check.ts` discovers ticket ids from fixture directories and ensures
state manifests exist (copying from `orchestrator/manifests/` when needed).

## Libraries to reuse (do not reinvent)

- `withLocalAgent` / `withCloudAgent`, streaming helpers — `lib/sdk.ts`
- `loadManifest`, `requireFacadeFile`, `requireExtractionTarget`, `requireHarnessScript`, `fixtureHarnessInput` — `lib/manifest.ts`
- `runHarness` — `lib/harness.ts`
- `publishArtifactsForPr`, `ensureStranglerBranch`, `openPullRequest` — `lib/gitPublish.ts`
- Linear/Slack helpers — `lib/linear.ts`, `lib/slack.ts`
- Keep agents **thin**: prompts + I/O only
