# Táborová jízda - vývoj WordPress šablony

Repozitář obsahuje pouze vlastní WordPress šablonu a nástroje pro její vývoj. WordPress core, databáze, pluginy, média, zálohy a přístupové údaje do Gitu nepatří.

```text
Git theme/ ↔ Windows junction ↔ LocalWP wp-content/themes/taborovajizda
        ↓
pracovní větev → pull request → release ZIP → ruční nasazení
```

## Požadavky

Na novém počítači nainstaluj:

- [Git](https://git-scm.com/download/win)
- [Visual Studio Code](https://code.visualstudio.com/)
- [LocalWP](https://localwp.com/)
- [Node.js LTS](https://nodejs.org/) včetně npm

Composer není potřeba.

## První zprovoznění

```powershell
cd "$HOME\Documents"
git clone https://github.com/lopapex/taborovajizda.git
cd taborovajizda
code .
```

V LocalWP vytvoř web `taborovajizda-local` s prostředím **Preferred**. Obnova produkční zálohy je volitelná a má smysl jen tehdy, když potřebuješ stejný obsah a pluginy.

V kořeni repozitáře vytvoř lokální konfiguraci:

```powershell
Copy-Item .localwp.example.json .localwp.json
notepad .localwp.json
```

Nastav skutečnou cestu:

```json
{
  "localPublicPath": "C:\\Users\\<uživatel>\\Local Sites\\taborovajizda-local\\app\\public"
}
```

Potom nejdřív zobraz náhled propojení a až po kontrole jej aplikuj:

```powershell
.\scripts\connect-localwp-theme.ps1
.\scripts\connect-localwp-theme.ps1 -Apply
```

Skript zachová původní lokální šablonu jako `taborovajizda.pre-junction-<čas>`. LocalWP potom používá přímo verzovaný adresář `theme/`.

Nainstaluj build závislosti a vytvoř první CSS/JS bundle:

```powershell
cd theme
npm install
npm run build
cd ..
```

## Struktura

```text
theme/
├── app/                         PHP služby, integrace a bootstrap
├── config/season.json           aktivní ročník
├── resources/
│   ├── css/                     Tailwind, komponenty a aktuální vzhled
│   ├── ts/                      TypeScript
│   └── assets/season/           grafika aktivního ročníku
├── template-parts/
│   ├── layout/                  hlavička, patička, sidebar
│   ├── pages/                   stránky a rubriky
│   └── components/              opakované části obsahu
├── patterns/                    WordPress block patterns
├── public/build/                generovaný a ignorovaný build
├── theme.json                   WordPress design tokeny
└── style.css                    pouze povinná hlavička šablony
```

Kořenové WordPress soubory jako `header.php`, `page-kontakt.php` a `category.php` jsou záměrně krátké entrypointy. Skutečný markup je v `template-parts/`.

## Běžný vývoj

Repozitářový adresář `theme/` je s LocalWP propojený přímo pomocí junction. Soubory se proto nikam nekopírují: změna uložená ve VS Code je zároveň změnou aktivní lokální šablony.

1. Spusť web v LocalWP.
2. Ve VS Code otevři celý repozitář, například:

   ```powershell
   code "C:\Users\matej\Dropbox\PC\Documents\ChatGPT\tj web 2.0"
   ```

3. V jednom terminálu spusť:

   ```powershell
   cd theme
   npm run dev
   ```

4. Terminál s `npm run dev` nech během práce spuštěný.
5. Upravuj `theme/app`, `theme/resources` nebo `theme/template-parts`.
6. Obnov stránku v prohlížeči; pokud vidíš starou verzi, použij `Ctrl+F5`.
7. Před uložením změn do Gitu spusť:

   ```powershell
   npm run check
   ```

### Kdy je potřeba build

| Co měníš | Co udělat pro zobrazení změny |
| --- | --- |
| PHP, `app/`, `template-parts/` | Pouze obnovit stránku. |
| CSS, TypeScript nebo assety v `resources/` | Nechat běžet `npm run dev` a obnovit stránku. |
| Změna se stále neukazuje | Použít `Ctrl+F5` a případně vyčistit WordPress cache. |
| Finální kontrola před commitem | Spustit `npm run check`. |

Po zavření terminálu se vývojový build zastaví. Při příští práci stačí v `theme/` znovu spustit `npm run dev`; `npm install` se neopakuje, pokud se nezměnily závislosti nebo nebyl odstraněn `node_modules`.

Build používá Tailwind CSS 4 bez Preflightu, aby během postupné migrace neměnil stávající WordPress markup. Nové utility mají prefix `tj:`. React se nepoužívá.

`npm run dev` zůstává spuštěný a při každém uložení CSS nebo TypeScriptu automaticky obnoví `public/build/`. Potom stačí refresh prohlížeče. Změny PHP a `template-parts` build nepotřebují a projeví se hned po refreshi. Samostatné `npm run build` je potřeba až pro finální kontrolu, CI a release.

## Každoroční změna

Provozní údaje jsou pouze v `theme/config/season.json`. V templates se rok, název a termín nepíšou natvrdo. Vizuální tokeny jsou v `theme/theme.json` a aktuální roční CSS v `theme/resources/css/season.css`.

Pro nový ročník použij skill `$tj-new-season` nebo náhled skriptu:

```powershell
.\scripts\new-season.ps1 -Year 2027 -Slug novy-motiv -Title "DOPLNIT NÁZEV"
```

Skript bez `-Apply` nic nemění. Nevymýšlej nepotvrzené datum, cenu, databázový klíč ani vizuální brief. Staré ročníky jsou archivované v Git historii; nevytvářej další kopii celého ročního CSS.

## Formuláře a lokální prostředí

Přihláška a kontakt používají WordPress `admin-post.php` handlery. Staré newsletter odkazy pod `/odeslat-dotaz/` zůstávají kompatibilní.

Mimo prostředí `production` šablona neposílá skutečné formulářové e-maily ani nezapisuje formuláře do externí databáze. Lokální výsledek zapíše do PHP logu a vrátí simulovaný úspěch.

## Git workflow

- Nikdy necommituj změny šablony přímo do `main`.
- Použij větev `change/YYYY-MM-DD-<popis>` nebo `season/<rok>-<slug>`.
- Pull request a merge zůstávají ruční.
- Projektové skilly:
  - `$tj-connect-localwp` - propojení LocalWP;
  - `$tj-save-changes` - kontrola, commit a push pracovní větve;
  - `$tj-new-season` - nový ročník;
  - `$tj-create-release` - ověřený produkční ZIP;
  - `$design-taste-frontend` - audit a redesign bez generického AI vzhledu.

Společná pravidla pro Codex, Claude, Copilot a další agenty jsou v [AI-README.md](AI-README.md).

## Release a nasazení

Po merge aktualizuj čistý `main` a spusť:

```powershell
.\scripts\create-theme-release.ps1
```

Release skript exportuje přesný Git commit do dočasného adresáře, spustí `npm ci` a `npm run build` a vytvoří ZIP pouze z runtime souborů. `node_modules`, zdrojové CSS/TS a vývojová konfigurace v ZIPu nejsou.

Výstup v ignorovaném `releases/` obsahuje ZIP a manifest s commitem, tagem, velikostí, počtem položek a SHA-256.

Produkční nasazení:

1. Připrav poslední funkční ZIP nebo čerstvou rollback zálohu.
2. WordPress → Vzhled → Šablony → Instalovat → Nahrát šablonu.
3. Nahraj ZIP a potvrď nahrazení existující šablony.
4. Neaktivuj jinou šablonu.
5. Vyčisti cache a použij `Ctrl+F5`.
6. Ověř homepage, přihlášku, dokumenty, kontakt, rubriku a administraci.
