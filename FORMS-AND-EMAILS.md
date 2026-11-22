# Přihlášky a e-maily

Tento dokument popisuje produkční chování formulářů. Externí databáze není součástí WordPress databáze ani Gitu a její přístupové údaje se ukládají pouze do neverzované konfigurace WordPressu.

## Konfigurace aktivního ročníku

Soubor `theme/config/season.json` určuje aktivní ročník a obsahuje také:

- `registration.externalSeasonKey` — ID odpovídajícího řádku v externí tabulce `tabory`;
- `location`, `ageRange`, `mapCoordinates` — místo, věk a bod na mapě zobrazené v hero a kontaktu (formuláře je nepoužívají);
- `contactEmail` — provozní adresa použitá jako odesílatel a interní příjemce formulářových zpráv.

Na produkci má být `externalSeasonKey` nastaven explicitně. Hodnota `null` použije poslední řádek tabulky `tabory`, což je pouze nouzová zpětná kompatibilita.

## Elektronická přihláška

1. Handler ověří povinná pole, e-mail, nonce a rodné číslo.
2. Podle `externalSeasonKey` načte ročník z tabulky `tabory`.
3. Do tabulky `prihlasky` vloží nový záznam s `id_tabora` vybraného ročníku.
4. Databázový unikátní index `id_tabora + rc` zabrání opakované přihlášce stejného dítěte na stejný ročník.
5. Na kontaktní e-mail z formuláře odešle kopii přihlášky a platební pokyny.
6. Kopii zprávy odešle na `contactEmail`.
7. Po úspěchu zobrazí potvrzení; při chybě zápisu nebo duplicitě vrátí odpovídající chybový stav.

E-mail v současnosti obsahuje údaje z přihlášky včetně rodného čísla a adresy. Kvůli ochraně osobních údajů je vhodné v další změně citlivé hodnoty z e-mailu odstranit a ponechat je pouze v zabezpečené databázi.

## Hromadné e-maily rodičům

Web hromadně nic nerozesílá. Adresy rodičů dává administrátorům stránka Nástroje → Kontakty rodičů (`app/ParentContacts.php`): výběr ročníku z tabulky `tabory` (výchozí je aktivní ročník), nebo všechny ročníky; sourozenci se stejným e-mailem jsou sloučení do jedné adresy. Tlačítko Kopírovat adresy je pro pole Skrytá kopie (Bcc) při organizačních zprávách; Stáhnout CSV (`email;jmeno;deti`, UTF-8 s BOM) je pro import do rozesílací služby u pozvánek, které musí mít odkaz na odhlášení. Stránka jen čte a funguje tam, kde jsou definované konstanty `TJ_DB_*`; jinde ukáže, že databáze není dostupná.

## Seznam dětí

Nástroje → Seznam dětí (`app/ChildrenList.php`) ukazuje přihlášené děti jednoho ročníku v pořadí přihlášení: číslo, jméno, věk k prvnímu dni tábora (`tabory.termin_od`), holka/kluk podle rodného čísla (u dívek je k měsíci přičteno 50, od roku 2004 případně 70), nástup, výstup, spolubydlící, kontakt a poznámku. Všechny sloupce se řadí kliknutím v prohlížeči. Stáhnout CSV dá stejné sloupce v pořadí přihlášení (UTF-8 s BOM, středníky, české telefony jako „777 111 222“). Rodné číslo ani adresa se nezobrazují ani nestahují. Společný přístup k databázi obou stránek je v `app/Registrations.php` (název ročníku bere z `tabory.nazev`).

## Newsletter

Newsletter byl 12. 11. 2026 zrušen: web už adresy nesbírá, při publikaci aktuality nic nerozesílá a staré ověřovací ani odhlašovací odkazy nefungují. Tabulka `newsletter` v externí databázi zůstala beze změny; její smazání je samostatný krok správce databáze.

## E-mailová doprava

Šablona používá WordPress funkci `wp_mail()`. Konkrétní SMTP server nastavuje WordPress nebo serverové prostředí; přístupové údaje nepatří do šablony ani do `season.json`.

## LocalWP a stage

V prostředí, jehož `wp_get_environment_type()` není `production`, se po stejné vstupní validaci:

- nezapisuje do externí produkční databáze;
- neposílají skutečné e-maily;
- vrací simulovaný úspěšný výsledek a zapisuje diagnostiku do lokálního PHP logu.

Stage musí mít `WP_ENVIRONMENT_TYPE` nastavený na `staging`. Jinak by se mohl chovat jako produkce.

V prostředích `local` a `staging` je přihláška vidět i při uzavřeném ročníku (ladicí zobrazení), aby šla otestovat; vypnout ji lze `define('TJ_DEBUG_REGISTRATION_CTA', false);`. Na stage formuláře odesílají na `/test/index.php/odeslat-dotaz/`, protože jiné cesty pod `/test/` obsluhuje produkční server.

## Příprava roku 2027

1. V externí tabulce `tabory` vytvořit řádek s rokem, názvem, cenou a termínem.
2. Zapsat jeho přidělené ID jako řetězec do `registration.externalSeasonKey`.
3. Zkontrolovat `contactEmail`.
4. Otestovat přihlášku mimo produkci v simulačním režimu.
5. Po nasazení provést jednu kontrolovanou produkční přihlášku a ověřit databázový řádek i doručení zpráv.
