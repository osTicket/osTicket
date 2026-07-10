# Repo landmarks (verify, don't assume)

Use this as a discovery checklist. Confirm paths and behavior against the
current tree before stating them as fact.

## Source of truth (in priority order)

1. `.cursor/rules/*.mdc` — process, gates, file ownership
2. `.cursor/skills/*.md` — stage agent behavior (cartographer, extractor, strangler)
3. `orchestrator/pipeline.ts` + `orchestrator/lib/types.ts` — stage order + seam schema
4. Real manifests / fixtures / harness scripts under `orchestrator/` and `legacy/`

Root `README.md` = upstream osTicket install docs. Not demo architecture.

## Pipeline stages (typical)

Confirm against `orchestrator/pipeline.ts`:

1. **cartographer** — seam manifest JSON (no implementation)
2. **harnessBuilder** + **fixtureGenerator** + **baselineCapture** — parity inputs
3. **extractor** — thin service under `include/Services/`
4. **strangler** — smallest facade patch
5. **verifier** — parity gate (`gatePassed`)
6. On pass: **prAgent** + Slack notify + Linear **In Review**

On fail: halt; leave ticket in progress; no PR / Slack success / In Review.

## Useful entry commands

Confirm flags in the scripts themselves:

```bash
npx tsx orchestrator/pipeline.ts MOD-<id> --criteria "<text>" [--from-stage N]
npx tsx orchestrator/test-stage1.ts
npx tsx orchestrator/capture-and-verify.ts [MOD-id]
npx tsx orchestrator/ci-parity-check.ts
```

Listener (if present): `orchestrator/listener.ts` polls Linear Ready tickets.

## Key libraries

- `orchestrator/lib/sdk.ts` — `withLocalAgent` / `withCloudAgent`, streaming
- `orchestrator/lib/manifest.ts` — load/require facade + extraction target
- `orchestrator/lib/linear.ts` / `slack.ts` — notify only after gate pass
- `orchestrator/lib/types.ts` — `SeamManifest`, `ParityReport`

## State locations

| Path | Usually |
|------|---------|
| `orchestrator/manifests/` | Committed seam manifests |
| `orchestrator/.state/` | Runtime cache (often gitignored) |
| `orchestrator/fixtures/MOD-*` | Golden baselines |
| `legacy/harness/*.php` | Capture scripts (bootstrap via `main.inc.php`) |

## Env var names to look for

Document names only, never values: `CURSOR_API_KEY`, `LINEAR_API_KEY`,
`SLACK_WEBHOOK_URL`, `GITHUB_REPO_URL`, `GITHUB_DEMO_BRANCH`, `LEGACY_APP_URL`.

## CI

`.github/workflows/` + `scripts/ci-docker-bootstrap.sh` → typically
`npx tsx orchestrator/ci-parity-check.ts`.

## Out of scope unless manifest names them

`include/*/vendor/`, mpdf/laminas vendor trees, unrelated osTicket PHP/UI.
