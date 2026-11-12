# Instrukce pro AI asistenty

Tento soubor je autoritativní společná dokumentace pro Codex, Claude, GitHub Copilot a další agenty. Produktové vstupní soubory na něj pouze odkazují.

## Neměnné hranice

- `theme/` je jediný verzovaný zdroj vlastní WordPress šablony.
- LocalWP používá `theme/` přímo přes Windows junction.
- Do Gitu nepatří databáze, WordPress core, pluginy, `uploads`, zálohy, hesla, `.env`, `wp-config.php`, logy ani `.localwp.json`.
- Produkční release je pouze theme ZIP.
- Neměň produkci, databázi ani externí služby bez samostatného výslovného požadavku.
- Zachovej uživatelské změny. Nestashuj je, nezahazuj je a nepoužívej destruktivní Git příkazy.
- Před přesunem, odstraněním, commitem, pushem nebo nasazením ukaž dopad a vyžádej potvrzení odpovídající riziku.

## Setup po branách

Každou bránu nejprve ověř. Pokud uživatel musí instalovat program nebo dokončit GUI krok, dej jedinou přesnou instrukci, zastav se a čekej na potvrzení. Potom stav znovu ověř.

### 1. Programy

```powershell
git --version
code --version
node --version
npm --version
Test-Path "$env:LOCALAPPDATA\Programs\Local\Local.exe"
```

Požadavky: Git, VS Code, LocalWP, Node.js LTS a npm. Composer není potřeba.

### 2. Repozitář

Ověř `git rev-parse --show-toplevel`, vzdálený repozitář a `theme/style.css`. Klon:

```powershell
git clone https://github.com/lopapex/taborovajizda.git
cd taborovajizda
code .
```

### 3. LocalWP

Pokud web neexistuje, uživatel v GUI vytvoří `taborovajizda-local` s prostředím Preferred. Po potvrzení ověř `wp-admin`, `wp-content`, `wp-includes` a `wp-config.php`.

Obnova produkční zálohy je volitelná. Nespouštěj ji automaticky.

### 4. Lokální konfigurace a junction

Cestu odvoď ze skutečného webu a nastav v ignorovaném `.localwp.json`. Ověř:

```powershell
git check-ignore -v .localwp.json
.\scripts\connect-localwp-theme.ps1
```

Před `-Apply` ukaž zdroj, cíl a zálohu a počkej na potvrzení. Původní LocalWP šablonu nikdy nemaž.

### 5. Build

```powershell
cd theme
npm install
npm run build
cd ..
```

Ověř existenci `theme/public/build/manifest.json`. Build je ignorovaný; `package-lock.json` je verzovaný.

## Architektura

- `app/`: malé namespaced moduly pro theme, roční data, data homepage (`Home.php`) a oddělené formulářové handlery;
- `config/season.json`: aktivní roční provozní data;
- `config/archive-seasons.json`: ověřené historické ročníky, trailery a explicitní vazby na rubriky; bez historických cen a termínů;
- `resources/css/app.css`: Tailwind vstup, barevné a stínové tokeny a komponenty použité alespoň třikrát; vlastní markup používá přímo `tj:` utility bez `@apply`; barvy pouze jako tokeny a CSS proměnné;
- `resources/css/content.css`: pouze dynamický WordPress/plugin markup;
- `resources/css/season.css`: pouze aktivní roční vzhled a efekty;
- `resources/ts`: TypeScript bez Reactu;
- `template-parts/`: pouze skutečně sdílené části (dialog, pager, nadpis sekce, fakta, sociální sítě, formulář dotazu, notifikace formulářů); jednorázový markup zůstává ve WordPress entrypointu;
- `theme.json`: společné design tokeny;
- kořenové WordPress templates: `front-page.php`, `page-e-prihlaska.php`, `index.php` (vše ostatní včetně rubrik a 404), `header.php`, `footer.php`. Legacy layout, sidebar, menu, widgety, odpočet ani newsletter už neexistují; nevracej je.

PHP 8.2 a WordPress 6.5 jsou minimum. Používej namespace `TaborovaJizda\Theme`. Nevytvářej globální legacy soubor, vlastní image pipeline ani kopie jednorázových templates.

## Vývoj a kontrola

