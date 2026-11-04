---
name: tj-connect-localwp
description: Connect the Táborová jízda repository theme directly to its LocalWP site using a Windows directory junction. Use for first-time setup, repair, or verification of the local development environment.
---

# Connect LocalWP

Make `theme/` the live LocalWP theme without copying files.

1. Read `AI-README.md`, follow its setup gates, and verify `.localwp.json` exists. If software installation or a LocalWP GUI step is required, instruct the user, stop, and wait for confirmation before verifying and continuing.
2. Run `scripts/connect-localwp-theme.ps1` without `-Apply` and show the repository path, LocalWP target, and proposed backup path.
3. Ask for confirmation immediately before applying because the existing LocalWP theme directory will be moved.
4. Run the script with `-Apply`. It must preserve the original directory as `taborovajizda.pre-junction-<timestamp>` and create a junction only at `wp-content/themes/taborovajizda`.
5. Verify the junction target and open the local homepage for a basic check when browser access is available.
6. If `theme/node_modules` is missing, run `npm install` from `theme/`, then run `npm run build` and verify `theme/public/build/manifest.json`.
7. Explain that normal CSS/TypeScript work uses a continuously running `npm run dev`; PHP and template changes need only a browser refresh.

Never delete the preserved backup, edit another theme, or touch the database, plugins, uploads, or production. If the path is already connected correctly, report success without changing anything.
