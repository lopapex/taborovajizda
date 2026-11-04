---
name: tj-save-changes
description: Review Táborová jízda theme edits, create a safe working branch, commit them, and push the branch to GitHub. Use after editing the junction-connected theme in LocalWP or directly in the repository.
---

# Save theme changes

Version the current live LocalWP theme without copying it.

1. Read `AI-README.md` and run `scripts/connect-localwp-theme.ps1` without `-Apply` to verify that LocalWP is connected.
2. Show `git status --short` and the theme diff. Refuse changes outside `theme/`; never stash, discard, or include unrelated work.
3. Run `npm run check` from `theme/` and PHP syntax checks before proposing a commit. A running `npm run dev` is only the automatic watch build, not the final check.
4. Ask for a short change description.
5. If the current branch is `main`, run `git fetch origin main`, create `change/YYYY-MM-DD-<slug>`, and keep the working changes on that branch. Never commit directly to `main`.
6. Ask for confirmation before committing and pushing. Commit only `theme/` with the supplied description.
7. If `origin/main` advanced, rebase the new commit onto it before pushing; stop on conflicts and do not resolve them by discarding work.
8. Push with `git push -u origin <branch>` and return the GitHub pull-request URL.

Do not create or merge a pull request, tag a release, or modify production unless separately requested.
