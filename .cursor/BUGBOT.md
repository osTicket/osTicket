# Bugbot review rules — strangler-fig demo

This repository is a **strangler-fig migration demo** (Cursor SDK orchestrator + osTicket), not an upstream osTicket contribution fork. Review changes against these constraints.

## Scope

- Prefer changes in `orchestrator/`, `include/Services/`, `legacy/harness/`, and `orchestrator/fixtures/`.
- Seam work must stay within the current ticket's manifest (`facadeFile`, `extractionTarget`).
- **Reject** edits to `include/*/vendor/`, mpdf/laminas vendor trees, or unrelated osTicket files unless the seam manifest explicitly names them.
- Do not treat upstream osTicket README or contribution guidance as the source of truth for this repo.

## Parity gate (sacred)

- **Reject** any change that skips, weakens, or bypasses the verifier.
- **Reject** invented or guessed `expected` values in fixtures under `orchestrator/fixtures/`.
- **Reject** fixture edits made solely to make the verifier pass without a real baseline capture.
- **Flag** PRs that would open or move a Linear ticket to **In Review** when parity has not genuinely passed.

## PHP extraction and facade patches

When reviewing `include/Services/**` or facade files (e.g. `include/class.sla.php`):

- **Reject** facade patches that change the entry-point method signature.
- **Reject** patches that drop existing side effects or facade-level orchestration (globals, schedule resolution, caching, caller-visible contracts).
- **Reject** extracted services that call back into the facade entry point they replace (infinite recursion).
- **Reject** changes outside manifest-named files (`extractionTarget`, `facadeFile`).
- **Flag** unrelated PHP refactors bundled with an extraction.

## Harness and fixtures

When reviewing `legacy/harness/**`, `orchestrator/fixtures/**`, or harness-related orchestrator agents:

- **Reject** harness scripts that load individual class files in isolation instead of bootstrapping through `main.inc.php`.
- **Reject** new harnesses when an existing harness already covers the seam.
- **Reject** fixtures without real baseline-captured expected values.

## Orchestrator TypeScript

When reviewing `orchestrator/**/*.ts`:

- **Flag** stage agents that duplicate logic instead of using existing helpers (`withLocalAgent` / `withCloudAgent`, `streamRunWithProgress`, manifest helpers from `orchestrator/lib/manifest.ts`).
- **Reject** new hardcoded MOD-25 paths unless the code already deliberately special-cases MOD-25; new tickets should be manifest-driven.
- **Reject** parallel state locations outside `orchestrator/.state/`.

## Linear / Slack / pipeline

When reviewing `orchestrator/lib/linear.ts`, `orchestrator/lib/slack.ts`, or `orchestrator/pipeline.ts`:

- **Reject** Linear status moves to **In Review** or Slack success notifications that fire before `report.gatePassed === true`.
- **Reject** new comment or Slack payload formats alongside existing helpers (`buildInReviewComment`, `buildParityFailedComment`, `notifyPrOpened`, etc.).
- **Flag** success-path logic on a failed parity gate.
