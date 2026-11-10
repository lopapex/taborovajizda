# Audit obsahu a migrační pravidla 2027

Tento dokument je výstupem Fáze A redesignu. Opírá se o lokální WordPress sitemapu, `WORDPRESS-CONTENT.md`, aktivní menu, widgety a šablonový kód. Neprovádí žádnou databázovou migraci.

## Zásady

- Každá současná veřejná URL zůstává dostupná se stavem HTTP 200.
- Homepage může obsah z existujících stránek znovu použít jako sekce, ale původní stránky se nemažou ani nepřesměrovávají.
- Žádný 301 redirect není v této fázi potřeba ani schválen.
- Standardní WordPress příspěvky zůstávají zdrojem aktualit a jejich permalink se nemění.
- `theme/config/season.json` zůstává jediným zdrojem aktivního ročníku, dokud nebude samostatně schválen CMS model.

## Inventář

| Oblast | Zjištěný stav | Zdroj pravdy |
| --- | --- | --- |
| Stránky | 13 indexovaných URL včetně homepage | `wp-sitemap-posts-page-1.xml` |
| Příspěvky | 152 publikovaných URL | `wp-sitemap-posts-post-1.xml` |
| Rubriky | 12 indexovaných URL | `wp-sitemap-taxonomies-category-1.xml` |
| Hlavní menu | domů, aktuality, pro rodiče, dokumenty, aktivity, lidé, kontakt, minulé ročníky | WordPress menu `menu_main` |
| Mobilní menu | Samostatné WordPress menu `menu_main_mobile` | `header.php` a WordPress menu |
| Sidebar widgety | Sociální sítě, roční informace, countdown, trailer a nejnovější příspěvky | WordPress widgety |
| Dokumenty | Zmocnění, bezinfekčnost a souhlas, potvrzení lékaře, obsah kufru | `/dokumenty/` a média WordPressu |
| Aktuální roční média | Plakát, sezonní hero, pozadí a YouTube trailer | `season.json`, `resources/assets/season/` a widgety |
| Galerie | Envira Gallery a Photonic Gallery & Lightbox jsou aktivní; konkrétní vložení zůstává redakčním obsahem | WordPress obsah a pluginy |

## Mapování stránek

| Současná URL | Cílové použití | Zachování URL |
| --- | --- | --- |
| `/` | One-page prezentace | Ano, hlavní vstupní stránka |
| `/dokumenty/` | Upoutávka v `#pro-rodice` a plná samostatná stránka | Ano |
| `/e-prihlaska/` | Samostatná stávající stránka mimo tento redesign | Ano |
| `/informace-pro-rodice/` | Zdroje pro `#pro-rodice`; samostatná stránka zůstává | Ano |
| `/kontakt-2/` | Zdroje pro `#kontakt`; samostatná stránka zůstává | Ano |
| `/litujeme/` | Samostatný informační stav | Ano |
| `/minule-rocniky/` | Archiv ročníků | Ano |
| `/obsah-taboroveho-kufru/` | Propojený zdroj z části pro rodiče | Ano |
| `/ochrana-osobnich-udaju/` | Právní stránka | Ano |
| Historické stránky 2020 a 2021 | Odkazy z archivu, bez slučování či přesměrování | Ano |

## Interní odkazy a navigace

Hlavní menu je spravované ve WordPressu a šablona mu doplňuje fragmenty `#menu` a `#menu_mobile`. Při zavedení kotvové navigace je nutné zachovat stávající položky a jejich cíle přemapovat takto:

| Současný záměr odkazu | Cílový odkaz po redesignu |
| --- | --- |
| Domů | `/#aktuality` nebo začátek homepage |
| Aktuality | `/#aktuality` se zachovaným odkazem na `/category/aktuality/` jako archiv |
| Pro rodiče | `/#pro-rodice` |
| Dokumenty | `/#pro-rodice` a samostatné `/dokumenty/` v obsahu sekce |
| Aktivity a lidé | `/#program` se zachovanými rubrikovými archivy |
| Kontakt | `/#kontakt` a zachované `/kontakt-2/` |
| Minulé ročníky | `/minule-rocniky/` |

Explicitní odkazy v PHP zůstávají funkční: ochrana osobních údajů, odesílací endpoint formulářů a odkazy aktualit. Jejich URL se nemění.

## Globální a roční obsah

| Typ | Patří sem | Pravidlo |
| --- | --- | --- |
| Roční provozní obsah | název, termín, cena, věk, místo, stav, registrační URL, aktuální plakát a hero | Zdroj: `season.json` a aktivní roční widgety |
| Globální obsah | popis tábora, aktivity, lidé, kontakty, právní informace, dokumenty, sociální sítě | Zdroj: WordPress stránky, příspěvky, menu a widgety |
| Historický obsah | příspěvky a stránky minulých ročníků | Zdroj: explicitní rubriky a stávající permalink |

## Historické ročníky

Mapování používá pouze explicitní rubriky. Datum publikace není zdrojem přiřazení.

| Rok | Rubrika |
| ---: | --- |
| 2018 | `tabor2018` |
| 2019 | `tabor-2019` |
| 2021 | `tabor-2021` |
| 2022 | `tabor-2022` |
| 2023 | `tabor-2023` |
| 2024 | `tabor-2024` |
| 2025 | `tabor-2025` |
| 2026 | `tabor-2026` |

Příspěvky v rubrice `Nezařazené` ani stránky 2020 a 2021 se automaticky nepřiřazují. Před datovou migrací potřebují ruční redakční rozhodnutí.

## Rozhodnutí pro první vizuální krok

První redesign nepřidává taxonomii `rocnik`, ACF ani vlastní metadata. Aktivní ročník nadále řídí `season.json`; historické ročníky čte archiv ze stávajících explicitních rubrik. Tím nevznikají dva konkurenční zdroje dat.

## Migrační a rollback plán

1. Před každou budoucí obsahovou migrací exportovat databázi pouze z lokálu nebo stage a zaznamenat seznam dotčených ID příspěvků.
2. Novou taxonomii nebo metadata přidat nejdřív bez přesunu obsahu a ověřit v lokálu.
3. Přesouvat jen položky s ručně potvrzeným ročníkem; zachovat slug, datum, autora, obsah, média a původní rubriky do ověření.
4. Po každé dávce porovnat sitemapu, HTTP 200, canonical URL a odkazovou strukturu.
5. Rollback provést obnovením lokální nebo stage databázové zálohy a odstraněním pouze nově přidaných term vztahů či metadat.

Produkční databáze se v rámci tohoto plánu nemění.