Spusť LocalWP a `npm run dev` v `theme/`. Po změně spusť `npm run check` a PHP syntax přes PHP dodané LocalWP. Ověř homepage, přihlášku, dokumenty, kontakt, jednu rubriku, jeden příspěvek a administraci.

`npm run dev` je watch build: změny v `resources/css`, `resources/ts` a jejich assetech automaticky přegeneruje do `public/build/`; uživatel potom pouze obnoví stránku. PHP a `template-parts` se projeví po refreshi bez buildu. Nepožaduj po uživateli ruční `npm run build` po každé změně.

Zachovej URL, slugy, navigaci, názvy a pořadí formulářových polí, logo a obsahový hlas, pokud uživatel výslovně neschválí změnu.

Lokální formulářové testy nesmí zapisovat do externí produkční databáze ani posílat skutečné e-maily.

Newsletter je zrušený. Homepage používá sdílený dotazový formulář; jeho příjemce je `contactEmail` ze `season.json`.

## Roční změna

Git je zdroj pravdy pro aktivní rok, název, datum, cenu, kontaktní e-mail, stav registrace a explicitní externí season key. Nepotvrzené hodnoty nech `null`; nevymýšlej je. `contactEmail` je povinná platná adresa a používá se jako provozní odesílatel a interní příjemce formulářových zpráv.

Použij `$tj-new-season` nebo nejdřív spusť `scripts/new-season.ps1` bez `-Apply`. Nevytvářej kopii celého ročního stylesheetu. Provozní data měň v `season.json`; `registration.remainingPlaces` je ručně spravovaný počet volných míst a `null` jej skryje. Nahraď pevně pojmenované `resources/assets/season/hero.jpg`, design tokeny měň v `theme.json` a roční efekty v `resources/css/season.css`. Sémantickou CSS komponentu vytvářej pouze pro vzor použitý alespoň třikrát; nepoužívej `@apply`.


Při redesignu nejprve načti repozitářový `$design-taste-frontend`, proveď audit a zachovej SEO a informační architekturu.

## Uložení změn

1. Ukaž `git status --short` a relevantní diff.
2. Změny na `main` ulož na větev `change/YYYY-MM-DD-<slug>`; roční změny na `season/<rok>-<slug>`.
3. Před commitem a pushem vyžádej potvrzení.
4. Commituj pouze zamýšlené soubory.
5. Pushni pracovní větev a vrať URL pro pull request.
6. Pull request automaticky nevytvářej ani neslučuj bez dalšího požadavku.

Použij `$tj-save-changes`, když odpovídá rozsahu.

## Release

Release vzniká pouze z čistého otestovaného commitu:

```powershell
.\scripts\create-theme-release.ps1 -SourceBranch stage
.\scripts\create-theme-release.ps1 -SourceBranch main
```

Zdrojová větev je povinná a může být pouze `stage` nebo `main`. Skript musí exportovat commit explicitně zvolené lokální větve, spustit `npm ci` a `npm run build`, zabalit pouze runtime whitelist a ověřit jediný kořen `taborovajizda/`. Reportuj větev, ZIP, manifest, commit, tag, velikost, počet položek a SHA-256.

Release artefakty necommituj, nenahrávej a nenasazuj bez samostatného požadavku. Použij `$tj-create-release`.

## Produkční nasazení

Před nahráním ukaž ZIP, manifest, rollback a kontrolní kroky a vyžádej samostatné potvrzení. Nasazení probíhá ručně přes Vzhled → Šablony → Instalovat → Nahrát. Po nahrazení vyčisti cache a proveď produkční smoke test.

## Migrace obsahu a stage

Redakční změny z LocalWP se na stage i produkci přenášejí po jednotlivých objektech, nikdy kopií databáze. Postup, mapování URL, dokumenty, ověření a seznam obsahu redesignu 2027 jsou ve skillu `$tj-migrate-content` (`.agents/skills/tj-migrate-content/SKILL.md`).

Stage (`https://taborovajizda.cz/test/`, WP Staging) má trvalé odkazy `/index.php/%postname%/`, protože nginx serveru posílá ostatní cesty pod `/test/` na produkční WordPress a ke konfiguraci serveru nemáme přístup. Šablona v tom režimu sama doplňuje `index.php/` do odkazů (`app/IndexPermalinks.php`); na produkci nic nemění. Stage má `WP_ENVIRONMENT_TYPE` `staging`, zobrazuje ladicí přihlášku a formuláře jen simuluje.
