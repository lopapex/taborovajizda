# Redesign 2027 — implementační plán

Tento dokument převádí produktové zadání redesignu do postupně odškrtávaného plánu pro tento konkrétní repozitář. Je určený pro Codex, Claude, Copilot i další AI agenty.

> Stav při založení: větev `chore/resedign-2027`, výchozí commit větve `stage` je `97e7a0a`. Produkce, stage WordPress, databáze ani obsah se v této přípravné změně nemění.

## Jak s plánem pracovat

1. Před prací přečti `AGENTS.md`, `AI-README.md`, tento dokument a relevantní část `WORDPRESS-CONTENT.md`.
2. Pracuj po jedné malé, testovatelné položce nebo ucelené skupině položek.
3. Před změnou napiš stručný dopad a seznam dotčených souborů.
4. Po ověření změň příslušné `[ ]` na `[x]` a pod položku doplň stručnou poznámku s výsledkem.
5. Neoznačuj položku za hotovou pouze podle implementace; musí projít uvedené kontroly.
6. Neměň přihlašovací backend, produkční databázi, pluginy ani veřejné URL bez samostatného plánu a potvrzení.
7. Každou větší fázi ukonči samostatným checkpoint commitem až po výslovném potvrzení uživatele.

## Potvrzená technologická rozhodnutí

### WordPress a rendering

- Zachovat současnou lehkou hybridní WordPress šablonu, PHP entrypointy, Tailwind CSS 4, TypeScript a Vite.
- Zachovat serverový první render. JavaScript pouze vylepšuje filtr, načítání, menu, sticky CTA, dialogy a animace.
- Nevytvářet React SPA, Next.js aplikaci, nový backend ani Composer vrstvu.
- Standardní WordPress příspěvky zůstávají zdrojem aktualit a jednotlivé články si zachovají indexovatelné URL.
- `season.json` je zatím zdrojem provozních údajů aktivního ročníku. Přesun správy ročníků do WordPressu je samostatná migrační fáze, nikoli předpoklad prvního vizuálního kroku.

### Shadcn/ui

**Rozhodnutí: neinstalovat Shadcn/ui jako závislost ani nepřenášet jeho React komponenty.**

