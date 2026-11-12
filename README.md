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

## WordPress administrace

- PROD WP admin: [https://taborovajizda.cz/65e43gerg534e/](https://taborovajizda.cz/65e43gerg534e/)
- Stage WP admin: [https://taborovajizda.cz/test/wp-admin/](https://taborovajizda.cz/test/wp-admin/)

Přístupové údaje, hesla ani obnovovací kódy se nikdy necommitují. Přehled WordPress stránek, příspěvků, rubrik a jejich vazby na PHP šablony je v [mapě WordPress obsahu](WORDPRESS-CONTENT.md).

Datový tok elektronické přihlášky, newsletteru a formulářových e-mailů popisuje [dokumentace formulářů a e-mailů](FORMS-AND-EMAILS.md).

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
├── app/                         theme, roční data, data homepage (Home.php) a formulářové handlery
├── config/season.json           aktivní ročník
├── resources/
│   ├── css/app.css              Tailwind vstup, barevné a stínové tokeny, sdílené komponenty
│   ├── css/content.css          jen WordPress/plugin obsah a legacy šablony
│   ├── css/season.css           pouze aktuální roční vzhled
│   ├── ts/                      TypeScript
│   └── assets/season/           grafika aktivního ročníku
├── template-parts/              sdílené části: hlavička/patička, dialog, pager, nadpis sekce, fakta, sítě, formulář
├── public/build/                generovaný a ignorovaný build
├── theme.json                   WordPress design tokeny
└── style.css                    pouze povinná hlavička šablony
```

Povinné WordPress soubory jako `header.php`, `page-e-prihlaska.php` a `category.php` obsahují svůj skutečný markup. Nevytvářej pro jednorázový markup další přeposílací template-part. Název šablony stránky odpovídá jejímu veřejnému slugu (`/e-prihlaska/`).

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

Build používá Tailwind CSS 4 utility-first bez Preflightu. Vlastní markup styluj přímo prefixovanými `tj:` utilitami; `@apply` nepoužívej. Sémantickou komponentní třídu přidej pouze pro stejný vzor použitý alespoň třikrát. Barvy a stíny se zapisují pouze jako tokeny (`tj:bg-sun`, `tj:border-ink/11`, `tj:shadow-card`; v CSS `var(--tj-color-…)` a `--alpha()`), hex hodnoty jen v jejich definici. Vlastní CSS proměnné mají prefix `--tj-`. Do `content.css` patří jen selektory WordPress/plugin obsahu, který nelze opatřit třídami, a legacy šablony; do `season.css` pouze roční fonty, grafika a efekty. Třídy se v PHP nesmí skládat za běhu (Tailwind je musí najít ve zdrojáku celé). React se nepoužívá.

`npm run dev` zůstává spuštěný a při každém uložení CSS nebo TypeScriptu automaticky obnoví `public/build/`. Potom stačí refresh prohlížeče. Změny PHP a `template-parts` build nepotřebují a projeví se hned po refreshi. Samostatné `npm run build` je potřeba až pro finální kontrolu, CI a release.

## Každoroční změna

Provozní údaje jsou pouze v `theme/config/season.json`. V templates se rok, název, termín ani kontaktní e-mail nepíšou natvrdo. Vizuální tokeny jsou v `theme/theme.json` a aktuální roční CSS v `theme/resources/css/season.css`.

Každý ročník se připraví ve čtyřech oddělených krocích:

1. Aktualizuj data v `config/season.json` (název, termín, cena, `location`, `ageRange`, `mapCoordinates`), včetně `contactEmail` a ručně spravovaného `registration.remainingPlaces`; `null` znamená, že počet nezobrazujeme.
2. Nahraď přesně dva soubory: `resources/assets/season/hero.jpg` a `resources/assets/season/background.jpg`.
3. Uprav potvrzené barvy v `theme.json` a pouze roční efekty v `season.css`.
4. Po potvrzení termínu nastav countdown widget ve WordPress administraci; šablona jej sama zobrazí, dokud cílový čas neuplyne.

Pro nový ročník použij skill `$tj-new-season` nebo náhled skriptu:

```powershell
.\scripts\new-season.ps1 -Year 2027 -Slug novy-motiv -Title "DOPLNIT NÁZEV"
```

Skript bez `-Apply` nic nemění. Nevymýšlej nepotvrzené datum, cenu, databázový klíč ani vizuální brief. Názvy ročních obrázků jsou pevné a nejsou součástí JSON konfigurace. Staré ročníky jsou archivované v Git historii; nevytvářej další kopii celého ročního CSS.

Odpočet není součástí šablony: vytváří jej plugin `jquery-t-countdown-widget` z konfigurace uložené ve WordPress databázi. Šablona (`app/CountdownWidget.php`) widget nevykreslí, jakmile jeho cílový čas uplyne nebo nejde přečíst, takže se nikdy nezobrazí záporný odpočet. Pro nový ročník stačí nastavit nové datum widgetu ve WordPressu.

## Nová aktualita, připnutí a test

- **Aktualita:** ve WordPressu vytvoř standardní příspěvek v rubrice `Aktuality` s datem v aktivním roce (`year` v `season.json`). Homepage ji zobrazí sama, včetně dialogu s plným textem; bez náhledového obrázku použije první fotografii z textu.
- **Připnuté oznámení:** v příspěvku zapni „Připnout na začátek blogu“ (Stick to the top). Aktualita se zobrazí první se štítkem „Důležité oznámení“; po odepnutí se vrátí na své místo podle data.
- **Test před release:** z `theme/` spusť `npm run check` (validace `season.json`, TypeScript, build), PHP lint (`php -l`) a projdi homepage a `/e-prihlaska/` v LocalWP na šířkách 360, 768 a 1280 px. Mimo produkci formuláře pouze simulují odeslání.

## Formuláře a lokální prostředí

Přihláška a kontakt používají stejné namespaced WordPress handlery a nonce jako `admin-post.php`, ale formuláře odesílají na `/odeslat-dotaz/`. Produkční bezpečnostní plugin blokuje anonymní přístup do `/wp-admin/`; kompatibilní veřejný endpoint proto požadavek předá stejnému handleru. Staré newsletter odkazy pod touto URL zůstávají funkční.

Mimo prostředí `production` šablona neposílá skutečné formulářové e-maily ani nezapisuje formuláře do externí databáze. Po úspěšné validaci lokální výsledek zapíše do PHP logu a vrátí simulovaný úspěch; chybné vstupy vrací stejné chybové kódy jako produkce.

## Git workflow

- Nikdy necommituj změny šablony přímo do `main`.
- Použij větev `change/YYYY-MM-DD-<popis>` nebo `season/<rok>-<slug>`.
- Pull request a merge zůstávají ruční.
- Projektové skilly:
  - `$tj-connect-localwp` - propojení LocalWP;
  - `$tj-save-changes` - kontrola, commit a push pracovní větve;
  - `$tj-new-season` - nový ročník;
  - `$tj-create-release` - ověřený produkční ZIP;
  - `$tj-migrate-content` - přenos obsahu z LocalWP na stage a produkci;
  - `$design-taste-frontend` - audit a redesign bez generického AI vzhledu.

Společná pravidla pro Codex, Claude, Copilot a další agenty jsou v [AI-README.md](AI-README.md).

## Release a nasazení

Po merge aktualizuj čistý `main` a spusť:

```powershell
.\scripts\create-theme-release.ps1 -SourceBranch main
```

Pro ověřovací ZIP z větve `stage` použij:

```powershell
.\scripts\create-theme-release.ps1 -SourceBranch stage
```

Release skript exportuje přesný commit explicitně zvolené lokální větve do dočasného adresáře, spustí `npm ci` a `npm run build` a vytvoří ZIP pouze z runtime souborů. `node_modules`, zdrojové CSS/TS a vývojová konfigurace v ZIPu nejsou.

Výstup v ignorovaném `releases/` obsahuje ZIP a manifest s commitem, tagem, velikostí, počtem položek a SHA-256.

Produkční nasazení:

1. Připrav poslední funkční ZIP nebo čerstvou rollback zálohu.
2. WordPress → Vzhled → Šablony → Instalovat → Nahrát šablonu.
3. Nahraj ZIP a potvrď nahrazení existující šablony.
4. Neaktivuj jinou šablonu.
5. Vyčisti cache a použij `Ctrl+F5`.
6. Ověř homepage, přihlášku, dokumenty, kontakt, rubriku a administraci.
