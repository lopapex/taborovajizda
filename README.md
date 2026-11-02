# Táborová jízda – vývoj a nasazení WordPress šablony

Tento repozitář je zdrojem pravdy pro vlastní WordPress šablonu `taborovajizda`. Git verzionuje pouze šablonu a nástroje pro vývoj. Databáze, pluginy a ostatní obsah se mezi produkcí a LocalWP přenášejí archivem pluginu Backup Migration.

Baseline je funkčně shodná s produkční šablonou. Z komentovaných, neaktivních bloků v `functions.php` byly odstraněny staré SMS a Facebook tajné hodnoty a nahrazeny odkazy na konfiguraci mimo šablonu. Generovaný editorový soubor `.tags` byl vynechán. Tajné hodnoty ani lokální metadata do Gitu nepatří.

Repozitář nikdy nesmí obsahovat hesla, `wp-config.php`, databázové exporty, produkční zálohy ani obsah `uploads`.

## Co je kde

- `theme/` – přesná kopie aktivní vlastní šablony `taborovajizda`;
- `scripts/sync-theme-to-local.ps1` – zrcadlí `theme/` pouze do `wp-content/themes/taborovajizda` zadaného LocalWP webu;
- `.gitignore` – blokuje zálohy, SQL, konfiguraci, logy a lokální soubory;
- `README.md` – tento opakovatelný postup.

Produkce: <https://taborovajizda.cz/>

Administrace: <https://taborovajizda.cz/wp-admin/>

GitHub: <https://github.com/lopapex/taborovajizda>

## 1. Instalace LocalWP

1. Stáhni LocalWP pouze z <https://localwp.com/> a dokonči instalaci.
2. Spusť LocalWP a vytvoř prázdný web s názvem `taborovajizda-local`.
3. Zvol výchozí prostředí. Lokální přihlašovací údaje mohou být libovolné; nepatří do Gitu.
4. V přehledu webu si poznamenej hodnotu **Site domain** a cestu **Site path**. WordPress public adresář je standardně:

   ```text
   C:\Users\<uživatel>\Local Sites\taborovajizda-local\app\public
   ```

Oficiální záložní způsob importu LocalWP používá ZIP s adresářem `wp-content` a SQL dumpem: <https://localwp.com/help-docs/getting-started/how-to-import-a-wordpress-site-into-local/>.

## 2. Vytvoření a stažení produkční zálohy

Tento krok se dělá v produkční administraci přes **Backup Migration**.

1. Otevři Backup Migration a zkontroluj rozsah zálohy:
   - databáze: zahrnout;
   - pluginy: zahrnout;
   - šablony: zahrnout;
   - ostatní obsah `wp-content`: zahrnout;
   - `uploads`: nezahrnovat;
   - WordPress core: nezahrnovat.
2. Vytvoř novou ruční zálohu.
3. Po dokončení stáhni archiv mimo tento repozitář, například do samostatného adresáře Dokumenty/Zálohy.
4. Poznamenej si datum, čas a přesný název archivu.

Archiv necommituj. Nezahrnuje externí databázi přihlášek a newsletteru a žádný krok tohoto postupu ji nemění.

## 3. Obnova produkční kopie v LocalWP

1. Otevři administraci `taborovajizda-local`.
2. Nainstaluj a aktivuj plugin **Backup Migration**.
3. V pluginu nahraj stažený produkční archiv a spusť obnovu.
4. Po obnově se případně znovu přihlas produkčním WordPress účtem, protože byla obnovena produkční databáze.
5. Ověř **Nastavení → Obecné** a otevři úvodní stránku.

Pokud plugin nepřepíše produkční URL na lokální doménu, klikni v LocalWP na **Open site shell** a nejprve spusť zkušební náhradu:

```bash
wp search-replace 'https://taborovajizda.cz' 'http://taborovajizda-local.local' --all-tables --precise --skip-columns=guid --dry-run
```

Doménu v příkazu nahraď přesnou hodnotou **Site domain** z LocalWP. Jestli zkušební výpis vypadá správně, spusť stejný příkaz bez `--dry-run`:

```bash
wp search-replace 'https://taborovajizda.cz' 'http://taborovajizda-local.local' --all-tables --precise --skip-columns=guid
wp cache flush
```

Protože záloha neobsahuje `uploads`, některá lokální média mohou chybět. Pro vývoj a test odpočtu to není chyba.

