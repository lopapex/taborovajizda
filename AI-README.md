# Instrukce pro AI asistenty

Tento soubor je společný zdroj instrukcí pro Codex, Claude, GitHub Copilot a další AI agenty pracující s tímto repozitářem. Produktové vstupní soubory (`AGENTS.md`, `CLAUDE.md` a `.github/copilot-instructions.md`) na něj pouze odkazují.

## Cíl projektu

- `theme/` je jediný verzovaný zdroj vlastní WordPress šablony `taborovajizda`.
- LocalWP používá `theme/` přímo přes Windows directory junction.
- WordPress databáze, core, cizí pluginy, média a lokální konfigurace nejsou součástí Gitu.
- Produkční release je ZIP pouze se šablonou.

## Zásady pro všechny agenty

1. Neinstaluj programy, pluginy ani rozšíření bez výslovného souhlasu uživatele.
2. Když uživatel musí dokončit ruční krok v GUI, popiš jeden konkrétní krok, zastav se a čekej na jeho potvrzení. Nepředstírej, že byl dokončen.
3. Po potvrzení uživatele výsledek ověř a pokračuj až po úspěšné kontrole.
4. Před přesunem, přepsáním, odstraněním, commitem, pushem nebo produkčním nasazením ukaž zamýšlený dopad a vyžádej potvrzení odpovídající riziku.
5. Nikdy nemaž původní LocalWP šablonu. Setup skript ji ukládá jako `taborovajizda.pre-junction-<timestamp>`.
6. Neměň databázi, pluginy, `uploads`, jinou šablonu ani produkci, pokud o to uživatel výslovně nepožádá.
7. Nikdy necommituj `.localwp.json`, přihlašovací údaje, `.env`, `wp-config.php`, SQL, zálohy, logy ani obsah `uploads`.
8. Zachovej existující uživatelské změny. Nepoužívej destruktivní Git příkazy ani automatické zahazování změn.

## Setup od prázdného počítače

Postupuj po jednotlivých branách. Každou bránu nejprve ověř; pokud není splněná, dej uživateli instrukci a čekej.

### Brána 1: potřebné programy

Ověř dostupnost:

```powershell
git --version
code --version
Test-Path "$env:LOCALAPPDATA\Programs\Local\Local.exe"
```

Pokud něco chybí:

- Git: <https://git-scm.com/download/win>
- Visual Studio Code: <https://code.visualstudio.com/>
- LocalWP: <https://localwp.com/>

Požádej uživatele, aby chybějící program nainstaloval, a **čekej**. Po jeho potvrzení příkaz zopakuj.

### Brána 2: repozitář

Pokud agent ještě není uvnitř repozitáře, naveď uživatele:

```powershell
cd "$HOME\Documents"
git clone https://github.com/lopapex/taborovajizda.git
cd taborovajizda
code .
```

Ověř `git rev-parse --show-toplevel` a existenci `theme/style.css`. Pokud klonování nebo otevření provádí uživatel, čekej na potvrzení.

### Brána 3: LocalWP web

Ověř, zda existuje očekávaný public adresář. Pokud ne, požádej uživatele, aby v LocalWP vytvořil web:

- název: `taborovajizda-local`;
- prostředí: `Preferred`;
- lokální WordPress účet: libovolný.

Tento krok je v GUI; popiš ho a **čekej**, až uživatel oznámí dokončení. Potom ověř adresáře `wp-admin`, `wp-content`, `wp-includes` a soubor `wp-config.php`.

Obnova produkčního obsahu přes Backup Migration je volitelná. Nabídni ji pouze tehdy, když uživatel potřebuje stejný obsah nebo nastavení; nespouštěj ji automaticky.

### Brána 4: lokální konfigurace

Pokud chybí `.localwp.json`, vytvoř jej z `.localwp.example.json` až se souhlasem uživatele a nastav skutečný `localPublicPath`. Cestu odvoď z existujícího LocalWP webu, nevymýšlej ji.

Ověř, že `.localwp.json` je ignorovaný pomocí:

```powershell
git check-ignore -v .localwp.json
```

### Brána 5: propojení šablony

Nejdřív spusť pouze náhled:

```powershell
.\scripts\connect-localwp-theme.ps1
```

Ukaž uživateli zdroj, cíl a cestu zálohy. Před aplikací požádej o potvrzení. Potom spusť:

```powershell
.\scripts\connect-localwp-theme.ps1 -Apply
```

Ověř, že `wp-content/themes/taborovajizda` je junction na `<repo>/theme`, a načti lokální homepage. Pokud už junction existuje správně, nic neměň.

## Práce na šabloně

- Otevírej celý kořen repozitáře, nikoli pouze `theme/`.
- Upravuj pouze vlastní zdrojové soubory v `theme/`, pokud zadání nevyžaduje změnu workflow.
- Po změně ověř lokální web, `Ctrl+F5`, dotčené prvky, základní stránky a absenci PHP chyb.
- Junction znamená, že není potřeba žádné kopírování mezi Gitem a LocalWP.

## Uložení změn

1. Ukaž `git status --short` a relevantní diff.
2. Pokud jsou změny mimo zamýšlený rozsah, zastav se a upozorni uživatele.
3. Nikdy necommituj změny šablony přímo do `main`.
4. Na `main` vytvoř větev `change/YYYY-MM-DD-<slug>`; pracovní změny na ní zachovej.
5. Commituj pouze zamýšlené soubory z `theme/`.
6. Před pushem ukaž větev, commit a vzdálený repozitář a vyžádej potvrzení.
7. Pushni pracovní větev a vrať odkaz na pull request. Pull request automaticky neslučuj.

Agenti podporující projektové skilly mohou použít `$tj-save-changes`. Ostatní agenti mají provést stejné kroky standardními Git příkazy.

## Release

Release vytvářej pouze z čistého, otestovaného commitu:

```powershell
.\scripts\create-theme-release.ps1
```

Skript musí vytvořit ZIP a manifest v ignorovaném `releases/`. Ověř:

- jediný kořen ZIPu `taborovajizda/`;
- commit a případný tag;
- velikost, počet položek a SHA-256;
- že Git zůstává čistý.

Agenti podporující projektové skilly mohou použít `$tj-create-release`.

## Produkční nasazení

Produkci neměň jen proto, že release vznikl. Nejdřív připrav ZIP, manifest, rollback a kontrolní kroky. Před skutečným nahráním nebo nahrazením šablony vyžádej samostatné potvrzení uživatele.

Nasazení probíhá přes **Vzhled → Šablony → Instalovat šablonu → Nahrát šablonu**, následované nahrazením existující šablony, vyčištěním cache a produkční kontrolou. Při chybě použij poslední funkční ZIP nebo ověřenou přednasazovací zálohu.
