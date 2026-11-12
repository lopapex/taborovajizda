# Mapa WordPress obsahu

Tento dokument popisuje, odkud se bere veřejný obsah a kde jej měnit při běžné editaci nebo redesignu. Inventura vychází z lokální kopie produkční databáze k 5. listopadu 2026.

## Jak WordPress skládá stránku

- Obsah stránek a příspěvků je v databázi a upravuje se ve WordPress administraci, nikoliv v Gitu.
- Společný rámec stránky tvoří `header.php`, `sidebar.php` a `footer.php`.
- Běžné stránky bez vlastního slug template používají fallback `index.php`.
- `/e-prihlaska/` automaticky používá `page-e-prihlaska.php` podle WordPress template hierarchy. Kontakt je sekce `#kontakt` na homepage.
- Výpis rubrik prochází přes `category.php`; jednotlivý markup příspěvku sdílí `template-parts/post.php`.
- Vzhled, komponenty, formulářová logika a roční konfigurace patří do Gitu. Text redakčního obsahu, widgety, menu a média patří do WordPressu.

## Stránky

WordPress má nastavenou statickou homepage: stránka ID `432`, slug `vitejte-na-strankach-taborove-jizdy`. Stránka příspěvků není samostatně nastavena.

| ID | Veřejný název | Slug / URL | Kde upravit obsah | PHP template |
| ---: | --- | --- | --- | --- |
| 432 | Vítejte na stránkách Táborové jízdy | `/` (`vitejte-na-strankach-taborove-jizdy`) | Stránky → upravit homepage | `index.php` |
| 20 | Dokumenty | `/dokumenty/` | Stránky → Dokumenty | `index.php` |
| 28 | Elektronická přihláška na tábor | `/e-prihlaska/` | Pevný formulář a text v Gitu; stav ročníku v `season.json` | `page-e-prihlaska.php` |
| 289 | Informace pro rodiče | `/informace-pro-rodice/` | Stránky → Informace pro rodiče | `index.php` |
| 4372 | Obsah táborového kufru | `/obsah-taboroveho-kufru/` | Stránky → Obsah táborového kufru | `index.php` |
| 3 | Zásady ochrany osobních údajů | `/ochrana-osobnich-udaju/` | Stránky → Zásady ochrany osobních údajů | `index.php` |

Stránky, kam se z redesignu 2027 nedá dostat (4917 Kontakt, 803 Přihlašování uzavřeno, 43 Minulé ročníky, 2123, 2459, 2473 a 2589 z let 2020–2021), a 26 starých aktualit z let 2018–2022 byly 12. 11. 2026 přesunuty do koše na lokálu a na stage; postup je v `.agents/skills/tj-migrate-content/SKILL.md`.

## Příspěvky a rubriky

V inventuře je 152 publikovaných příspěvků. Jeden příspěvek může být zařazen do více rubrik, proto součet počtů v tabulce nemusí odpovídat 152.

| Rubrika | Slug / URL | Publikovaných příspěvků | Zobrazení |
| --- | --- | ---: | --- |
| Aktuality | `/category/aktuality/` | 75 | `category.php` → `template-parts/post.php` |
| Lidé | `/category/lide/` | 18 | `category.php` → `template-parts/post.php` |
| Nezařazené | `/category/nezarazene/` | 1 | `category.php` → `template-parts/post.php` |
| Tábor 2018 | `/category/tabor2018/` | 8 | `category.php` → `template-parts/post.php` |
| Tábor 2019 | `/category/tabor-2019/` | 14 | `category.php` → `template-parts/post.php` |
| Tábor 2021 | `/category/tabor-2021/` | 13 | `category.php` → `template-parts/post.php` |
| Tábor 2022 | `/category/tabor-2022/` | 7 | `category.php` → `template-parts/post.php` |
| Tábor 2023 | `/category/tabor-2023/` | 18 | `category.php` → `template-parts/post.php` |
| Tábor 2024 | `/category/tabor-2024/` | 10 | `category.php` → `template-parts/post.php` |
| Tábor 2025 | `/category/tabor-2025/` | 18 | `category.php` → `template-parts/post.php` |
| Tábor 2026 | `/category/tabor-2026/` | 6 | `category.php` → `template-parts/post.php` |
| Táborové aktivity | `/category/taborove_aktivity/` | 14 | `category.php` → `template-parts/post.php` |

