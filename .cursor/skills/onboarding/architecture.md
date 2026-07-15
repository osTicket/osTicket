# Architecture

Spot-check the tree before treating paths as absolute truth.

## Dual nature of the tree

This repository is **osTicket (PHP host app) + a demo overlay**. Most root PHP,
`scp/`, `api/`, `include/class.*.php`, etc. are stock osTicket. The demo lives
in a small set of surfaces.

| Area | Role | Demo relevance |
|------|------|----------------|
| `orchestrator/` | Pipeline, agents, SDK wrappers, Linear/Slack, fixtures, manifests | **Primary** |
| `include/Services/` | Extracted PHP service classes | **Primary** |
| `legacy/harness/` | Parity capture scripts (CLI tools, not product) | **Primary** |
| `legacy/seed/` | Seed/helpers for schedules / empty-schedule testing | Supporting |
| `orchestrator/fixtures/` | Golden baselines per ticket (`MOD-*`) | **Primary** |
| `orchestrator/manifests/` | Committed seam manifests | **Primary** |
| `scripts/` | CI / Docker bootstrap | Supporting |
| `.cursor/rules/` | Process gates and file-ownership rules | **Primary** |
| `.cursor/skills/` | Agent skills (this onboarding skill, etc.) | Supporting |
| `.github/workflows/` | Parity CI | Supporting |
| Root PHP / `scp/` / `api/` / `setup/` | Stock osTicket | Host only — not demo SoT |
| `include/*/vendor/`, mpdf, laminas | Third-party | **Do not touch** unless manifest names them |
| Root `fixtures/` | Empty / misleading | Prefer `orchestrator/fixtures/` |
| Root `README.md` | Upstream osTicket install | **Not** demo architecture |

## Source of truth (priority order)

1. `.cursor/rules/*.mdc` — process, gates, ownership
2. `orchestrator/agents/*.ts` — stage prompts and I/O
3. `orchestrator/pipeline.ts` + `orchestrator/lib/types.ts` — stage order + schemas
4. Real manifests / fixtures / harnesses under `orchestrator/` and `legacy/`

## Orchestrator layout

```
orchestrator/
├── pipeline.ts          # Full stage runner (CLI / Automation entry)
├── listener.ts          # Deprecated local Linear poller (prefer Automation)
├── capture-and-verify.ts
├── ci-parity-check.ts   # CI: all fixture suites
├── test-stage1.ts … test-stage3.ts
├── agents/              # Thin stage wrappers
│   ├── cartographer.ts
│   ├── harnessBuilder.ts
│   ├── fixtureGenerator.ts
│   ├── baselineCapture.ts
│   ├── extractor.ts
│   ├── strangler.ts
│   ├── verifier.ts
│   └── prAgent.ts
├── lib/
│   ├── types.ts         # SeamManifest, Fixture, ParityReport
│   ├── sdk.ts           # withLocalAgent / withCloudAgent, streaming
│   ├── manifest.ts      # load/require facade, harness, extraction target
│   ├── harness.ts       # Run PHP harness scripts
│   ├── gitPublish.ts    # Commit/push artifacts before nested cloud PR
│   ├── linear.ts        # Ticket status / comments
│   ├── slack.ts         # PR-opened notify
│   └── terminal.ts      # Pipeline/agent log helpers
├── manifests/           # Committed MOD-*-manifest.json
├── fixtures/            # MOD-*/**.json golden cases + per-ticket parity.json
└── .state/              # Runtime cache (gitignored)
```

Cloud Automation environment (repo root `.cursor/`): `environment.json`, `Dockerfile`,
`install.sh`, `start.sh` — Compose + bootstrap for hands-off parity.

## Key types (`orchestrator/lib/types.ts`)

**SeamManifest** — cartographer output; drives later stages:

- `ticketId`, `entryPoint`, `coreLogic`, `consumers`
- `inputShape`, `outputShape`, `sideEffects`, `constraints`
- `facadeFile` — PHP file to patch in strangler stage
- `extractionTarget` — new service under `include/Services/`
- `harnessScript` — path under `legacy/harness/`
- `harnessInputShape` — what the harness JSON payload looks like

**Fixture** — one parity case:

- Prefer generic `input` + `expected`
- Legacy SLA fields (`graceHours`, `start`, `scheduleId`) still supported for MOD-25

**ParityReport** — verifier output:

- `passed` / `failed` / `totalCases`, `mismatches[]`, **`gatePassed`**

## Strangler-fig pattern (in this demo)

1. **Cartograph** the seam (no code changes).
2. **Capture** real legacy behavior into fixtures via a harness.
3. **Extract** core logic into `include/Services/<Name>.php`.
4. **Strangle** the facade: keep the public method signature; delegate to the service.
5. **Verify** harness output still matches fixture `expected` values.
6. Only then open a PR and notify humans.

Anti-recursion: the service must never call back into the facade entry point it
replaced.

## Active seams (verify in tree)

Typical committed examples (confirm `orchestrator/manifests/` and
`include/Services/`):

| Ticket | Facade | Service | Harness |
|--------|--------|---------|---------|
| MOD-25 | `include/class.sla.php` (`addGracePeriod`) | `SlaGracePeriodCalculator.php` | `legacy/harness/sla_capture.php` |
| MOD-26 | `include/class.sla.php` (`priorityEscalation`) | `SlaPriorityEscalationResolver.php` | `legacy/harness/priority_escalation_capture.php` |

MOD-25 has deliberate special-casing in `orchestrator/lib/manifest.ts` for its
proven harness path. New tickets should be **manifest-driven**, not hardcoded.

## Integrations

| System | Role |
|--------|------|
| Cursor SDK (`@cursor/sdk`) | Local/cloud agents for cartography, codegen, PR |
| Linear | Ticket Ready → In Progress → In Review |
| Slack | Notify only after parity pass + PR |
| GitHub Actions | Golden fixture parity on PRs |
| Docker Compose | MySQL + PHP Apache for local/CI app runtime |