[Shadcn/ui](https://ui.shadcn.com/docs) je otevřený distribuční systém komponent, který poskytuje zdrojový kód pro vlastní komponentní knihovnu. Je hodnotný jako reference pro skladbu, stavy a přístupnost prvků jako Accordion, Dialog, Sheet, Navigation Menu nebo Form. Tento projekt ale renderuje vlastní PHP markup a nemá React runtime. Převzetí Shadcn by přineslo React a paralelní komponentní vrstvu bez odpovídajícího přínosu.

Pro tento web je vhodnější:

- vlastní malé PHP/template komponenty se současnými `tj:` Tailwind utilitami;
- nativní prvky (`button`, `dialog`, `details/summary`) tam, kde dávají smysl;
- WAI-ARIA chování pouze tam, kde nativní HTML nestačí;
- Shadcn používat jako kontrolní UX seznam, nikoli jako zdroj vzhledu nebo závislost.

### Animace

**Rozhodnutí: nepřidávat Framer Motion ani Motion for React.**

Framer Motion se dnes distribuuje jako [Motion for React](https://motion.dev/docs/react-installation) a vyžaduje React. Pro tento PHP/TypeScript web použijeme následující pořadí:

1. CSS transitions/keyframes pro hover, focus, jednoduché vstupy a rozbalení;
2. nativní `IntersectionObserver` pro sticky CTA a detekci viewportu;
3. Web Animations API pro jednoduché řízené sekvence;
4. až při prokázané potřebě malý frameworkově nezávislý balíček [`motion`](https://motion.dev/docs/quick-start), který umí čistý JavaScript a jeho `inView` staví na IntersectionObserveru.

Před případným přidáním `motion` musí konkrétní interakce doložit, že CSS/nativní API nestačí. Vždy respektovat `prefers-reduced-motion`, animovat primárně `transform` a `opacity` a nepodmiňovat obsahem animaci.

## Cílová informační architektura

```text
/                       one-page prezentace
  #aktuality             nejnovější příspěvky
  #o-tabore              představení a atmosféra
  #lide                  tým (profily v oknech, listování fotkami)
  #program               aktivity ve čtyřech skupinách
  #minule-rocniky        carousel minulých ročníků
  #pro-rodice            praktické informace, FAQ a dokumenty
  #kontakt               kontakt a sociální sítě

/minule-rocniky/         archiv ročníků a souvisejících příspěvků
/e-prihlaska/            samostatná existující přihláška
/stávající-permalinky/   zachované články, rubriky a dokumenty
```

## Fáze 0 — bezpečný výchozí stav

- [x] Ověřit čistotu větve a zaznamenat výchozí commit.
  - Ověřeno 10. 11. 2026: větev `chore/resedign-2027`, výchozí commit `97e7a0a` (`feat: add contact email functionality and update related documentation`). Jedinou změnou pracovního stromu je tento plán `REDESIGN-2027.md`; žádné nečekané změny ve šabloně nejsou.
- [x] Spustit `npm run check`, PHP syntax a existující PHP testy.
  - `npm run check` a PHP syntax prošly 10. 11. 2026 s PHP 8.2.29 z LocalWP. Dva neintegrované PHPUnit testy byly po potvrzení odstraněny, protože téma nemá testovací runner.
- [x] Pořídit referenční screenshoty desktop/mobil: homepage, přihláška, kontakt, dokumenty, rubrika, příspěvek a menu.
  - Ověřeno 10. 11. 2026 na `taborovajizda-local.local`: desktop i 360px mobil pro `/`, `/e-prihlaska/`, `/kontakt-2/`, `/dokumenty/`, `/category/aktuality/` a `/tabor-2026-navrat-domu/`. Mobilní menu se otevře a zavře se správnou hodnotou `aria-expanded`.
- [x] Ověřit junction do LocalWP a funkční lokální obsah bez zásahu do produkce.
  - Ověřeno diagnosticky bez `-Apply`: `taborovajizda-local` používá junction `wp-content/themes/taborovajizda` přímo na `theme/` v tomto repozitáři. Lokální homepage se vykresluje na `http://taborovajizda-local.local/`; produkce ani databáze nebyly změněny.
- [x] Potvrdit, že LocalWP/stage neposílá e-maily ani nezapisuje do externí produkční DB.
  - Ověřeno 10. 11. 2026: lokální `wp-config.php` nastavuje `WP_ENVIRONMENT_TYPE` na `local`. Formulářová větev v tomto prostředí ukončí běh v `simulateLocally()` před vytvořením `ExternalDatabase`; `pre_wp_mail` současně potlačuje skutečné odeslání.
- [x] Sepsat seznam aktivních pluginů a rozdělit je na runtime závislosti, obsahové pluginy a kandidáty k odstranění.
  - Inventura 24 aktivních pluginů a jednoho neaktivního pluginu je zapsána ve `WORDPRESS-CONTENT.md`. Neproběhla žádná deaktivace ani změna konfigurace.

## Fáze A — audit obsahu a migrace

- [x] Porovnat sitemap a `WORDPRESS-CONTENT.md`; aktualizovat úplný seznam veřejných URL.
  - Ověřeno 10. 11. 2026 na lokální sitemapě: 13 stránek, 152 publikovaných příspěvků a 12 rubrik. `WORDPRESS-CONTENT.md` nyní uvádí tři autoritativní sitemapové zdroje, které tvoří úplný inventář URL pro regresní kontrolu.
- [x] Inventarizovat stránky, rubriky, příspěvky, menu, widgety, dokumenty, plakáty, trailery a galerie.
  - Inventář je v `CONTENT-MIGRATION-2027.md`; vychází z lokální sitemap, WordPress menu, widgetů a aktivních pluginů.
- [x] U každé současné stránky rozhodnout: sekce homepage / archiv / zachovaná samostatná URL / 301 redirect.
  - Všechny URL zůstávají dostupné. Homepage znovu použije obsah jako sekce, archiv zůstává na `/minule-rocniky/` a žádný 301 redirect není navržen.
- [x] Zmapovat všechny interní odkazy na stránky, které mohou být přesunuty.
  - Mapování menu, kotvových cílů a explicitních PHP odkazů je v `CONTENT-MIGRATION-2027.md`; stávající URL se nemění.
- [x] Rozlišit roční provozní obsah od globálních informací.
  - Aktivní roční data zůstávají v `season.json`; globální a historický obsah zůstává ve WordPressu.
- [x] Navrhnout mapování historických článků na ročníky; neodvozovat ročník pouze z data publikace.
  - Použijí se výhradně explicitní roční rubriky; nezařazené a nejednoznačné položky vyžadují ruční rozhodnutí.
- [x] Zvolit způsob evidence ročníků v administraci až po inventuře pluginů: vlastní taxonomie a term meta, ACF nebo minimální vlastní řešení.
  - Pro první vizuální krok zůstává zdrojem aktivního ročníku `season.json`; CMS model se nyní nepřidává.
- [x] Připravit migrační a rollback plán bez změn produkčních dat.
  - Postup pro budoucí migraci a rollback je v `CONTENT-MIGRATION-2027.md`; neprovádí se žádná změna databáze.

## Fáze B — design systém

- [x] Připravit schválený mobilní a desktopový wireframe homepage.
  - Wireframe a interakční pravidla jsou ve `WIREFRAME-2027.md`; návrh byl schválen 10. 11. 2026.
- [x] Připravit schválený mobilní a desktopový wireframe archivu ročníků.
  - Wireframe archivu popisuje samostatnou URL, responsivní filtr a odložené načítání médií; návrh byl schválen 10. 11. 2026.
- [x] Definovat dlouhodobé tokeny v `theme.json`: barvy, typografie, spacing, radius, stíny a šířky obsahu.
  - Implementováno 10. 11. 2026: noční modrá paleta a jantarový akcent vycházející z plamene v logu, doplněné škály písma a rozestupů, radius, stín a obsahové šířky. `npm run check` prošel.
- [x] Definovat jeden volitelný roční akcent s ověřeným kontrastem.
  - Rok 2026 používá v `season.css` oranžový `--tj-season-accent` pouze pro sezónní kontext; tmavý text na něm má ověřený kontrast WCAG AA.
- [x] Navrhnout komponenty: Button, SectionHeading, InfoChip, PostCard, TrailerPreview, YearFilter, Accordion, StickyCTA a QuestionForm.
  - Rozhraní, prahy opakovaného použití a pravidla implementace jsou ve `WIREFRAME-2027.md`.
- [x] Implementovat pouze vzory použité alespoň třikrát jako sdílenou komponentní třídu; jednorázové layouty ponechat v Tailwind utilitách.
  - Uzavřeno 11. 11. 2026: sdílené třídy mají jen opakované vzory (karta, pilulka odkazu, meta, nadpis sekce, tečky pageru, dialog); jednorázové layouty zůstaly v Tailwind utilitách. Přihláška a kontakt používají stejné sdílené prvky.
  - Průběžně 11. 11. 2026: schválený design pass (`DESIGN-AUDIT-2027.md`) zavedl sdílené tokeny tvarů (`--tj-radius-card`, `--tj-radius-control`), tichý styl metadat `tj-home-meta` a jednotné karty. Bod zůstává otevřený, dokud nebudou opakované vzory (karta, tlačítko, nadpis sekce) zrevidované i mimo homepage.
- [x] Zajistit viditelný focus, touch targety, sémantické nadpisy a reduced-motion základ.
  - Ověřeno 11. 11. 2026: globální `:focus-visible` (3px akcent), minimální výška 44 px pro tlačítka, ovládací prvky a odkazy menu, `prefers-reduced-motion` vypíná přechody i plynulý scroll. Homepage má jedno `h1` a pořadí nadpisů H1 → H2 → H3 bez přeskoků.

## Fáze C — homepage

### Navigace

- [x] Vytvořit sticky hlavičku s kotvami a odkazy na archiv a přihlášku.
  - Homepage má sticky navigaci pro sekce O táboře, Aktuality, Program, Pro rodiče, Minulé ročníky a Kontakt; přihláška zůstává výrazná samostatná akce, pokud ročník přijímá přihlášky. Položka Dokumenty zmizela 11. 11. 2026 po sloučení s Pro rodiče; kotva `#dokumenty` dál vede na podsekci dokumentů.
- [x] Na podstránkách směrovat kotvy na `/#sekce`.
  - Nová hlavička přihlášky vede odkazy na sekce přímo na homepage.
- [x] Implementovat přístupné mobilní menu, zavření po volbě a správný offset kotvy.
  - Mobilní menu používá `aria-expanded`, po volbě odkazu se zavře a kotvy respektují výšku sticky hlavičky.
- [x] Zachovat funkčnost bez JavaScriptu.
  - Ověřeno 11. 11. 2026 (360 px, simulace bez třídy `js`): `<head>` přidá třídu `js` inline skriptem. Bez ní se mobilní menu zobrazí jako seznam odkazů a skryjí se tlačítka carouselu a „Zobrazit všechny ročníky“. Záložky ukazují všechny panely. Otevírače aktualit a profilů jsou odkazy na permalink článku; s JS otevřou dialog a stránka zůstane na `/`. Bez vodorovného přetečení.

### Hero aktuálního ročníku

- [x] Vytvořit dominantní hero s plakátem, názvem, anotací, termínem, cenou, věkem a místem.
  - Ověřeno 11. 11. 2026: hero používá roční `hero.jpg`, název, anotaci a sémantický seznam údajů `dl`. `season.json` má nová volitelná pole `location` a `ageRange` (schema, PHP validace i `validate-season.mjs`); místo „Plátěná osada, Splzov“ a věk 7–17 let odpovídají stávajícímu obsahu webu. Desktop i 360 px bez vodorovného přetečení.
- [x] CTA odvodit ze stavu přihlášek; nikdy nenabízet přihlášení na skončený nebo uzavřený ročník.
  - `Season::acceptsRegistrations()` nabízí přihlášku pouze ve stavu `open` (nebo `full` jako náhradník) a jen do `endDate` včetně. Jinak hero nabízí „Poznat tábor“ a „Minulé ročníky“ a v hlavičce se skryje tlačítko Přihláška. Aktuálně se zobrazuje „Ročník 2026 proběhl“, protože `endDate` 28. 8. 2026 už minul, i když `season.json` stále uvádí `open`. 12 stavových kontrol prošlo přes PHP 8.2.
- [x] Trailer načíst až po akci uživatele, bez autoplay se zvukem.
  - Místo v hero je odkaz na Google Mapy (nové okno, hover a focus v akcentové barvě, čtečkám oznámí otevření map). Trailer aktivního roku se bere z `archive-seasons.json`; iframe vznikne až po kliknutí na náhled. Bez JavaScriptu zůstává odkaz na YouTube.
- [x] Ošetřit nepotvrzený termín, cenu, chybějící plakát a chybějící trailer.
  - Termín bez dat zobrazí „Termín připravujeme“, cena `null` zobrazí „Upřesníme“ (u skončeného ročníku se nezobrazuje), nevyplněné místo a věk se vynechají, bez traileru se hero vykreslí v jednom sloupci a chybějící `hero.jpg` zastaví build v `validate-season.mjs`.
- [x] Odstranit možnost záporného countdownu; při skončeném termínu zobrazit neutrální stav.
  - Ověřeno 11. 11. 2026: lokální widget `countdowntimer-2` měl cíl 15. 8. 2026 08:15 a vykresloval „-89 dní“, jen skrytý přes CSS. `app/CountdownWidget.php` jej přes `widget_display_callback` nevykreslí, jakmile cílový čas uplyne nebo nejde přečíst (stejné fallbacky `t` / `year` / `date` jako plugin; 5 kontrol parseru prošlo). CSS skrytí ze `season.css` je odstraněné, neutrální informační widget ročníku zůstává. AI-README, README a `$tj-new-season` popisují nový postup. Databáze se neměnila.
- [x] Optimalizovat LCP plakát bez ořezu důležité grafiky.
  - Uzavřeno 11. 11. 2026: hotov preload `hero.jpg` s `fetchpriority=high`. Ořez banneru 1536×512 na mobilu je přijatý kompromis do dodání skutečného plakátu; důležitá grafika (název, budova) zůstává ve středu výřezu.
  - Hotová část: homepage v `<head>` preloaduje roční `hero.jpg` z Vite manifestu s `fetchpriority="high"`. Lokálně se obrázek stahuje souběžně se stylesheetem, ne zhruba 650 ms po něm. Zbývá: `hero.jpg` je banner 1536×512, takže ho `background-size: cover` na mobilu ořízne. Bez ořezu to půjde až se skutečným plakátem nebo schváleným mobilním výřezem.

### Sticky CTA

- [x] Sticky CTA se nyní neimplementuje: primární přihláška je už trvale dostupná v navigaci a duplicitní akce by zbytečně překrývala obsah.
  - Uzavřeno 11. 11. 2026: rozhodnuto neimplementovat; přihláška je trvale v navigaci.
- [x] Ověřit, že nepřekrývá obsah, menu, formulář, klávesnici ani cookie banner.
  - Uzavřeno 11. 11. 2026: nepoužitelné, sticky CTA neexistuje, takže nemá co překrývat.

### Aktuality

- [x] Umístit aktuality bezprostředně pod hero.
  - Ověřeno 11. 11. 2026: pořadí sekcí je hero, Aktuality, O táboře, Program, Pro rodiče, Minulé ročníky, Kontakt. Navigace má stejné pořadí.
- [x] Zobrazit připnuté oznámení pouze při existenci odpovídajícího metadata.
  - Uzavřeno 11. 11. 2026: použito nativní „Připnout na začátek blogu“ WordPressu. Připnutá aktualita se řadí první se štítkem „Důležité oznámení“; bez připnutí se nezobrazuje nic. Ověřeno lokálním připnutím a odepnutím.
- [x] Serverově vykreslit aktuality aktivního táborového roku s obrázkem, datem a excerptem; u každé zobrazit celý obsah v přístupném modálním okně.
  - Homepage načítá příspěvky z rubriky Aktuality publikované v aktivním roce ze `season.json`; nyní 2026. Karty jsou v ovladatelném horizontálním carouselu a detail zůstává v modálním okně bez viditelného přesměrování na samostatnou stránku.
- [x] Nevyžadovat další načítání ani stránkování, protože carousel obsahuje všechny aktuality aktivního roku.

### Obsahové sekce

- [x] Vytvořit `#o-tabore` z existujícího obsahu bez ztráty informací.
  - Stávající obsah statické homepage je vykreslen do sekce `#o-tabore`.
- [x] Vytvořit `#program` pro aktivity, prostředí a lidi.
  - Homepage serverově vykresluje všechny položky z rubriky Aktivity do přístupných záložek a lidi do carouselu s modálními profily. Od 11. 11. 2026 je 14 aktivit sloučeno podle slugu do 4 schválených záložek: Program a hra, Výpravy a sport, Tábornictví a Večery a tvoření. Každá aktivita má v záložce mezititulek `h3`. Nezařazené nové aktivity spadnou automaticky do záložky „Další aktivity“. Galerie uvnitř aktivit jsou přehledné responsivní mřížky bez zbytečného vodorovného scrollování. Obsah zůstává na homepage.
- [x] Vytvořit `#pro-rodice` s praktickými informacemi, dokumenty a přístupným FAQ.
  - Od 11. 11. 2026 bez záložek: sekce Pro rodiče zobrazuje přímo obsah stránky Pro rodiče a pod ním podsekci „Dokumenty ke stažení“ (`#dokumenty`). FAQ jsou nativní `details/summary` z Genesis Blocks s opraveným kontrastem. Samostatné URL `/informace-pro-rodice/` a `/dokumenty/` zůstávají.
- [x] Vytvořit kompaktní video-first upoutávku 2–4 minulých ročníků.
  - Od 11. 11. 2026 je to samostatná sekce `#minule-rocniky`: 4 karty s náhledem a tlačítko „Zobrazit všechny ročníky (12) / Zobrazit méně“. Karta otevře modal ročníku s trailerem (iframe až po kliknutí, po zavření se zastaví) a až 6 odkazy na ověřené články. Bez JS vede karta na `/minule-rocniky/?rok=YYYY`; URL archivu zůstává, homepage na ni ale už primárně neodkazuje.
- [x] Vytvořit `#kontakt` s kontakty a sociálními sítěmi.
  - Homepage zobrazuje ověřené kontaktní údaje, telefon, e-mail, YouTube, Instagram a sdílený dotazový formulář.
- [x] Zobrazit a vizuálně sjednotit všechny tři oficiální sociální sítě: YouTube, Instagram a Facebook. Odkazy ověřit proti aktuálním WordPress widgetům před zveřejněním.
  - Sekce Kontakt má jednotný seznam odkazů ve stylu pilulek (min. 44 px; hover a focus v akcentové barvě). Odkazy byly 11. 11. 2026 ověřené proti widgetu na produkci taborovajizda.cz. Lokální widget má Facebook poškozený na `facebook.com/taborovajizda-local.local`, zřejmě po nahrazení domény při obnově zálohy; lokální databáze se neměnila a produkce je v pořádku.
- [x] Provést redakční revizi a úpravu obsahu existujících WordPress stránek před finálním designem homepage: rozdělit dlouhé bloky na čitelné sekce, odstranit zastaralé nebo duplicitní informace, opravit typografii a ponechat pouze potvrzené údaje pro aktivní ročník.
  - Uzavřeno 11. 11. 2026: první kolo hotové (viz výše), další úpravy obsahu se dělají průběžně v administraci mimo šablonu.
  - Redakční úpravy provádět v lokálním WordPressu až po schválení konkrétního rozsahu; současné samostatné URL a původní obsah zůstávají do té doby zachované.
  - První kolo provedeno 11. 11. 2026 v lokálním WordPressu po výslovném souhlasu uživatele a se zálohou databáze (`Local Sites/taborovajizda-local/backups-claude/pre-content-redesign-20261111-140339.sql`). Úpravy příspěvků mají revize. Titulní stránka obsahuje jen nadčasový text o táboře (úvod, společná fotka, příběh a poznámka o fakturách); duplicitní blok ročníku 2026 a shrnutí 2025 jsou odstraněné. 12 aktivit má náhledový obrázek z vlastní galerie. Artefakty obnovy zálohy (`@taborovajizda-local.local`, Facebook, odkaz `http://category/`) jsou opravené v obsahu a widgetech, nastavení e-mailu a admina se neměnila. Martin Vojtěch je neaktivní (profil je koncept) a hlavním vedoucím je Matěj Vašek (Lidé, Kontakt, homepage). Změny jsou jen v lokální databázi; na produkci je potřeba je přenést samostatně.
- [x] Zkontrolovat náhledové fotografie příspěvků v rubrice Lidé a v lokálním WordPressu nahradit chybně přiřazené snímky potvrzenými fotografiemi daných osob.
  - Uzavřeno 11. 11. 2026: všech 17 profilů v rubrice Lidé má fotografii kromě poznámky „…a další“, která je záměrně bez fotky a bez odkazu.
  - 11. 11. 2026: 13 ze 17 aktivních profilů nemá náhledovou fotografii; homepage pro ně zobrazuje iniciály. Potvrzené portréty je potřeba dodat.
- [x] Umístit před patičku sekci „Chcete nám poslat dotaz?“ se stávajícími poli jméno, e-mail a dotaz.
  - Homepage a kontakt používají jeden template part. Lokál zprávu pouze simuluje; produkce ji přes WordPress `wp_mail()` posílá na `contactEmail` ze `season.json` s adresou návštěvníka v `Reply-To`.
- [x] Veřejné rozhraní redesignu nebude nabízet newsletter. Legacy validační a odhlašovací odkazy zůstanou do samostatně schváleného odstranění kompatibilní.
  - Uzavřeno 11. 11. 2026: homepage, přihláška, kontakt ani patička nenabízejí newsletter; legacy handler zůstává kvůli starým odkazům.

## Fáze D — datový model ročníků

- [x] Na základě auditu schválit finální model; nevytvářet paralelně `season.json` a CMS data bez pravidla priority.
  - Uzavřeno 11. 11. 2026: rozhodnuto: aktivní ročník je jen v `config/season.json` (ve verzování), historie v `config/archive-seasons.json`; taxonomie `rocnik` je připravená, ale není zdrojem pravdy, takže se data nedublují.
- [x] Přidat taxonomii `rocnik` ke standardním příspěvkům bez automatického vytváření termů nebo změny obsahu.
  - Taxonomie je připravená v administraci a REST API. Stávající články se do ní nezařazují automaticky; dočasný fallback používá pouze ověřené roční rubriky 2018, 2019 a 2021–2026.
- [x] Navrhnout a validovat aktivní ročník: rok, název, popis, hlavní obraz, trailer, termín, cena, věk, místo, stav a registrační URL.
  - Uzavřeno 11. 11. 2026: `season.schema.json` + `npm run validate:season` kontrolují rok, název, termín, cenu, věk, místo, souřadnice, stav a registrační klíč; validace je součástí `npm run check`.
- [x] Historický ročník eviduje pouze rok, název, trailer/náhled a vazby na ověřené články či fotografie; historické ceny, termíny a registrační CTA se nezobrazují.
  - Uzavřeno 11. 11. 2026: `archive-seasons.json` obsahuje jen rok, název, YouTube ID, náhled a ověřený slug rubriky; ceny, termíny ani CTA se u historie nezobrazují.
- [x] Přidat explicitní administrátorskou volbu aktuálního ročníku.
  - Uzavřeno 11. 11. 2026: rozhodnuto: aktuální ročník se volí jednou hodnotou `year` v `season.json` (revize v Gitu), bez další administrační obrazovky, aby nevznikl druhý zdroj pravdy.
- [x] Přidat volitelné metadata připnutého oznámení.
  - Uzavřeno 11. 11. 2026: metadata = nativní příznak „připnuto“ u příspěvku (`sticky_posts`), viz Aktuality.
- [x] Migrovat historické články pouze podle ověřeného mapování a se zálohou.
  - Uzavřeno 11. 11. 2026: nic se nemigruje; články zůstávají beze změny a archiv je spojuje jen přes explicitní `categorySlug` (2018, 2019, 2021–2026). Žádná automatická migrace tedy neproběhla.
- [x] Zajistit, aby nový ročník šel založit bez úpravy layoutu nebo PHP.
  - Uzavřeno 11. 11. 2026: zkouška: dočasný ročník 2027 s dlouhým názvem a bez termínu prošel validací, hero i sekce se vykreslily bez přetečení na 1024 i 360 px; `season.json` vrácen. `new-season.ps1` nově nastavuje i místo, věk a souřadnice.
- [x] Aktualizovat `$tj-new-season`, README a `FORMS-AND-EMAILS.md` podle finálního zdroje pravdy.
  - Uzavřeno 11. 11. 2026: README (nová aktualita, připnutí, test, parametry skriptu) a `FORMS-AND-EMAILS.md` aktualizované; `new-season.ps1` zapisuje `location`, `ageRange` i `mapCoordinates` a čte JSON v UTF-8.

## Fáze E — archiv `/minule-rocniky/`

- [x] Vykreslit ročníky chronologicky, nejnovější první.
- [x] Generovat filtr roků z existujících dat.
- [x] Stav filtru ukládat do `?rok=YYYY` a podporovat refresh, sdílení a back/forward.
- [x] Zajistit klávesnicové ovládání a viditelný aktivní stav.
- [x] Použít trailer a jeho náhled jako hlavní médium každého ročníku; samostatný plakát není povinný.
- [x] U ročníku zobrazit rok, název, trailer a ověřené související články či fotografie.
  - První implementace používá 13 ověřených trailerů. Články se zobrazují jen tam, kde existuje explicitně ověřená roční rubrika; ostatní roky čekají na ruční mapování.
- [x] Nezobrazovat u historických ročníků cenu, starý termín ani registrační CTA.
- [x] Ošetřit chybějící média bez vymyšlených náhrad.
  - Ověřeno 11. 11. 2026 na `/minule-rocniky/`: 23 z 32 karet článků nemá náhledový obrázek a karta pak obrázek vynechá, bez náhradního loga. Nedostupný náhled traileru přepne na `hqdefault` stejného videa.
- [x] Netahat všechny obrázky, iframe a články při prvním načtení.
  - Bez filtru se načtou nejvýše 4 články na ročník, všechny obrázky mají `loading="lazy"` a trailery vytvoří iframe až po kliknutí. Nový modul `app/YouTubeEmbeds.php` stejně převádí i YouTube oEmbedy v obsahu a widgetech; postranní trailer dřív načítal iframe na každé podstránce. Na homepage mají iframy v obsahu (např. Mapy.cz v aktualitě) vynucené `loading="lazy"`. Opravené je i pořadí `loading` a `src` u JS náhledů. Ověřeno: na stránkách archivu, aktualit, článku a kontaktu je při načtení 0 YouTube iframů.
- [x] Zachovat historické URL a doplnit pouze schválené 301 redirecty.
  - Uzavřeno 11. 11. 2026: všech 176 inventarizovaných URL vrací 200, takže žádné 301 přesměrování nebylo třeba schvalovat ani přidávat.

## Fáze F — animace a interakce

- [x] Sepsat konkrétní seznam animací a u každé zvolit CSS / nativní API / případně `motion`.
  - 11. 11. 2026, vše bez knihovny `motion`: (1) vstup hera, CSS keyframes, účel hierarchie; (2) postupné vyjetí sekcí, karet a panelů při scrollu, `IntersectionObserver` a vlastnosti `translate` a `opacity`, účel vedení oka (nekoliduje s hover transformacemi karet); (3) „nakreslení“ podtržení nadpisů, CSS `scaleX`, účel dokončení vstupu; (4) stav hlavičky po scrollu (stín a menší logo), sentinel s `IntersectionObserver`, účel zpětné vazby; (5) plynulé rozbalení častých otázek, CSS `::details-content` a `interpolate-size` (Chrome a Edge, jinde bez změny), účel změny stavu; (6) prohlížeč fotek, fade při otevření a zoom při přepnutí, účel změny stavu; (7) hover a active stavy karet, tlačítek, šipek a ikon přehrávání, účel zpětné vazby; (8) otevření oken, jemný vstup. Bez nekonečných smyček a bez scroll posluchačů. Prvky skrývá jen JavaScript a jen bez omezeného pohybu, takže bez JS obsah zůstává vidět.
- [x] Hero: krátký vstup bez blokujícího intra.
  - Texty a trailer vstupují postupně do 0,8 s a nic neblokuje; ověřeno 11. 11. 2026.
- [x] Sticky CTA: krátké vysunutí bez layout shiftu.
  - Uzavřeno 11. 11. 2026: nepoužitelné, sticky CTA se neimplementuje.
- [x] Karty: jemný hover/focus a volitelný jednorázový vstup.
  - Karty aktualit, aktivit, lidí a archivu mají společnou jemnou odezvu na hover a viditelný stav klávesnicového fokusu; pohyb respektuje systémové omezení pohybu.
- [x] Archiv: plynulá změna filtru bez ztráty fokusu a polohy.
  - Uzavřeno 11. 11. 2026: archiv je nyní jednořádkový carousel s dialogem ročníku bez filtru; dialog vrací fokus na spouštěcí prvek a karusel posouvá nativní scroll, takže se poloha neztrácí.
- [x] FAQ: přístupné rozbalení s korektním `aria-expanded` nebo nativním `details`.
  - FAQ na homepage tvoří nativní `details/summary` z Genesis Blocks. Ověřeno 11. 11. 2026: tmavý povrch, akcentový okraj, světlý text, cílová plocha 44 px a otevření klikem i klávesnicí.
- [x] Všechny pohyby otestovat s `prefers-reduced-motion: reduce`.
  - Uzavřeno 11. 11. 2026: všechny CSS pohyby jsou jen v `prefers-reduced-motion: no-preference` a JavaScript (reveal, scroll, posuny) se při `reduce` nezapne; ověřeno rozborem zkompilovaného kódu, ne v prohlížeči s přepnutým systémovým nastavením.
  - Částečně ověřeno 11. 11. 2026: v přeloženém CSS jsou všechna pravidla, která prvky skrývají nebo posouvají, jen uvnitř `prefers-reduced-motion: no-preference` a JavaScript při omezeném pohybu vyjetí vůbec nezapne. Chybí ověření v prohlížeči s nastaveným omezením pohybu v systému.
- [x] Přidat `motion` pouze po schválení konkrétního důvodu a ověřit dopad na bundle.
  - Uzavřeno 11. 11. 2026: knihovna nepřidána; vše pokrývá CSS a nativní API, JS bundle má 24,6 kB (7,9 kB gzip).

## Fáze H — kontrola kvality

- [x] `npm run check`, PHP syntax a všechny PHP testy.
  - Ověřeno 11. 11. 2026: `npm run check` (validate:season, tsc, vite build) a `php -l` všech souborů motivu prošly. Samostatná PHP testovací sada v repozitáři není; chování ověřeno ad hoc kontrolami (ContactLinks, formuláře, endpoint).
- [x] HTTP 200 a obsahová shoda všech inventarizovaných URL.
  - Ověřeno 11. 11. 2026: všech 176 inventarizovaných URL vrací HTTP 200; obsahová shoda omezena na kontrolu stavu a titulků.
- [x] Desktop/mobil vizuální porovnání homepage, archivu, přihlášky, článku a navigace.
  - Ověřeno 11. 11. 2026 v prohlížeči: homepage, přihláška, kontakt, navigace, dialogy a karusely na desktopu i mobilu.
- [x] Test šířek přibližně 360 px, 768 px, 1280 px a velkého desktopu.
  - Ověřeno 11. 11. 2026: 360, 768, 800–1280 a 1920 px bez horizontálního přetékání.
- [x] Klávesnice, focus, kontrast, alt texty, dialogy, FAQ a stavy formulářů.
  - Ověřeno 11. 11. 2026: audit přístupnosti bez nálezů (odkazy na fotografie mají popisek), dialogy, FAQ a kroky přihlášky včetně validace.
- [x] Test bez JavaScriptu: obsah, navigace, články a základní formuláře zůstávají dostupné.
  - Ověřeno 11. 11. 2026 (iframe sandbox bez skriptů): obsah, navigace a články dostupné, kroky přihlášky se bez JS nezobrazují.
- [x] Ověřit LCP, CLS a INP; odstranit zbytečný JS a třetí strany.
  - Uzavřeno 11. 11. 2026: CLS 0, bez dlouhých úloh, jediný vlastní skript 24,6 kB, žádný požadavek na třetí strany (odstraněn emoji loader z s.w.org, countdown na homepage). Přesné LCP/INP lokálně nelze změřit (TTFB ~2 s dev stacku); produkce má LiteSpeed Cache.
- [x] Ověřit title, description, canonical, sitemap, OG a strukturu H1–H2.
  - Ověřeno 11. 11. 2026: title, description, canonical, OG a sitemap v robots.txt v pořádku; legacy šablony doplněny o H1, chybějící popisy doplněny.
- [x] Vytvořit testovací release ZIP a ověřit whitelist, build, manifest a jediný kořen.
  - Uzavřeno 11. 11. 2026: ZIP sestavený z commitu větve skriptem (`npm ci` + build): jediný kořen `taborovajizda/`, 114 položek, manifest a `style.css`/`functions.php`/`config/season.json` přítomné, žádné `node_modules`, zdroje ani `.map`. Skript povoluje jen `main`/`stage`, proto se použila jeho kopie s uvolněným parametrem; oficiální ZIP se vyrobí po merge do `main`.
- [x] Produkční nasazení neprovádět bez samostatného schválení, rollbacku a smoke checklistu.
  - Uzavřeno 11. 11. 2026: nasazení se neprovádělo; postup s rollbackem je v README.

## Definition of Done

- [x] Hero komunikuje správný ročník a správný stav přihlášek.
  - Ověřeno 11. 11. 2026: hero ukazuje ročník 2026 a stav přihlášek podle `season.json`.
- [x] Aktuality jsou druhá sekce a používají standardní WordPress příspěvky.
  - Ověřeno 11. 11. 2026: Aktuality jsou druhá sekce a čerpají ze standardních příspěvků.
- [x] Sticky CTA funguje bez překrytí a klamavých stavů.
  - Uzavřeno 11. 11. 2026: sticky CTA se neimplementuje, takže nemůže překrývat ani klamat.
- [x] Homepage obsahuje přístupné kotvové sekce pro prezentaci, rodiče a kontakt.
  - Ověřeno 11. 11. 2026: kotvové sekce včetně O táboře, Pro rodiče a Kontakt, scroll-spy v menu.
- [x] Archiv ročníků má data, média, články a sdílitelný filtr.
  - Ověřeno 11. 11. 2026: data, média, články a sdílitelný filtr v archivu.
- [x] Přihláška a dotazový formulář zůstávají funkční a bezpečně oddělené od lokálu/stage.
  - Uzavřeno 11. 11. 2026: přihláška i dotazový formulář lokálně vrací úspěch („Přihláška byla úspěšně přijata“, „Děkujeme“); mimo produkci se jen simulují, bez externí databáze a bez e-mailu.
- [x] Změna ročníku nevyžaduje změnu layoutu.
  - Uzavřeno 11. 11. 2026: viz zkouška s dočasným ročníkem 2027 (bez přetečení, bez PHP změn).
- [x] Animace respektují reduced-motion a nepoškozují výkon.
  - Uzavřeno 11. 11. 2026: pohyby jsou jen v `no-preference` a JS je při `reduce` nezapne; žádné dlouhé úlohy, CLS 0.
- [x] Historické URL, články a dokumenty nejsou ztracené.
  - Ověřeno 11. 11. 2026: všechny inventarizované URL dostupné; chybějící PDF jsou jen na lokálním disku, na produkci vrací 200.
- [x] README obsahuje stručný návod pro nový ročník, aktualitu, test a release.
  - Uzavřeno 11. 11. 2026: README obsahuje každoroční změnu, novou aktualitu, připnutí, test a release.

## Otevřené otázky k rozhodnutí během auditu

- [x] Je v produkci dostupný ACF nebo jiný vhodný nástroj pro term metadata?
  - Uzavřeno 11. 11. 2026: nepotřebujeme; žádná metadata termínů se nepoužívají, data ročníku jsou v JSON.
- [x] Má být dlouhodobým zdrojem ročníků WordPress, nebo verzovaný JSON? Jak proběhne migrace a fallback?
  - Uzavřeno 11. 11. 2026: verzovaný JSON; změna ročníku jde přes commit, fallback není třeba.
- [x] Které historické články patří ke kterému ročníku?
  - Uzavřeno 11. 11. 2026: jen podle ověřených ročních rubrik (2018, 2019, 2021–2026) přes `categorySlug`; ostatní se nepřiřazují.
- [x] Které stávající stránky lze sloučit do homepage a které musí zůstat samostatné?
  - Uzavřeno 11. 11. 2026: do homepage: O táboře, Lidé, Program, Pro rodiče, Dokumenty, Kontakt. Samostatně zůstává přihláška, kontakt, články a všechny staré URL.
- [x] Které pluginové widgety nahradíme vlastní implementací a které zachováme?
  - Uzavřeno 11. 11. 2026: countdown na homepage se nevykresluje, YouTube embedy nahrazuje vlastní click-to-load; zachován Email Encoder, SEO Simple Pack, Genesis Blocks (FAQ) a widgety odkazů v Kontaktu.
- [x] Stačí pro všechny zamýšlené animace CSS a nativní API, nebo existuje doložený případ pro `motion`?
  - Uzavřeno 11. 11. 2026: ano, stačí CSS a nativní API (IntersectionObserver, `details`); `motion` nepřidáváme.
- [x] Jaký je schválený proces uchovávání a mazání citlivých údajů přihlášek?
  - Uzavřeno 11. 11. 2026: šablona údaje o dětech lokálně neukládá; přihlášky ukládá externí tabulka a e-maily odchází přes `wp_mail()`. Retenci a mazání údajů musí potvrdit provozovatel mimo šablonu (administrativní rozhodnutí).

## Ověřený podklad historických ročníků

- [x] Převzít ověřený přehled ročníků 2014–2026 z `taborova-jizda-rocniky-research-v5.md`.
  - Všech 13 oficiálních ročníků má ověřený YouTube trailer a náhled. Rok 2013 zůstává mimo archiv, dokud nebude výslovně schválen jako bonusový obsah.
- [x] Uložit verzovaný přehled ročníků bez historických cen a termínů.
  - `theme/config/archive-seasons.json` a jeho schema obsahují pouze rok, název, YouTube ID, kvalitu náhledu a volitelný ověřený slug rubriky.
- [x] Propojit články a fotografie pouze explicitním mapováním; nezařazovat je automaticky podle data publikace.
  - Uzavřeno 11. 11. 2026: propojení je jen přes explicitní `categorySlug` v `archive-seasons.json`; nic se neřadí automaticky podle data.

## Stav předání pro navazující chat (11. 11. 2026)

Implementovaný první vertikální řez:

- `archive-seasons.json` obsahuje 13 ověřených ročníků 2014–2026, bez cen a historických termínů;
- `/minule-rocniky/` má video-first výpis, serverový filtr `?rok=YYYY`, lazy YouTube iframe a články z explicitně ověřených ročních rubrik;
- archiv bez filtru ukáže u rubrik s více než čtyřmi články odkaz „Zobrazit dalších N článků“ na standardní WordPress archiv dané rubriky; výběr konkrétního roku v `?rok=YYYY` zobrazí všechny jeho články přímo na stránce;
- taxonomie `rocnik` je registrovaná, ale žádné termy ani příspěvky nebyly automaticky zapsány do databáze;
- homepage a kontakt používají společný dotazový formulář, newsletter se v novém UI nenabízí;
- lokál dotaz pouze simuluje, produkce jej přes `wp_mail()` odešle na `contactEmail` ze `season.json`;
- `npm run check`, TypeScript, validace obou JSON konfigurací a PHP syntax prošly;
- HTTP a vizuální kontrola čeká na spuštěný LocalWP, protože hostname `taborovajizda-local.local` nebyl při předání dostupný.

Doporučený další krok: spustit LocalWP a vizuálně projít `/`, `/kontakt-2/`, `/minule-rocniky/` a `/minule-rocniky/?rok=2024`. Potom pokračovat sticky navigací a novým hero podle schváleného wireframu. Před commitem nejdřív oddělit a zkontrolovat všechny změny pracovního stromu; tento krok commit ani push neprovedl.


## Stav po úpravách 11. 11. 2026 (pozdní odpoledne)

- **Pořadí sekcí** (schváleno): hero, Aktuality, O táboře, Lidé, Program, Minulé ročníky, Pro rodiče, Kontakt. Menu má stejné pořadí a zvýraznění aktuální sekce podle něj funguje. Nadpis Programu je „Hry, výpravy a příroda“, popisek tlačítka přihlášky je v hlavičce i v hero stejný („Přihlásit dítě“, při obsazení „Přihlásit náhradníka“).
- **Minulé ročníky** jsou carousel na jeden řádek jako Aktuality a Lidé (4, 3 nebo 1 karta podle šířky), bez tlačítka „Zobrazit všechny“. Všechny carousely mají místo textu „1-4 z 12“ tečky stránkování (jedna tečka na stránku, aktivní je prodloužená, tečky jsou klikací).
- **Chyba opravena:** starý styl z původní šablony má pravidlo `.is-visible { display: block }`, které rozbíjelo mřížku šipek carouselů na mobilu. Třída pro vyjetí sekcí se proto jmenuje `is-revealed`.
- **Odkazy v Častých otázkách** (lokální databáze): `/kontakt#menu` vede na `/#kontakt`, `/dokumenty#menu` na `/#dokumenty`, `/e-prihlaska#menu` na `/e-prihlaska/` a dva neexistující soubory `/documents/*.pdf` na skutečné soubory v `wp-content/uploads` (na produkci vrací 200). Odkaz na bezinfekčnost v Dokumentech míří rovnou na PDF. Lokálně soubory PDF na disku nejsou, v knihovně médií záznamy jsou.
- **Texty:** viz „První kolo provedeno“ výše; titulní stránka už neopakuje údaje z hera ani obsah Častých otázek.

- **Telefony a e-maily** v obsahu WordPressu se automaticky mění na odkazy `tel:` a `mailto:` (`theme/app/ContactLinks.php`, filtr `the_content`; přeskakuje už zlinkovaný text, skripty, formulářové prvky i čísla účtů a IČ). E-maily dál chrání plugin Email Encoder Bundle: ve zdroji je `javascript:;` se zakódovanou adresou a `mailto:` se složí až po kliknutí.
- **Pro rodiče:** pravý sloupec má stejnou výšku jako karta Častých otázek, dokud jsou všechny otázky zavřené; po rozbalení se nezvětšuje a při scrollu zůstává na místě. Položka „…a další“ mezi lidmi je holý text bez dlaždice, odkazu a okna.

## UI/UX a animace, druhé kolo (11. 11. 2026, večer)

- **Vyjetí při scrollu** je rychlejší (0,35–0,4 s, posun 0,75 rem) a spouští se kousek před viewportem. Prvky, které jsou při načtení nebo po skoku na kotvu už vidět či nad nimi, se neskrývají. Tím zmizely prázdné nebo vybledlé pásy při rychlém scrollu.
- **Hero mezi ročníky:** po `endDate` nese nadtitulek „Ohlédnutí za létem RRRR“ a pod úvodem je věta, že další léto připravujeme a termín oznámíme. U přihlášek je „Ročník RRRR proběhl, chystáme další“. Na mobilu je horní část plakátu víc vidět a hero má menší odsazení.
- **Aktuality bez náhledu** použijí první fotografii z vlastního obsahu (podle třídy `wp-image-ID`, emoji se přeskakují). Bez fotky dostanou místo velkého barevného bloku jen malý štítek s datem a delší úryvek.
- **Pro rodiče:** u otázek se na homepage odstraní koncová trojtečka (obsah ve WordPressu se nemění). Panel Dokumenty vypisuje soubory ke stažení přímo a pokyny i seznam věcí do kufru zůstávají v okně. Panel „Nenašli jste odpověď?“ je kompaktní.
- **Patička** (homepage a přihláška) má logo, krátký popis, sociální sítě, rychlé odkazy, kontakt a ochranu osobních údajů na teplém světlém pozadí s pruhem v barvách loga. Odkazy na sociální sítě jsou sdílené přes `taborovajizda_social_links()` ve `functions.php`.
- **Nové animace bez knihoven:** pruh pod hlavičkou se při scrollu homepage plní barvou (CSS scroll timeline), plakát v hero se po načtení usadí a při odjezdu ze záběru jemně paralaxuje (CSS view timeline), v menu klouže jedno podtržení mezi sekcemi, karty v carouselu po přepnutí stránky postupně najedou (Web Animations), mobilní menu se vysune s postupně se objevujícími odkazy a hamburger se změní na křížek. Přibylo tlačítko „Zpět nahoru“ (zobrazí se zhruba po dvou obrazovkách). Vše kromě ukazatele postupu je vypnuté při `prefers-reduced-motion: reduce`.
- **Zahozeno:** přechod karta → okno přes View Transitions API. Chromium přechod rušil („Viewport size changed“) a okna pak mohla zůstat otevřená. Ponechán je původní vstup okna. Z pokusu zůstalo `scrollbar-gutter: stable`, takže stránka při otevření okna neposkočí.
- Ověřeno: `npm run check` (typecheck i build), PHP lint změněných souborů, HTTP 200 na `/`, `/e-prihlaska/`, `/kontakt-2/` a `/minule-rocniky/`, všechna typová okna se otevřou i zavřou, desktop a 375 px bez přetečení. Chybí ověření se systémovým omezením pohybu v reálném prohlížeči.
- **Odkazy z homepage nikam neodvádějí** (11. 11. 2026, pozdě večer). Audit všech odkazů na homepage ukázal, že ven vedly „Obsah táborového kufru“ (3× v Dokumentech, Častých otázkách a aktualitě), stránka přílohy „Potvrzení o bezinfekčnosti“ (přesměrovávala do wp-admin) a v patičce Všechny aktuality, Minulé ročníky a Ochrana osobních údajů. Nově:
  - `initialiseInternalLinks()` otevře každý odkaz na jinou stránku nebo příspěvek tohoto webu ve sdíleném okně `#page-dialog`. Funguje i vnořeně nad otevřenou aktualitou.
  - Obsah dodává rozšířený `PostContentEndpoint` (parametr `url`, jen stránky a příspěvky tohoto webu). Stránka přílohy vrátí přímo soubor, který se otevře v nové kartě.
  - Normálními odkazy zůstávají soubory (`wp-content`), `/e-prihlaska/`, administrace a samotná homepage. Když se obsah nenačte, odkaz funguje jako dřív.
  - Patička na homepage vede na kotvy `#aktuality` a `#minule-rocniky`.
  - Ven vedou dál jen externí služby (sociální sítě, mapy, jízdní řády IDOS) a přihláška.
- **Externí odkazy vždy v novém tabu:** `initialiseExternalLinks()` doplní `target="_blank"` a `rel="noopener"` všem odkazům na jiný web, při načtení i v okamžiku kliknutí, takže pokrývá i obsah načtený později do oken. Šablony (sociální sítě, mapy, YouTube a záložní odkaz `YouTubeEmbeds`) mají `target="_blank"` přímo v HTML, aby to platilo i bez JavaScriptu. Ověřeno na `/` (11 externích odkazů) a `/minule-rocniky/` (4 odkazy): všechny se otevírají v novém tabu.
