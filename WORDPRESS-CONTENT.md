# Mapa WordPress obsahu

Tento dokument popisuje, odkud se bere veřejný obsah a kde jej měnit při běžné editaci nebo redesignu. Inventura vychází z lokální kopie produkční databáze k 5. listopadu 2026.

## Jak WordPress skládá stránku

- Obsah stránek a příspěvků je v databázi a upravuje se ve WordPress administraci, nikoliv v Gitu.
- Společný rámec stránky tvoří `header.php`, `sidebar.php` a `footer.php`.
- Běžné stránky bez vlastního slug template používají fallback `index.php`.
- `/e-prihlaska/` automaticky používá `page-e-prihlaska.php` a `/kontakt-2/` používá `page-kontakt-2.php` podle WordPress template hierarchy.
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
| 4917 | Kontakt | `/kontakt-2/` | Kontaktní formulář a text v Gitu | `page-kontakt-2.php` |
| 803 | Přihlašování je aktuálně uzavřeno | `/litujeme/` | Stránky → Přihlašování je aktuálně uzavřeno | `index.php` |
| 43 | Minulé ročníky | `/minule-rocniky/` | Stránky → Minulé ročníky | `index.php` |
| 4372 | Obsah táborového kufru | `/obsah-taboroveho-kufru/` | Stránky → Obsah táborového kufru | `index.php` |
| 3 | Zásady ochrany osobních údajů | `/ochrana-osobnich-udaju/` | Stránky → Zásady ochrany osobních údajů | `index.php` |
| 2123 | Tábor 2020: Vikingové | `/tabor-2020-vikingove/` | Stránky → příslušná stránka | `index.php` |
| 2459 | Tábor 2021: Egypt | `/tabor-2021-egypt/` | Stránky → příslušná stránka | `index.php` |
| 2589 | Tábor 2021 — Souhrnné informace (nejen k testování) | `/tabor-2021-souhrnne-informace-nejen-k-testovani/` | Stránky → příslušná stránka | `index.php` |
| 2473 | Táborové výzvy 2021 | `/taborove-vyzvy-2021/` | Stránky → příslušná stránka | `index.php` |

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
