# Táborová jízda – vývoj webu

Tento repozitář obsahuje vlastní WordPress šablonu a nástroje pro lokální vývoj.

```text
repozitář/theme ← propojení → LocalWP/wp-content/themes/taborovajizda
        ↓
pracovní větev → GitHub → release ZIP → produkční WordPress
```

LocalWP a Git používají stejné soubory. Změny se mezi nimi nekopírují. Databáze, pluginy, média, zálohy, hesla a lokální konfigurace se do Gitu neposílají.

## 1. Instalace programů

Na novém počítači nainstaluj:

- [Git](https://git-scm.com/download/win)
- [Visual Studio Code](https://code.visualstudio.com/)
- [LocalWP](https://localwp.com/)

## 2. Stažení projektu

Otevři PowerShell a spusť:

```powershell
cd "$HOME\Documents"
git clone https://github.com/lopapex/taborovajizda.git
cd taborovajizda
code .
```

Ve VS Code otevírej celý adresář `taborovajizda`, nikoliv pouze `theme/`:

```text
taborovajizda/          ← kořen Git projektu
├── .agents/skills/     ← projektové skilly
├── scripts/            ← pomocné skripty
├── theme/              ← soubory WordPress šablony
└── README.md
```

Soubory webu upravuj v `theme/`.

## 3. Vytvoření lokálního WordPressu

V LocalWP:

1. Klikni na `+` a vyber **Create a new site**.
2. Web pojmenuj `taborovajizda-local`.
3. Vyber prostředí **Preferred**.
4. Nastav libovolný lokální účet WordPressu.
5. Dokonči vytvoření a web spusť.

Public adresář bude obvykle zde:

```text
C:\Users\<uživatel>\Local Sites\taborovajizda-local\app\public
```

### Volitelně: kopie produkčního obsahu

Pokud potřebuješ stejné stránky, příspěvky, pluginy a nastavení jako na produkci:

1. Na produkci vytvoř zálohu přes plugin **Backup Migration**.
2. Stejný plugin nainstaluj v lokálním WordPressu.
3. Zálohu obnov do `taborovajizda-local`.

Zálohu nikdy nevkládej do repozitáře. Pro samotnou práci na šabloně tento krok není povinný.

## 4. Nastavení cesty k LocalWP

V kořeni repozitáře spusť:

```powershell
Copy-Item .localwp.example.json .localwp.json
notepad .localwp.json
```

Do souboru vlož skutečnou cestu k public adresáři:

```json
{
  "localPublicPath": "C:\\Users\\TVOJE_JMENO\\Local Sites\\taborovajizda-local\\app\\public"
}
```

`.localwp.json` je lokální nastavení a Git ho ignoruje.

## 5. Propojení Gitu s LocalWP

Nejjednodušší je v Codexu napsat:

```text
Použij $tj-connect-localwp a propoj tento projekt s LocalWP.
```

Skill:

1. ověří `.localwp.json` a instalaci WordPressu;
2. ukáže zdroj, cíl a plánovanou zálohu;
3. před změnou si vyžádá potvrzení;
4. původní lokální šablonu přesune do `taborovajizda.pre-junction-YYYYMMDD-HHMMSS`;
5. vytvoří Windows junction:

   ```text
   LocalWP\wp-content\themes\taborovajizda → repozitář\theme
   ```

6. ověří, že lokální web funguje.

Stejnou operaci lze provést ručně:

```powershell
.\scripts\connect-localwp-theme.ps1          # pouze náhled
.\scripts\connect-localwp-theme.ps1 -Apply   # vytvoření propojení
```

Skript původní šablonu nemaže. Opakované spuštění pouze ověří existující propojení.

## 6. Běžná práce

1. Spusť web v LocalWP.
2. Ve VS Code otevři celý repozitář pomocí `code .`.
3. Upravuj soubory v `theme/`.
4. V prohlížeči použij `Ctrl+F5`.
5. Změny uvidíš ve VS Code v panelu **Source Control**.

Protože LocalWP používá přímo `theme/`, každá uložená změna je okamžitě vidět lokálně a zároveň ji sleduje Git.

### Lokální kontrola

Před uložením změn ověř:

- úvodní stránku;
- přihlášku, dokumenty a kontakt;
- jednu rubriku a jeden příspěvek;
- všechny prvky dotčené změnou;
- že administrace funguje a nikde není PHP chyba.

## 7. Uložení změn na GitHub

V Codexu napiš:

```text
Použij $tj-save-changes a ulož moje změny.
```

Skill:

1. ukáže změněné soubory a diff;
2. odmítne nesouvisející změny mimo `theme/`;
3. vytvoří pracovní větev `change/YYYY-MM-DD-popis`;
4. před commitem a odesláním si vyžádá potvrzení;
5. commitne pouze šablonu a pushne pracovní větev;
6. vrátí odkaz pro vytvoření pull requestu.

Pull request zkontroluj a sluč do `main` ručně.

## 8. Vytvoření release ZIPu

Po sloučení pull requestu aktualizuj `main`:

```powershell
git switch main
git pull --ff-only
```

Potom v Codexu napiš:

```text
Použij $tj-create-release a vytvoř produkční ZIP.
```

Nebo spusť přímo:

```powershell
.\scripts\create-theme-release.ps1
```

Skill vyžaduje čistý Git a v ignorované složce `releases/` vytvoří:

- `taborovajizda-theme-YYYY-MM-DD-<commit>.zip`;
- manifest s commitem, tagem, velikostí, SHA-256 a návodem k nasazení.

ZIP vzniká pouze z `theme/` a obsahuje jediný kořenový adresář `taborovajizda/`.

## 9. Ruční nasazení na produkci

1. Uchovej poslední funkční ZIP šablony nebo čerstvou rollback zálohu.
2. Ve WordPressu otevři **Vzhled → Šablony → Instalovat šablonu → Nahrát šablonu**.
3. Vyber ZIP ze složky `releases/` a klikni na **Instalovat**.
4. Zvol nahrazení existující šablony novou nahranou verzí.
5. Neaktivuj jinou šablonu; `taborovajizda` má zůstat aktivní.
6. Vyčisti cache a použij `Ctrl+F5`.
7. Zopakuj lokální kontrolní body na produkci.

Při chybě nahraj poslední funkční ZIP stejným postupem nebo obnov ověřenou přednasazovací zálohu.