## 4. První import produkční šablony do Gitu

Tento krok se provádí pouze při založení repozitáře.

1. Z obnoveného LocalWP webu zkopíruj celý adresář:

   ```text
   <LocalPublicPath>\wp-content\themes\taborovajizda
   ```

   do adresáře `theme/` v kořeni tohoto repozitáře.
2. Ověř, že `theme/style.css` existuje a že šablona v LocalWP je aktivní.
3. Zkontroluj, že Git neobsahuje zálohu, SQL, `wp-config.php` ani osobní údaje:

   ```powershell
   git status --short
   git check-ignore -v cesta-k-souboru
   ```

4. Založ baseline:

   ```powershell
   git branch -M main
   git remote add origin https://github.com/lopapex/taborovajizda.git
   git add .gitignore README.md scripts theme
   git commit -m "Import current production theme"
   git tag -a baseline-production-2026-11-02 -m "Production theme baseline 2026-11-02"
   git push -u origin main
   git push origin baseline-production-2026-11-02
   ```

Datum v názvu tagu vždy nahraď skutečným datem importu ve formátu `YYYY-MM-DD`.

## 5. Běžný vývojový postup

Každou změnu dělej na samostatné větvi. Pro demo se používá:

```powershell
git switch main
git pull --ff-only
git switch -c demo/hide-expired-countdown
```

Upravuj soubory v `theme/`, ne kopii uvnitř LocalWP. Potom šablonu synchronizuj:

```powershell
.\scripts\sync-theme-to-local.ps1 -LocalPublicPath "C:\Users\<uživatel>\Local Sites\taborovajizda-local\app\public"
```

Skript ověří, že zadaná cesta je WordPress, a zrcadlí pouze adresář `wp-content/themes/taborovajizda`. Přepínač `-WhatIf` ukáže cíl bez kopírování:

```powershell
.\scripts\sync-theme-to-local.ps1 -LocalPublicPath "C:\Users\<uživatel>\Local Sites\taborovajizda-local\app\public" -WhatIf
```

Po synchronizaci vyčisti cache a tvrdě obnov prohlížeč:

```bash
wp cache flush
```

Demo změna v `theme/style.css` je:

```css
/* Temporarily hidden until the 2027 camp date is confirmed. */
#taborovajizda_body #countdowntimer-2 {
    display: none !important;
}
```

Lokálně ověř:

- před změnou je `#countdowntimer-2` viditelný;
- po změně má `display: none`;
- sociální odkazy, blok Tábor 2026, video a nejnovější příspěvky zůstaly viditelné;
- úvodní stránka, přihláška, dokumenty, kontakt a jedna rubrika se načtou bez PHP chyby;
- administrace funguje.

Po ověření změnu ulož:

```powershell
git add theme/style.css
git commit -m "Hide expired countdown"
git push -u origin demo/hide-expired-countdown
```

Na GitHubu vytvoř pull request do `main`, zkontroluj jedinou zamýšlenou CSS změnu a pull request sluč. Potom lokálně:

```powershell
git switch main
git pull --ff-only
git tag -a release-2026-11-02-hide-countdown -m "Hide expired countdown"
git push origin release-2026-11-02-hide-countdown
git rev-parse HEAD
```

Datum tagu nahraď skutečným datem vydání.

## 6. Příprava release archivu

Produkční archiv vždy vytvářej z čerstvé produkční kopie, aby release nepřepsal novější obsah starou lokální databází.

1. Domluv krátké okno bez editace WordPressu.
2. Na začátku okna v produkčním Backup Migration vytvoř a stáhni čerstvý archiv. Toto je nouzová rollback záloha.
3. V LocalWP vytvoř samostatný prázdný web `taborovajizda-release`.
4. Do něj přes Backup Migration obnov právě staženou rollback zálohu.
5. Přepni repozitář přesně na release tag a synchronizuj šablonu:

   ```powershell
   git switch --detach release-2026-11-02-hide-countdown
   .\scripts\sync-theme-to-local.ps1 -LocalPublicPath "C:\Users\<uživatel>\Local Sites\taborovajizda-release\app\public"
   ```

6. V release kopii vyčisti cache a projdi kontrolní checklist níže.
7. V lokálním Backup Migration vytvoř nový archiv se stejným rozsahem: databáze, pluginy, šablony a ostatní `wp-content`; bez `uploads` a WordPress core.
8. Stáhni release archiv mimo Git a spočítej SHA-256:

   ```powershell
   Get-FileHash -Algorithm SHA256 "C:\cesta\k\release-archivu.zip"
   git rev-parse release-2026-11-02-hide-countdown^{commit}
   ```

