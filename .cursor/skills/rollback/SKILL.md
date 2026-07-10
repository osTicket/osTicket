---
name: rollback
description: >-
  Safely reset this demo workspace to a clean baseline: confirm intent, check
  git status, reset, and verify. Use when the user asks to rollback, clean
  baseline, git reset --hard, discard local demo changes, or invokes /rollback.
---

# Rollback — clean baseline

Walk through the **exact, safe** sequence to get back to a clean demo baseline
under pressure. Destructive git is involved — never skip confirmation.

## Hard rules

- **Confirm intent** before `git reset --hard` or any `git clean`
- No `git push --force` / force-push to main
- No `git commit --amend` of shared history as part of rollback
- Never delete or overwrite `.env`
- Never edit fixtures or invent `expected` values to “clean up” parity
- Never blind `git clean -fd` on the whole osTicket tree without an explicit
  path list and a second confirmation

## Workflow

Copy this checklist and tick as you go:

```
Rollback:
- [ ] 1. Confirm intent
- [ ] 2. Inspect status
- [ ] 3. Reset (only after confirm)
- [ ] 4. Verify clean
- [ ] 5. Optional parity check
```

### 1. Confirm intent

If the user has not clearly asked to discard local work, ask once:

> This will discard uncommitted changes (and optionally untracked files under
> agreed paths). Proceed with clean baseline?

Do not run destructive commands until they confirm (or their original message
was already an explicit reset/rollback/clean-baseline request).

### 2. Inspect status

Run in parallel:

```bash
git status
git branch --show-current
git log -3 --oneline
```

Summarize:

- Current branch
- Staged / unstaged tracked changes (especially `orchestrator/`,
  `include/Services/`, `legacy/harness/`, `orchestrator/fixtures/`)
- Untracked files (call out `.env` — leave it alone; call out
  `orchestrator/.state/` as safe runtime cache)

### 3. Reset

**Tracked changes** (after confirm):

```bash
git reset --hard HEAD
```

**Untracked demo junk** — only with explicit path confirmation. Prefer scoped
paths, for example:

```bash
git clean -fd -- orchestrator/.state
```

Only add other paths (e.g. accidental untracked harness/fixture files) when the
user names them. Do **not** `git clean -fd` at repo root by default — this tree
is a full osTicket app with many legitimate untracked/ignored paths.

If they only wanted tracked files reset, stop after `git reset --hard`.

### 4. Verify clean

```bash
git status
```

Report whether the working tree matches expectations. Remind them `.env` and
`node_modules/` / `orchestrator/.state/` may still appear via ignore rules —
that is normal.

### 5. Optional parity check

If they want proof the baseline still passes:

```bash
npx tsx orchestrator/ci-parity-check.ts
# or one ticket:
npx tsx orchestrator/capture-and-verify.ts MOD-<id>
```

Requires Docker/DB as usual; if the environment isn’t up, say so instead of
faking a pass.

## Chat output

Keep the reply short:

1. What you will discard
2. Commands run (or about to run)
3. Post-status result
4. Next step if parity was requested

## Anti-patterns

- Resetting without confirmation on ambiguous asks (“can we clean this up?”)
- Force-push or rewriting remote history
- Deleting `.env` or committed golden fixtures
- Whole-repo `git clean -fd` without path scope + confirm
- Using rollback to “fix” a failed parity gate by changing expecteds
