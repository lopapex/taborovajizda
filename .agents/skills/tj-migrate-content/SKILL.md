---
name: tj-migrate-content
description: Migrate WordPress content edits (pages, posts, featured images, SEO descriptions, documents) from the LocalWP copy of Táborová jízda to stage (`/test/`) or production, together with a theme release. Use when a redesign or editorial round made in LocalWP must reach stage or production without copying the database.
---

# Migrate content and theme release

Content lives in the WordPress database, the theme in Git. Never copy a whole database between environments (`WORDPRESS-CONTENT.md`): the targets have their own posts, plugins, users, form data and media. Migrate only the reviewed difference, object by object, and keep everything reversible through WordPress revisions and a fresh backup.

Read `AI-README.md` first. Production is changed only after a separate explicit request; show the full change list and wait for confirmation before every write to stage or production.

## Environments

| | LocalWP | Stage | Production |
| --- | --- | --- | --- |
| Home URL | `http://taborovajizda-local.local` | `https://taborovajizda.cz/test` | `https://taborovajizda.cz` |
| Admin | `/wp-admin/` | `/test/wp-admin/` (WP Staging login first) | `/65e43gerg534e/` |
| `WP_ENVIRONMENT_TYPE` | `local` | `staging` | `production` |
| Permalinks | `/%postname%/` | `/index.php/%postname%/` | `/%postname%/` |
| Uploads | local disk, missing files proxied read-only from production by LocalWP nginx | own copy from the WP Staging clone | origin |
| Forms | simulated | simulated | real e-mails and external DB |

Stage uses index.php permalinks because the server nginx sends every non-existing path under `/test/` to the production WordPress, and only `/test/index.php/...` reaches stage PHP. Nobody on the project has access to that nginx config. The theme (`app/IndexPermalinks.php`) adds `index.php/` to template, content, menu and widget links while those permalinks are active; on production it does nothing. Never change stage back to "Post name" permalinks, and never set index.php permalinks on production.

Credentials are never typed by the agent. The user signs in to stage or production in the built-in browser pane; the agent then works in that session. Read `WP_ENVIRONMENT_TYPE` in Tools → Site Health → Info before any form test.

## 1. Inventory the local change

1. Use the LocalWP MySQL from `%APPDATA%\Local\sites.json` (port, `root`/`root`, database `local`, prefix `wp_`).
2. Pick the baseline: the last `Local Sites/taborovajizda-local/backups-claude/pre-*.sql` taken before the editorial round. Load it into a temporary database, e.g. `tj_baseline`, and drop it at the end.
3. Diff against the live local database:
   - `wp_posts` (excluding `revision`, `auto-draft`): `post_content`, `post_title`, `post_status`, `post_excerpt`;
   - `wp_postmeta`: new or changed rows, ignoring `_edit_lock`, `_edit_last`, `stackable_optimized_css*`; `_thumbnail_id` and `ssp_meta_description` matter;
   - `wp_term_relationships`, `wp_terms`;
   - `wp_options`: `widget_*`, `sidebars_widgets`, `nav_menu*`, `sticky_posts`, `page_on_front`, SEO options.
4. Drop local artefacts from the list: changes that only repair the LocalWP restore (e.g. `facebook.com/taborovajizda-local.local`, `@taborovajizda-local.local` e-mails, `http://category/...`). Check the target instead; it usually has the right value already.
5. Write the list as a table: object, ID, what changes, target status. Show it to the user.

## 2. Check the target before overwriting

Read every object on the target through the REST API in the signed-in browser (`/?rest_route=/wp/v2/pages/<id>&context=edit`; nonce from `wp-admin/admin-ajax.php?action=rest-nonce`, header `X-WP-Nonce`). The plain `/wp-json/` path is blocked.

- Normalise URLs (target home ↔ local home) and compare the target with the **baseline**, not with the new local version.
- Target equals baseline → safe to overwrite.
- Target differs → someone edited it there. Do not overwrite; show the difference and let the user decide. Keep target-only edits (for example a renamed title) unless told otherwise.
- Already equal to the new local version → skip.
- Confirm all referenced attachment IDs (featured images, `wp-image-*`, `data-id`) exist on the target (`/wp/v2/media&include=...`).

## 3. Map URLs

- Replace `http(s)://taborovajizda-local.local` with the target home URL. For stage that is `https://taborovajizda.cz/test` (the theme adds `index.php/` at render time); for production `https://taborovajizda.cz`.
- Root-relative links (`/wp-content/...`, `/e-prihlaska/`) break on stage because `/` is production. Store absolute target URLs.
- Documents link straight to the PDF in uploads, never to an attachment page.
- After mapping, the payload must contain no `taborovajizda-local`, no `href="/`, and no link to another environment.

## 4. Documents

