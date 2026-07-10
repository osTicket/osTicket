---
name: onboarding
description: >-
  Onboard engineers to this strangler-fig migration demo (Cursor SDK
  orchestrator + osTicket): architecture, pipeline stages, seams, fixtures,
  how to run, CI, and sacred constraints. Use when the user asks to onboard,
  get a refresher, understand the repo, explain the architecture, tour the
  codebase, or learn how the orchestrator / parity gate works.
---

# Onboarding — strangler-fig demo

Produce a **new-engineer briefing** for this repo. Prefer the knowledge in this
skill and its references; **spot-check the live tree** before stating paths,
stage order, or env vars as fact (they can drift).

Do **not** treat the root `README.md` as demo architecture — it is upstream
osTicket install docs only.

## What this repo is

A **strangler-fig migration demo**: a TypeScript orchestrator drives Cursor SDK
agents that extract logic from legacy osTicket PHP into thin services, patch
facades to delegate, and prove behavioral parity with golden fixtures before
opening a PR.

Mental model:

```
Linear ticket (Ready)
  → cartographer (seam manifest)
  → harness + fixtures + baseline capture
  → extractor (include/Services/)
  → strangler (facade patch)
  → verifier (parity gate)  ← sacred
  → on pass: PR + Slack + Linear In Review
```

## Output (always both)

1. **Canvas** — standalone visual briefing beside chat.
2. **Chat walkthrough** — concise narrative (not a dump of the canvas).

Read the canvas skill before writing the `.canvas.tsx` file. Write it to the
workspace canvases directory (see canvas skill for the exact path). Link it
with a markdown link using the full absolute path.

Name the canvas `onboarding.canvas.tsx` (refresh if it already exists).

Do **not** create or update a `REPO_GUIDE.md` unless the user asks for a doc.

## Progressive disclosure

Read these as needed (one level deep):

| File | When |
|------|------|
| [architecture.md](architecture.md) | Directory map, demo vs host app, key types |
| [pipeline.md](pipeline.md) | Stage-by-stage agents, gate behavior, entry scripts |
| [runbook.md](runbook.md) | Install, env, commands, CI, day-to-day workflows |
| [constraints.md](constraints.md) | Sacred rules, Cursor rules map, common pitfalls |

## Workflow

Copy this checklist and complete it in order:

```
Onboarding briefing:
- [ ] 1. Load skill references + spot-check tree
- [ ] 2. Confirm pipeline + active seams
- [ ] 3. Confirm how-to-run + env names
- [ ] 4. Canvas + chat deliverables
```

### 1. Load references + spot-check

In parallel when possible:

- Read [architecture.md](architecture.md), [pipeline.md](pipeline.md),
  [runbook.md](runbook.md), [constraints.md](constraints.md)
- Spot-check: `orchestrator/pipeline.ts`, `orchestrator/lib/types.ts`,
  `.cursor/rules/*.mdc`, `package.json`, `docker-compose.yml`
- List current: `orchestrator/agents/`, `orchestrator/manifests/`,
  `orchestrator/fixtures/`, `include/Services/`, `legacy/harness/`

### 2. Confirm pipeline + seams

From live files, note:

- Stage order and gate behavior (`gatePassed`)
- Active ticket ids (manifests + fixture dirs)
- Facade → extraction target → harness for each seam

### 3. Confirm run + env

From `package.json`, `docker-compose.yml`, `.github/workflows/`, `scripts/`,
and `.env` **keys only** (never print secret values):

- Bootstrap / Docker / full pipeline / single-stage / CI commands
- Required env var **names**

### 4. Deliverables

#### Canvas

Suggested composition (omit empty sections; embed live findings):

- **Header**: one-line what this repo is
- **Stats**: stage count, active seams, fixture suites
- **Pipeline**: stage flow
- **Directory map**: demo surfaces vs ignore/host
- **Active seams**: ticket → facade → service → harness
- **How to run**: compact command table
- **Gotchas**: parity gate + README trap

Follow canvas design rules: theme tokens only, no gradients/emojis/shadows,
mix open sections with cards, no empty placeholder UI.

#### Chat walkthrough

After the canvas, brief a new engineer:

1. **What this is** (1–2 sentences)
2. **Mental model** (ticket → verify → PR)
3. **Where to look first** (3–5 paths)
4. **How to run a slice** (one concrete command)
5. **Do not break these** (parity + scope)
6. **Link to the canvas**

Keep it pointed. Do not restate the entire canvas in prose.

## Depth

| Mode | When | Deliver |
|------|------|---------|
| **quick** | "quick tour" / "refresher" | Canvas + 5-bullet chat |
| **medium** (default) | onboard / understand repo | Full canvas + structured chat |
| **deep** | "deep dive" / "every stage" | Expand stage-by-stage + list concrete seam files |

## Anti-patterns

- Treating upstream osTicket docs as how this repo works
- Inventing pipeline stages, seams, or env vars not in the tree
- Dumping huge file trees or full fixture JSON into chat
- Skipping the canvas
- Writing secrets from `.env` into canvas or chat
- Editing fixtures or inventing `expected` values to "explain" a pass
