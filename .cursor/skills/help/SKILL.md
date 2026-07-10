---
name: help
description: >-
  Discovery index for this repo's Cursor skills: list what is available and
  route to the right one. Use when the user asks for help, what skills exist,
  what they can do in this repo, which skill to use, or invokes /help.
---

# Help — skill discovery index

Act as the **catalog and router** for project skills in this repo. Do not give
a full architecture briefing here — point at the right skill instead.

## Workflow

Copy this checklist:

```
Help:
- [ ] 1. Discover skills from the tree
- [ ] 2. Present the catalog
- [ ] 3. Route if the user stated a goal
```

### 1. Discover (always live)

Scan `.cursor/skills/*/SKILL.md` (project skills only for this catalog).

For each skill, read YAML frontmatter:

- `name` (must match folder name)
- `description` (one-line purpose + when)

Do **not** hardcode the skill list. Discovery keeps `/help` current when skills
are added or renamed. If the scan fails, say so and list whatever you found.

Skip nested reference files (`architecture.md`, etc.) — only folders that
contain `SKILL.md` are skills.

### 2. Present the catalog

Output a compact list. For each skill:

- **`/name`** — short purpose (paraphrase from `description`, one line)
- When to use it (pull trigger phrases from the description)

Keep it scannable. No essays.

### 3. Route

If the user stated a goal (or asked “which should I use?”), recommend **one**
primary skill and say how to invoke it (`/name` or `@name`).

Routing hints (confirm against discovered descriptions):

| User wants… | Prefer |
|-------------|--------|
| Full new-engineer briefing, canvas tour, refresher | `/onboarding` |
| Live demo walkthrough of pipeline / parity / manifests | `/architecture` |
| Clean baseline, git reset, discard local demo mess | `/rollback` |
| Just the skill list | Stay on `/help` (catalog only) |

If they already attached a skill, follow that skill instead of only linking it.

## Anti-patterns

- Dumping onboarding or architecture content into the help reply
- Inventing skills that are not on disk
- Listing personal/`~/.cursor/skills-cursor` built-ins as if they were this repo’s
- Writing secrets or running destructive commands
