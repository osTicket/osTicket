# Pipeline

Confirm stage order against `orchestrator/pipeline.ts` before briefing.

## Stage order

| # | Stage | Agent file | Runtime | Purpose |
|---|-------|------------|---------|---------|
| 1 | cartographer | `agents/cartographer.ts` | Cloud | Investigate legacy PHP; write seam manifest JSON (no implementation) |
| 2a | harnessBuilder | `agents/harnessBuilder.ts` | Local | Create/reuse `legacy/harness/<seam>_capture.php` |
| 2b | fixtureGenerator | `agents/fixtureGenerator.ts` | Cloud | Propose fixture cases under `orchestrator/fixtures/<ticket>/` |
| 2c | baselineCapture | `agents/baselineCapture.ts` | Local (harness) | Run harness; fill real `expected` values |
| 3 | extractor | `agents/extractor.ts` | Local | Create thin service at `extractionTarget` |
| 4 | strangler | `agents/strangler.ts` | Local | Smallest facade patch at `facadeFile` |
| 5 | verifier | `agents/verifier.ts` | Local (harness) | Compare harness output to fixture expecteds → `ParityReport` |
| 6 | prAgent | `agents/prAgent.ts` | Cloud | Open PR **only if** `gatePassed` |

`fromStage` in `runPipeline(ticketId, criteria, fromStage)` skips earlier work
(e.g. `--from-stage 3` reuses harness/fixtures/baseline).

Cartographer can load a cached manifest from `orchestrator/.state/` when
`--from-stage` / `--from-cache` implies reuse.

## Gate behavior (sacred)

After verifier:

**Pass** (`report.gatePassed === true`):

1. Log pause for human review (demo notes production would use `Agent.resume`)
2. `prAgent` opens PR
3. Slack `notifyPrOpened`
4. Linear comment + status → **In Review**

**Fail**:

- Halt pipeline
- Leave ticket **In Progress**
- **No** PR, **no** Slack success notify, **no** In Review
- Print mismatches (`expected` vs `actual`)

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
- Scoped to facade + extraction target from manifest

## Entry points

```bash
# Full pipeline for a ticket
npx tsx orchestrator/pipeline.ts MOD-<id> --criteria "<acceptance text>" [--from-stage N]

# Linear listener (poll Ready every 5s → In Progress → runPipeline)
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

`orchestrator/listener.ts`:

1. Poll Linear for a Ready ticket
2. Mark In Progress
3. `runPipeline(ticketId, ticket.description)`
4. Deduplicate with an in-memory `processed` set

## Manifest + state locations

| Path | Role |
|------|------|
| `orchestrator/manifests/MOD-*-manifest.json` | Committed source of truth for known seams |
| `orchestrator/.state/MOD-*-manifest.json` | Runtime cache (gitignored); cartographer/CI may copy committed → state |
| `orchestrator/fixtures/MOD-*/` | One JSON file per case |
| `orchestrator/fixtures/parity.json` | Aggregate parity artifact if present |

`ci-parity-check.ts` discovers ticket ids from fixture directories and ensures
state manifests exist (copying from `orchestrator/manifests/` when needed).

## Libraries to reuse (do not reinvent)

- `withLocalAgent` / `withCloudAgent`, streaming helpers — `lib/sdk.ts`
- `loadManifest`, `requireFacadeFile`, `requireExtractionTarget`, `requireHarnessScript`, `fixtureHarnessInput` — `lib/manifest.ts`
- `runHarness` — `lib/harness.ts`
- Linear/Slack helpers — `lib/linear.ts`, `lib/slack.ts`
- Keep agents **thin**: prompts + I/O only
