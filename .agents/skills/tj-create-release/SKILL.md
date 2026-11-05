---
name: tj-create-release
description: Build a verified WordPress theme release ZIP and manifest from a clean Táborová jízda Git commit. Use when a tested theme version is ready for manual production deployment.
---

# Create theme release

Create a production package from Git, never from LocalWP.

1. Read `AI-README.md` and show the current branch, selected source branch (`main` or `stage`), its commit, exact tag if present, and `git status --short`.
2. Run `scripts/create-theme-release.ps1 -SourceBranch <main|stage>`. Do not bypass its clean-tree or forbidden-file checks. The script archives the selected local branch, runs `npm ci` and the production build from that committed source; never package the local watch output.
3. Report the generated ZIP and manifest paths, commit SHA, tag, byte size, entry count, and SHA-256.
4. Confirm the manifest records the selected source branch and the archive has the single root directory `taborovajizda/`.
5. Present the deployment and rollback instructions from the manifest.

Do not commit release artifacts, upload them, access production, create tags, or change Git history unless the user separately requests that action.