The parents' documents (zmocnění, bezinfekčnost a souhlas, potvrzení lékaře) are attachments with `_wp_attached_file` under `uploads/`. LocalWP does not have the files; it proxies them from production.

1. Download every non-image attachment from production into a fresh temporary folder and copy the missing ones into the local `wp-content/uploads/`. Uploads are not in Git.
2. Check the local site serves them from disk: response `ETag` differs from production.
3. On stage the files exist from the clone; verify each linked PDF returns 200 with the stage `ETag`, not the production one.
4. A new document is uploaded to the target media library by the user (file pickers cannot be filled by the agent). Then use its target URL in the content.

## 5. Apply

Before production: the user makes a backup (Backup Migration plugin) and keeps the previous theme ZIP for rollback.

- Page and post content and titles: `POST /wp/v2/pages/<id>` or `/wp/v2/posts/<id>` with `content` / `title` / `status`. WordPress stores a revision, which is the rollback.
- Featured images: `POST /wp/v2/posts/<id>` with `featured_media`.
- SEO SIMPLE PACK description (`ssp_meta_description`) is not in REST: post the block editor meta-box form (`metabox-base-form` and `metabox-location-*` inputs, override `ssp_meta_description`) to the edit page's meta-box loader URL, then re-read the field. Check the post content length did not change.
- Widgets and menus: apply only real content changes, through Appearance in the admin or `/wp/v2/widgets`.
- Read every object back and compare it with the payload.

## 6. Theme

1. Merge the tested theme into the source branch, then use `$tj-create-release` (`stage` for stage, `main` for production).
2. The user uploads the ZIP in Appearance → Themes → Add → Upload and chooses "Replace current with uploaded". The agent cannot attach the file.
3. Verify the target loads the CSS asset name from the release manifest's build (`public/build/assets/app-*.css`).
4. Production: purge LiteSpeed Cache and the browser cache.

## 7. Verify

On the target, logged in:

- homepage, `#dokumenty`, Dokumenty, Informace pro rodiče, Kontakt, Přihláška, one category and one post return 200 without `Fatal error`;
- stage: no link on these pages leads outside `https://taborovajizda.cz/test/` (except external sites); pages open under `/test/index.php/...`;
- every PDF link returns 200 from the target itself;
- stage only: the registration form is visible (debug display) and a test submit shows the simulated success; production forms are tested only with one controlled real registration agreed with the user (`FORMS-AND-EMAILS.md`);
- desktop and 360 px.

Report the migrated objects, skipped objects with the reason, release ZIP and SHA-256, verification results, and what is left for the user.

## Redesign 2027 content set

The 11. 11. 2026 editorial round in LocalWP (baseline `pre-content-redesign-20261111-140339.sql`) changed:

| Object | Change | Stage (12. 11. 2026) |
| --- | --- | --- |
| Page 432 homepage | timeless intro text, group photo, no 2026/2025 blocks | migrated; stage title "…VERZE 2" kept |
| Page 20 Dokumenty | working links to bezinfekčnost, potvrzení lékaře, zmocnění 2026 | migrated |
| Page 289 Informace pro rodiče | FAQ links to `#dokumenty`, `#kontakt`, přihláška, PDFs; hlavní vedoucí Matěj Vašek | migrated |
| Page 4917 Kontakt | hlavní vedoucí Matěj Vašek | migrated |
| Page 28, 4917 | `ssp_meta_description` | migrated |
| 12 activity posts (313–341) | featured image from their gallery | migrated |
| 12 people posts (239, 345, 368, 419, 421, 442, 3629, 3643, 4138, 4396, 5064, 5086) | featured photo | migrated |
| Post 3616 Matěj Vašek | title "(hlavní vedoucí)" | already on stage |
| Post 219 Martin Vojtěch | hidden (local draft) | already private on stage |
| Posts 2309, 5325, 5708 | local restore artefacts only | not needed |

A Czech proofreading round on 12. 11. 2026 (baseline `pre-proofread-20261112-071008.sql`) then fixed spelling, punctuation and typography in pages 3, 20, 43, 289, 432, 4372, 4917, the people posts (239, 345, 368, 419, 421, 442, 829, 2261, 3616, 3629, 3643, 3660, 4138, 4396, 5064, 5086, 5106; quotes „…“ and lowercase role in six titles), the activity posts (311, 313, 317, 324, 331, 334, 336) and the 2026 posts (5572, 5603, 5613, 5701, 5708). The homepage line "A baví nás to (snad je to vidět)" became "A baví nás to. Přesvědčte se sami na fotkách z minulých ročníků." with a link to `#minule-rocniky`. Stage got the same changes as exact old → new replacements applied to its own content; do the same on production and report any replacement that is not found instead of overwriting the post. Historical posts (before 2026) are not proofread yet.

For production, run steps 2–7 for this set against production: compare with the production state, map URLs to `https://taborovajizda.cz`, then apply after confirmation.
