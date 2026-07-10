# Constraints and pitfalls

## Sacred: parity gate

- Never skip, weaken, or bypass the verifier.
- Never invent `expected` values or edit fixtures just to make a run pass.
- Do not open a PR or move Linear to **In Review** unless `gatePassed === true`.
- On failure: halt; leave ticket in progress; no success Slack.

Harnesses bootstrap through `main.inc.php`. Never load class files in isolation
(breaks `INCLUDE_DIR` / DB context).

Prefer an existing harness when it already covers the seam.

## Sacred: scope

Only touch files the **current ticket's seam manifest** names:

- `extractionTarget`
- `facadeFile`
- (and harness/fixture paths the stage owns)

Do **not** edit:

- `include/*/vendor/`, mpdf/laminas vendor trees
- Unrelated osTicket PHP/UI unless the manifest explicitly names them

## Sacred: strangler quality

- Preserve facade method **signatures** and caller-visible contracts.
- Preserve facade-level orchestration (globals, schedule resolution, caching).
- Move only core logic into the service.
- **Anti-recursion**: service must not call back into the facade entry point.
  Use lifted logic or forward delegation to deeper unrelated code only.

## Orchestrator conventions

- Keep stage agents thin — prompts + I/O; reuse `lib/sdk`, `lib/manifest`, etc.
- Persist runtime state under `orchestrator/.state/` only (gitignored).
- Do not hardcode MOD-25 paths in new code except where
  `lib/manifest.ts` already special-cases MOD-25; new tickets are manifest-driven.
- Linear/Slack: use existing helpers (`buildInReviewComment`, `notifyPrOpened`,
  `updateTicketStatus`, …). Do not invent parallel payload formats.
- Notify / status transitions **only after** a genuine parity pass.

## Cursor rules map

| Rule file | Applies to | Focus |
|-----------|------------|-------|
| `repo-context.mdc` | Always | Demo vs host, parity sacred, leave unrelated trees alone |
| `orchestrator-typescript.mdc` | `orchestrator/**/*.ts` | Thin agents, `.state/`, no new MOD-25 hardcoding |
| `harness-fixtures.mdc` | harness/fixtures/related agents | `main.inc.php`, real expecteds, reuse harnesses |
| `php-extraction-strangler.mdc` | Services + named facades | Smallest patch, anti-recursion, manifest-scoped files |
| `linear-slack-pipeline.mdc` | linear/slack/pipeline | Gate-first notifications |

## Common pitfalls

| Pitfall | Reality |
|---------|---------|
| Reading root `README.md` for architecture | Upstream osTicket only |
| Using root `fixtures/` | Empty/misleading; use `orchestrator/fixtures/` |
| Treating all of `include/` as fair game | Most is host app; prefer `Services/` + manifest paths |
| Guessing fixture expecteds | Must come from baseline capture against legacy behavior |
| "Fixing" parity by changing fixtures | Forbidden — fix extraction/strangler or harness instead |
| Opening PR on failed gate | Forbidden |
| Calling facade from extracted service | Infinite recursion after strangler patch |
| Isolated PHP requires of one class | Harness must use full bootstrap |
| Parallel Linear/Slack message formats | Reuse existing builders |

## Glossary

| Term | Meaning |
|------|---------|
| Seam | Boundary where legacy entry point will delegate to extracted code |
| Facade | Existing public API (e.g. `SLA::addGracePeriod`) kept stable for callers |
| Extraction target | New class under `include/Services/` |
| Harness | CLI PHP script that exercises the seam and prints JSON |
| Golden fixture | Checked-in input + captured `expected` output |
| Parity gate | Verifier requiring all fixtures to match before PR |
| Strangler-fig | Incrementally replace legacy behavior behind a stable facade |
