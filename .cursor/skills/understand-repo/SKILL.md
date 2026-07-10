---
name: understand-repo
description: >-
  Onboard to this strangler-fig demo repo: map architecture, pipeline stages,
  key directories, how to run, and sacred constraints. Use when the user asks to
  understand the repo, explain the architecture, onboard as a new engineer,
  dig into how the orchestrator works, or get a tour of the codebase.
---

# Understand this repo

Produce a **new-engineer briefing** for this strangler-fig migration demo
(Cursor SDK orchestrator + osTicket). Discover current state from the tree —
do not invent structure or treat the upstream osTicket README as source of truth.

## Output (always both)

1. **Canvas** — standalone visual briefing the user can open beside chat.
2. **Chat walkthrough** — concise narrative (not a dump of the canvas).

Read the canvas skill before writing the `.canvas.tsx` file. Write the canvas to
the workspace canvases directory (see canvas skill for the exact path). Link it
with a markdown link using the full absolute path.

Do **not** create or update a `REPO_GUIDE.md` unless the user asks for a doc.

## Workflow

Copy this checklist and complete it in order:

```
Repo briefing:
- [ ] 1. Orient from rules + skills
- [ ] 2. Map top-level layout
- [ ] 3. Trace the pipeline
- [ ] 4. Inventory seams / fixtures / state
- [ ] 5. How to run + env
- [ ] 6. Sacred constraints
- [ ] 7. Canvas + chat deliverables
```

### 1. Orient from rules + skills

Read (in parallel when possible):

- `.cursor/rules/repo-context.mdc` and other `.cursor/rules/*.mdc`
- `.cursor/skills/*.md` and this skill's [reference.md](reference.md)
- `orchestrator/pipeline.ts`, `orchestrator/lib/types.ts`

Ignore root `README.md` for demo architecture (upstream osTicket only).

### 2. Map top-level layout

Identify what each major area is for **today**. Prefer these demo surfaces:

| Area | Role |
|------|------|
| `orchestrator/` | Pipeline, agents, SDK, Linear/Slack, fixtures, manifests |
| `include/Services/` | Extracted PHP service classes |
| `legacy/harness/` | Parity capture scripts |
| `orchestrator/fixtures/` | Golden baselines |
| `scripts/` | CI / Docker bootstrap |
| Root PHP / `scp/` / `api/` | Stock osTicket host — not the demo source of truth |

Note empty or misleading dirs (e.g. root `fixtures/` vs `orchestrator/fixtures/`).

### 3. Trace the pipeline

From `orchestrator/pipeline.ts` and `orchestrator/agents/`, document:

- Stage order and what each agent does
- Gate behavior (verifier / `gatePassed`) and what happens on fail vs pass
- Listener entry (`orchestrator/listener.ts`) if present
- Stage runners (`test-stage*.ts`, `capture-and-verify.ts`, `ci-parity-check.ts`)

### 4. Inventory seams / fixtures / state

Skim (do not dump entire files):

- `orchestrator/manifests/` and `orchestrator/.state/` (what is committed vs runtime)
- `orchestrator/fixtures/MOD-*`
- `legacy/harness/*.php`
- `include/Services/` extractions and any facade files named by manifests

Summarize active seams (ticket ids, facade → extraction target) from real files.

### 5. How to run + env

From `package.json`, `docker-compose.yml`, `.github/workflows/`, `scripts/`, and
`.env.example` if present (never print secret values from `.env`):

- Install / Docker bootstrap
- Full pipeline vs single-stage commands
- CI parity path
- Required env var **names** only

### 6. Sacred constraints

Surface explicitly (from rules + code):

- Parity gate is sacred — no skip, weaken, invent expecteds, or edit fixtures to pass
- No PR / Slack / Linear **In Review** unless `gatePassed`
- Do not touch vendor trees or unrelated osTicket files unless the seam manifest names them
- Prefer thin agents + existing `lib/sdk` / `lib/manifest` helpers

### 7. Deliverables

#### Canvas

Create `understand-repo.canvas.tsx` (or refresh it) with live findings embedded.
Suggested composition (adapt to what you found; omit empty sections):

- **Header**: one-line what this repo is
- **Stats**: stage count, active seams, fixture suites, gate status if known
- **Pipeline**: stage flow (Stack/Row/Pills or a simple DAG via `computeDAGLayout`)
- **Directory map**: table of area → purpose (demo vs ignore)
- **How to run**: compact command table
- **Gotchas**: Callout(s) for parity gate and README trap

Follow canvas design rules: theme tokens only, no gradients/emojis/shadows,
mix open sections with cards, no empty placeholder UI.

#### Chat walkthrough

After the canvas, write a short briefing aimed at a new engineer:

1. **What this is** (1–2 sentences)
2. **Mental model** (ticket → manifest → harness/fixtures → extract → strangler → verify → PR)
3. **Where to look first** (3–5 paths)
4. **How to run a slice** (one concrete command)
5. **Do not break these** (parity + scope)
6. **Link to the canvas**

Keep it pointed. Do not restate the entire canvas in prose.

## Depth

Default: **medium** — enough to start contributing to orchestrator or a seam.
If the user asks for "deep dive" / "every file", expand stage-by-stage and list
concrete seam files. If they ask for "quick", canvas + 5-bullet chat only.

## Anti-patterns

- Treating upstream osTicket docs as how this repo works
- Inventing pipeline stages or env vars not in the tree
- Dumping huge file trees or full fixture JSON into chat
- Skipping the canvas
- Writing secrets from `.env` into canvas or chat
