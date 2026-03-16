---
name: conventional-commit
description: "Prompt and workflow for generating conventional commit messages. Guides creation of standardized, descriptive commit messages following the Conventional Commits specification. Triggers when user asks to commit, write a commit message, stage changes, or push work."
license: MIT
metadata:
  author: github
---

# Conventional Commit

Generate standardized commit messages following the [Conventional Commits specification](https://www.conventionalcommits.org/en/v1.0.0/).

## Workflow

1. Run `git status` to review changed files.
2. Run `git diff` or `git diff --cached` to inspect changes.
3. Stage your changes with `git add <file>`.
4. Construct the commit message using the structure below.
5. Run `git commit -m "type(scope): description"` in the terminal.

## Commit Message Structure

```
type(scope): description

[optional body]

[optional footer]
```

### Types

| Type | Use when |
|------|----------|
| `feat` | New feature |
| `fix` | Bug fix |
| `docs` | Documentation only |
| `style` | Formatting, missing semicolons, etc. (no code change) |
| `refactor` | Code change that neither fixes a bug nor adds a feature |
| `perf` | Performance improvement |
| `test` | Adding or updating tests |
| `build` | Build system or dependency changes |
| `ci` | CI configuration changes |
| `chore` | Other maintenance tasks |
| `revert` | Reverts a previous commit |

### Scope (for Freetter modules)

Use the module name as scope when the change is isolated to a domain:

- `identity` — authentication, users, workspaces, invitations
- `publishing` — posts, tags, media, versions
- `activity` — activity logs
- `audience` — subscribers, lists
- `community` — memberships, interactions
- `delivery` — campaigns, sends

## Examples

```bash
feat(identity): add accepted_at column to invitations migration
fix(publishing): correct foreign key targets in post migration
refactor(activity): fix migration loading path in service provider
test(publishing): add feature tests for post creation use case
chore: run pint formatting on modified PHP files
docs: update CURRENT_STATE.md with P0 fix progress
```

## Breaking Changes

Append `!` after the type/scope and add a `BREAKING CHANGE:` footer:

```
feat(identity)!: change workspace FK naming convention

BREAKING CHANGE: renames `workspace_id` to `identity_workspace_id` across all tables.
```

## Validation Rules

- **type**: Must be one of the allowed types above.
- **scope**: Optional but recommended; use module name for Freetter changes.
- **description**: Required. Use imperative mood ("add", "fix", "update" — not "added" or "fixes").
- **body**: Optional. Use for context or rationale.
- **footer**: Use for breaking changes or issue references (`Closes #123`).
