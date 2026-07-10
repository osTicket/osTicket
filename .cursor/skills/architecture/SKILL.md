---
name: architecture
description: >-
  Live walkthrough of this strangler-fig demo: pipeline stages, manifest-driven
  design, and the parity gate, grounded in the current tree. Use when the user
  asks for architecture, explain the system, walk through the pipeline, a demo
  beat of how the orchestrator works, or invokes /architecture.
---

# Architecture — live system walkthrough

Produce a **chat-first** explanation of how this demo works, suitable for a
live interview or demo (“let the agent explain the system it’s part of”).

Overlap with `/onboarding` is fine. This skill is the **snappy live beat**;
onboarding is the **full engineer briefing** (canvas + deep refs). If the user
wants the full canvas onboarding pack, tell them to use `/onboarding`.

Do **not** treat root `README.md` as demo architecture (upstream osTicket only).

## Output

Default: **chat narrative only** (no required canvas). Keep it pointed and
demo-ready. Only create a canvas if the user asks for one.

## Workflow

```
Architecture walkthrough:
- [ ] 1. Spot-check the live tree
- [ ] 2. Load shared refs if helpful
- [ ] 3. Deliver the narrative
```

### 1. Spot-check (required)

In parallel when possible:

- `orchestrator/pipeline.ts` — stage order and gate behavior
- `orchestrator/agents/` — which stages exist
- `orchestrator/lib/types.ts` — `SeamManifest`, `ParityReport`
- `orchestrator/manifests/`, `orchestrator/fixtures/`, `include/Services/`,
  `legacy/harness/` — active seams

Confirm stage count and names from code. Do not invent stages.

### 2. Shared refs (optional)

Reuse, do not fork:

- [../onboarding/architecture.md](../onboarding/architecture.md)
- [../onboarding/pipeline.md](../onboarding/pipeline.md)
- [../onboarding/constraints.md](../onboarding/constraints.md)

### 3. Narrative (default shape)

Deliver in this order:

1. **What this is** (1–2 sentences) — Cursor SDK orchestrator + osTicket strangler-fig demo
2. **Manifest-driven design** — cartographer writes a seam manifest; later stages read `facadeFile`, `extractionTarget`, `harnessScript`
3. **Pipeline** — stage flow from live `pipeline.ts` (ticket → harness/fixtures/baseline → extract → strangler → verifier → PR only on pass)
4. **Parity gate** — sacred; no PR / Slack success / Linear In Review unless `gatePassed`
5. **Where it lives** — 3–5 real paths from the tree you just checked

Depth:

| Mode | Deliver |
|------|---------|
| **default** | Crisp demo walkthrough (~1 screen of chat) |
| **deep** | Stage-by-stage + concrete seam files (manifest ↔ service ↔ harness) |

## Anti-patterns

- Requiring a canvas (that’s `/onboarding`)
- Freezing “five stages” if the tree shows a different shape
- Inventing seams or env values
- Dumping full manifest JSON
- Writing secrets from `.env`
