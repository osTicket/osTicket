# Multi-ticket GitHub setup

Use this checklist so 2–3 Linear **Ready** tickets can run concurrently via
Cursor Automation and each finish with its own PR without clobbering the base
branch.

## How concurrent runs isolate

| Layer | Isolation |
|-------|-----------|
| Compute | One Cursor cloud VM per Automation trigger |
| Git remote | Per-ticket branch `strangler/MOD-*` (orchestrator creates at pipeline start) |
| PR target | Base branch from `GITHUB_DEMO_BRANCH` (e.g. `demo/sla-strangler`) |
| Fixtures / state | Already keyed by ticket id under `orchestrator/fixtures/MOD-*/` |

The orchestrator **never** pushes to the base branch. Only `strangler/MOD-*`
refs receive commits.

## Required secrets (Automation / cloud agent)

Mirror these from local `.env` into the Cursor Automation environment:

| Secret | Purpose |
|--------|---------|
| `CURSOR_API_KEY` | SDK agents |
| `GITHUB_REPO_URL` | Cloud agent repo + git remote |
| `GITHUB_DEMO_BRANCH` | **Required** — PR base branch (not the push target) |
| `LINEAR_API_KEY` | Ticket status / comments |
| `SLACK_WEBHOOK_URL` | Optional PR-opened notification |

### GitHub token permissions

The cloud VM must authenticate `git push` and `gh pr create`. Typical setups:

- **Fine-grained PAT** or **classic PAT** with `contents: write` and
  `pull_requests: write` on this repository, **or**
- **GitHub App** installation with the same scopes.

Verify the automation identity can:

1. Create and push refs matching `strangler/*`
2. Open multiple PRs concurrently against the base branch
3. Trigger Actions on those PRs (default for same-repo PRs)

## Recommended branch protection (base branch)

Protect `demo/sla-strangler` (and `develop` if used as a merge target):

1. **Settings → Branches → Add rule** for the base branch pattern.
2. Require status check **Parity Check** (`.github/workflows/parity-check.yml`).
3. Disable force-push on the base branch.
4. Optionally disallow direct pushes so all changes land via PR.

The automation user should be allowed to **open** PRs; humans (or a merge queue)
merge after CI passes.

## Merge strategy when facades overlap

If two tickets patch the same `facadeFile` (e.g. `include/class.sla.php`):

- Both pipelines can finish and open separate PRs.
- Merge or rebase **one PR at a time** into the base branch.
- Rebase the second PR onto the updated base before merge.

Optional later: enable GitHub **merge queue** on the base branch.

## Branch cleanup (optional)

After merge, delete remote `strangler/MOD-*` branches via:

- GitHub **Automatically delete head branches** on merged PRs, or
- A scheduled workflow / manual cleanup.

## CI

No workflow changes are required for multi-PR concurrency. Each PR triggers its
own `parity-check` job with an isolated checkout.

## Ops rules

- **Automation XOR listener** — do not run `orchestrator/listener.ts` while
  the Linear Automation is active.
- Move N tickets to **Ready** → N Automations → N PRs → N Linear **In Review**
  updates (only after parity passes).