9. Do záznamu vydání ulož:
   - přesný název rollback archivu;
   - přesný název release archivu;
   - Git tag;
   - commit SHA;
   - SHA-256 release archivu;
   - datum a osobu, která nasazení provedla.
10. Vrať repozitář na větev `main`:

   ```powershell
   git switch main
   ```

## 7. Produkční nasazení

Před kliknutím na **Obnovit** musí jiná osoba nebo samostatná finální kontrola potvrdit správný archiv, tag, commit SHA, SHA-256 a dostupnost rollback zálohy.

1. V produkční administraci otevři Backup Migration.
2. Nahraj ověřený release archiv.
3. Ještě nespouštěj obnovu. Zkontroluj název a velikost nahraného souboru.
4. Zaznamenej počty příspěvků, stránek, médií a uživatelů před obnovou.
5. Po finálním potvrzení spusť obnovu.
6. Po dokončení se případně znovu přihlas, vyčisti všechny dostupné WordPress/cache vrstvy a proveď tvrdé obnovení stránky.
7. Projdi kontrolní checklist. Pokud je vše v pořádku, ukonči provozní okno.

Automatický deploy z Gitu není součástí tohoto postupu. Současný server nemá samostatný omezený SSH účet určený pouze pro tento web.

## 8. Kontrolní checklist

### Před produkční obnovou

- [ ] Krátké okno bez editace bylo oznámeno a zahájeno.
- [ ] Čerstvá rollback záloha je dokončená, stažená a dohledatelná podle názvu a data.
- [ ] Release kopie vychází z této čerstvé produkční zálohy.
- [ ] Nasazuje se správný release tag a commit SHA.
- [ ] SHA-256 staženého release archivu odpovídá záznamu.
- [ ] Release archiv neobsahuje `uploads` ani WordPress core.
- [ ] Lokální checklist prošel.
- [ ] Proběhlo samostatné finální potvrzení před kliknutím na Obnovit.

### Po obnově

- [ ] Produkční web i administrace používají HTTPS a načtou se bez PHP chyby.
- [ ] `#countdowntimer-2` zůstane skrytý i po tvrdém obnovení stránky.
- [ ] Sociální odkazy zůstaly viditelné.
- [ ] Blok Tábor 2026 zůstal viditelný.
- [ ] Video zůstalo viditelné.
- [ ] Nejnovější příspěvky zůstaly viditelné.
- [ ] Úvodní stránka se načte.
- [ ] Přihláška se načte.
- [ ] Dokumenty se načtou.
- [ ] Kontakt se načte.
- [ ] Jedna rubrika a jeden příspěvek se načtou.
- [ ] Počty příspěvků, stránek, médií a uživatelů před a po obnově souhlasí.
- [ ] Produkční administrace a Backup Migration zůstaly dostupné.
- [ ] Nebyla změněna externí databáze přihlášek a newsletteru.
- [ ] Výsledek a čas nasazení jsou zaznamenané.

## 9. Nouzový rollback

Rollback se provádí pouze při neúspěšné kontrole po nasazení.

1. Zastav editaci webu a poznamenej chybu i čas jejího zjištění.
2. V produkčním Backup Migration nahraj čerstvý přednasazovací rollback archiv.
3. Ověř jeho přesný název a datum.
4. Spusť obnovu.
5. Vyčisti cache a znovu projdi celý checklist.
6. Uchovej chybný release archiv i jeho záznam pro diagnostiku; necommituj je do Gitu.

Pokud není administrace dostupná, další zásah musí provést správce serveru. Současný SSH přístup není bezpečně oddělen pouze pro tento web.

## 10. Pozdější zapnutí odpočtu pro rok 2027

Zapnutí vyžaduje dvě navazující změny:

1. Ve WordPress widgetu T(-) Countdown nastav potvrzené datum a čas tábora 2027.
2. Na nové Git větvi odstraň z `theme/style.css` pravidlo, které skrývá `#countdowntimer-2`.

Potom zopakuj lokální kontrolu, pull request, release tag, přípravu release archivu a produkční nasazení z tohoto README. Samotné odstranění CSS bez nastavení data by zobrazilo chybný nebo prošlý odpočet.
