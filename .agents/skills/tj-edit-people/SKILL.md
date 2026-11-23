---
name: tj-edit-people
description: Change a person in the "Lidé" section of Táborová jízda (profile text, photo, role) on LocalWP, stage and production at once. Use when the user sends new profile data or a photo for a leader and wants all three environments to match.
---

# Edit the people section on all three environments

Read `AI-README.md` and `$tj-migrate-content` first. The user's request to change a person is the explicit go-ahead for production, stage and LocalWP; keep all three equal.

## How a person is stored

- A person is a **post in the category `lide`** (not a custom type). The homepage grid and the dialog come from `Home::people()`, `Home::person()` and `Home::article()` in `theme/app/Home.php`.
- The **portrait is the featured image** (`_thumbnail_id`). It is shown in a 4:5 frame with `object-fit: cover`. `personContent()` strips every image from the text when a featured image exists, so images inside `post_content` are never shown.
- The title carries the role: `Jméno Příjmení (role)`; the theme splits it into name and role. Change it only when the user asks.
- Text is core `wp:paragraph` blocks. Non-breaking spaces (U+00A0) follow one-letter words (a, i, k, o, s, u, v, z) and numbers (`2.`, `15`) before a word.
- Known IDs: Lucka Brožková 2261, Ondra Kopecký 345; the rest are listed in `$tj-migrate-content` (people posts). Find others by title, never by guessing.

## 1. Prepare

1. Get the data from the source the user gave (a Google Doc: `read_file_content` for text, `download_file_content` with `exportMimeType: application/zip` for the HTML export with `images/`). Look at the photos before using them.
2. Make a portrait: crop 4:5 around the face. No Pillow is installed; resize with PowerShell `System.Drawing` (`DrawImage` from a source rectangle, JPEG quality 85–90). A photo that is already tall (3:4) can stay as it is, `cover` trims it. Keep it under about 300 kB.
3. Build the new text from the user's wording, in the profile's voice. Remove statements that go stale (age) unless the user keeps them. Show the old → new difference.

## 2. Production first, then stage, then LocalWP

Compare the target with the current state before overwriting; if someone edited it there, show the difference and ask.

**Production** (`https://taborovajizda.cz`, admin `/65e43gerg534e/`, the user signs in in the built-in browser):

1. The agent cannot fill a file picker. Copy the photos into the ignored folder `.upload-tmp/` of the repo (it has its own `.gitignore`), reveal one with `reveal_path` and ask the user to drag them to `/wp-admin/media-new.php`. Wait for "nahráno", then read the new IDs from `/?rest_route=/wp/v2/media&per_page=5`.
2. Nonce: `fetch('/wp-admin/admin-ajax.php?action=rest-nonce')`, header `X-WP-Nonce`. Then `POST /?rest_route=/wp/v2/posts/<id>` with `{content, featured_media}`. WordPress keeps a revision, which is the rollback.
3. Read the public page back (`fetch('/<slug>/?v=' + Date.now())`) and check the new text and `uploads/...` filename. Purge LiteSpeed Cache in the admin bar.

**Stage** (`https://taborovajizda.cz/test`, REST under `/test/index.php?rest_route=`, nonce from `/test/wp-admin/admin-ajax.php?action=rest-nonce`; the user signs in at `/test/wp-login.php`):

- Same origin as production, so the agent uploads the photos itself: `fetch` the production file URL as a blob and `POST /wp/v2/media` with `Content-Disposition: attachment; filename="..."` and `Content-Type: image/jpeg`, then set the title. Then post `content` and `featured_media` as above.

**LocalWP** (`http://taborovajizda-local.local`, MySQL port from `%APPDATA%\Local\sites.json`):

1. Dump the database first to `Local Sites/taborovajizda-local/backups-claude/pre-<slug>-<date>.sql` (`mysqldump`, `root`/`root`).
2. Use WP-CLI through LocalWP's PHP and the site's `php.ini` (`%APPDATA%\Local\run\<site id>\conf\php\php.ini`): `wp media import <file> --porcelain --title=...`, `wp post update <id> <file>`, `wp post meta update <id> _thumbnail_id <media id>`. Copy the photo next to the WordPress root for the import and delete that copy afterwards.
3. Write the content file with a script that uses `lambda` replacements. Backslash references (`\1`) in a Python replacement string get mangled by the shell and silently delete words; check the result for dropped words and for the `\xa0` after one-letter words.

## 3. Verify

Open the person on all three environments (own page and homepage dialog), on desktop and 360 px: face fully visible in the 4:5 frame, text complete, no leftover old photo. Report the post IDs, the new media IDs per environment and what was not done. Remove `.upload-tmp/` content when finished.

## 4. Git

The people data are content, not Git; only this skill or theme changes go through `$tj-save-changes`. Never commit photos or SQL.