Příspěvek se upravuje v administraci přes Příspěvky. Při změně struktury karty nebo detailu upravuj sdílený `template-parts/post.php`; nevytvářej kopie podle ročníků.

## Postup při remodelaci

1. Nejdřív určete, zda se mění obsah v databázi, nebo prezentace v šabloně.
2. Redakční text, bloky, rubriky, menu, widgety a média měňte ve WordPressu.
3. Layout, barvy, komponenty, formuláře a chování měňte v Gitu a ověřte v LocalWP.
4. Zachovejte slugy a veřejné URL. Přejmenování nebo odstranění vyžaduje plán přesměrování.
5. Otestujte homepage, všechny vlastní page templates, běžnou stránku, rubriku i jednotlivý příspěvek na desktopu a mobilu.
6. Produkční změnu šablony distribuujte pouze ověřeným theme ZIPem; databázi ze stage nepřenášejte zpět na produkci.

## Údržba mapy

Po přidání nebo odstranění stránky či rubriky aktualizujte tuto mapu. Počty příspěvků jsou orientační snapshot; architektura a slugy jsou důležitější než přesný počet.

## Ověřený inventář veřejných URL

Při redesignu je autoritativním úplným seznamem indexovatelných URL WordPress sitemap na lokálu (`/wp-sitemap.xml`). Ověřeno 10. listopadu 2026:

| Sitemap | Počet URL | Obsah |
| --- | ---: | --- |
| `/wp-sitemap-posts-page-1.xml` | 13 | stránky v tabulce výše včetně homepage |
| `/wp-sitemap-posts-post-1.xml` | 152 | jednotlivé publikované příspěvky |
| `/wp-sitemap-taxonomies-category-1.xml` | 12 | rubriky |

Při změně šablony nebo informační architektury ověřte všechny tři sitemapové soubory. Stávající URL nesmí zmizet ani se přesměrovat bez samostatně schváleného plánu.

## Snapshot pluginů pro redesign

Inventura lokální administrace 10. listopadu 2026: 24 aktivních pluginů a 1 neaktivní (`WP SVG Icons`). Stav je podkladem pro audit, nikoli souhlas k deaktivaci nebo změně produkce.

| Skupina | Pluginy | Postup v redesignu |
| --- | --- | --- |
| Runtime a veřejný obsah | Email Encoder, Envira Gallery, Fluent Forms, Insert PHP Code Snippet, jQuery T(-) Countdown Widget, LiteSpeed Cache, Photonic Gallery & Lightbox, Post SMTP, Safe SVG, SEO SIMPLE PACK, Zalomení | Zachovat, dokud audit konkrétního výstupu nepotvrdí náhradu. Countdown je widget, SEO ovlivňuje metadata a galerie mohou být v redakčním obsahu. |
| Redakční nástroje | AJAX Thumbnail Rebuild, Copy & Delete Posts, Fix Media Library, Genesis Blocks, Gutenberg, Kadence Blocks, Media Library Organizer, Stackable | Nejsou přímou závislostí nové šablony. Před případným úklidem je nutné ověřit bloky a média používané ve všech publikovaných příspěvcích a stránkách. |
| Správa, bezpečnost a vývoj | Advanced Access Manager, All In One Login, Backup Migration, Fail2WP, Query Monitor, WP Crontrol | Mimo rozsah redesignu. Zachovat beze změny. |

Kandidáti pro budoucí samostatný úklid jsou neaktivní `WP SVG Icons`, překrývající se blokové knihovny a jednorázové redakční nástroje. Žádný z nich se nemá odstranit bez ověření závislostí, zálohy a samostatného schválení.
