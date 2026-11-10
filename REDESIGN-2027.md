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
  #program               aktivity, prostředí a lidé
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

- [ ] Připravit schválený mobilní a desktopový wireframe homepage.
- [ ] Připravit schválený mobilní a desktopový wireframe archivu ročníků.
- [ ] Připravit schválený mobilní a desktopový wireframe přihlášky bez změny jejího datového toku.
- [ ] Definovat dlouhodobé tokeny v `theme.json`: barvy, typografie, spacing, radius, stíny a šířky obsahu.
- [ ] Definovat jeden volitelný roční akcent s ověřeným kontrastem.
- [ ] Navrhnout komponenty: Button, SectionHeading, InfoChip, PostCard, Poster, Trailer, YearFilter, Accordion, StickyCTA a NewsletterForm.
- [ ] Implementovat pouze vzory použité alespoň třikrát jako sdílenou komponentní třídu; jednorázové layouty ponechat v Tailwind utilitách.
- [ ] Zajistit viditelný focus, touch targety, sémantické nadpisy a reduced-motion základ.

## Fáze C — homepage

### Navigace

- [ ] Vytvořit sticky hlavičku s kotvami a odkazy na archiv a přihlášku.
- [ ] Na podstránkách směrovat kotvy na `/#sekce`.
- [ ] Implementovat přístupné mobilní menu, zavření po volbě a správný offset kotvy.
- [ ] Zachovat funkčnost bez JavaScriptu.

### Hero aktuálního ročníku

- [ ] Vytvořit dominantní hero s plakátem, názvem, anotací, termínem, cenou, věkem a místem.
- [ ] CTA odvodit ze stavu přihlášek; nikdy nenabízet přihlášení na skončený nebo uzavřený ročník.
- [ ] Trailer načíst až po akci uživatele, bez autoplay se zvukem.
- [ ] Ošetřit nepotvrzený termín, cenu, chybějící plakát a chybějící trailer.
- [ ] Odstranit možnost záporného countdownu; při skončeném termínu zobrazit neutrální stav.
- [ ] Optimalizovat LCP plakát bez ořezu důležité grafiky.

### Sticky CTA

- [ ] Použít `IntersectionObserver` nad hero, ne kontinuální scroll handler.
- [ ] Desktop: kompaktní horní lišta; mobil: spodní bar se safe-area.
- [ ] Skrýt sticky CTA v hero, na přihlášce a ve stavech bez platné registrační akce.
- [ ] Ověřit, že nepřekrývá obsah, menu, formulář, klávesnici ani cookie banner.

### Aktuality

- [ ] Umístit aktuality bezprostředně pod hero.
- [ ] Zobrazit připnuté oznámení pouze při existenci odpovídajícího metadata.
- [ ] Serverově vykreslit první 3–4 příspěvky s obrázkem, datem, excerptem a URL.
- [ ] Přidat „Načíst další“ přes REST/AJAX s loading, error a empty stavem bez duplicit.
- [ ] Zachovat normální stránkování nebo odkazy jako fallback bez JS.

### Obsahové sekce

- [ ] Vytvořit `#o-tabore` z existujícího obsahu bez ztráty informací.
- [ ] Vytvořit `#program` pro aktivity, prostředí a lidi.
- [ ] Vytvořit `#pro-rodice` s praktickými informacemi, dokumenty a přístupným FAQ.
- [ ] Vytvořit kompaktní upoutávku 2–4 minulých ročníků.
- [ ] Vytvořit `#kontakt` s kontakty a sociálními sítěmi.
- [ ] Umístit newsletter před patičku a zachovat double opt-in/odhlášení.

## Fáze D — datový model ročníků

- [ ] Na základě auditu schválit finální model; nevytvářet paralelně `season.json` a CMS data bez pravidla priority.
- [ ] Pokud bude schválen CMS model, přidat taxonomii `rocnik` ke standardním příspěvkům.
- [ ] Navrhnout a validovat metadata: rok, název, popis, plakát, trailer, termín, cena, sourozenecká cena, věk, místo, stav a registrační URL.
- [ ] Přidat explicitní administrátorskou volbu aktuálního ročníku.
- [ ] Přidat volitelné metadata připnutého oznámení.
- [ ] Migrovat historické články pouze podle ověřeného mapování a se zálohou.
- [ ] Zajistit, aby nový ročník šel založit bez úpravy layoutu nebo PHP.
- [ ] Aktualizovat `$tj-new-season`, README a `FORMS-AND-EMAILS.md` podle finálního zdroje pravdy.

## Fáze E — archiv `/minule-rocniky/`

- [ ] Vykreslit ročníky chronologicky, nejnovější první.
- [ ] Generovat filtr roků z existujících dat.
- [ ] Stav filtru ukládat do `?rok=YYYY` a podporovat refresh, sdílení a back/forward.
- [ ] Zajistit klávesnicové ovládání a viditelný aktivní stav.
- [ ] U ročníku zobrazit dostupný plakát, trailer, popis a související články.
- [ ] Ošetřit chybějící média bez vymyšlených náhrad.
- [ ] Netahat všechny obrázky, iframe a články při prvním načtení.
- [ ] Zachovat historické URL a doplnit pouze schválené 301 redirecty.

## Fáze F — animace a interakce

- [ ] Sepsat konkrétní seznam animací a u každé zvolit CSS / nativní API / případně `motion`.
- [ ] Hero: krátký vstup bez blokujícího intra.
- [ ] Sticky CTA: krátké vysunutí bez layout shiftu.
- [ ] Karty: jemný hover/focus a volitelný jednorázový vstup.
- [ ] Archiv: plynulá změna filtru bez ztráty fokusu a polohy.
- [ ] FAQ: přístupné rozbalení s korektním `aria-expanded` nebo nativním `details`.
- [ ] Všechny pohyby otestovat s `prefers-reduced-motion: reduce`.
- [ ] Přidat `motion` pouze po schválení konkrétního důvodu a ověřit dopad na bundle.

## Fáze H — kontrola kvality

- [ ] `npm run check`, PHP syntax a všechny PHP testy.
- [ ] HTTP 200 a obsahová shoda všech inventarizovaných URL.
- [ ] Desktop/mobil vizuální porovnání homepage, archivu, přihlášky, článku a navigace.
- [ ] Test šířek přibližně 360 px, 768 px, 1280 px a velkého desktopu.
- [ ] Klávesnice, focus, kontrast, alt texty, dialogy, FAQ a stavy formulářů.
- [ ] Test bez JavaScriptu: obsah, navigace, články a základní formuláře zůstávají dostupné.
- [ ] Ověřit LCP, CLS a INP; odstranit zbytečný JS a třetí strany.
- [ ] Ověřit title, description, canonical, sitemap, OG a strukturu H1–H2.
- [ ] Vytvořit testovací release ZIP a ověřit whitelist, build, manifest a jediný kořen.
- [ ] Produkční nasazení neprovádět bez samostatného schválení, rollbacku a smoke checklistu.

## Definition of Done

- [ ] Hero komunikuje správný ročník a správný stav přihlášek.
- [ ] Aktuality jsou druhá sekce a používají standardní WordPress příspěvky.
- [ ] Sticky CTA funguje bez překrytí a klamavých stavů.
- [ ] Homepage obsahuje přístupné kotvové sekce pro prezentaci, rodiče a kontakt.
- [ ] Archiv ročníků má data, média, články a sdílitelný filtr.
- [ ] Přihláška a newsletter zůstávají funkční a bezpečně oddělené od lokálu/stage.
- [ ] Změna ročníku nevyžaduje změnu layoutu.
- [ ] Animace respektují reduced-motion a nepoškozují výkon.
- [ ] Historické URL, články a dokumenty nejsou ztracené.
- [ ] README obsahuje stručný návod pro nový ročník, aktualitu, test a release.

## Otevřené otázky k rozhodnutí během auditu

- [ ] Je v produkci dostupný ACF nebo jiný vhodný nástroj pro term metadata?
- [ ] Má být dlouhodobým zdrojem ročníků WordPress, nebo verzovaný JSON? Jak proběhne migrace a fallback?
- [ ] Které historické články patří ke kterému ročníku?
- [ ] Které stávající stránky lze sloučit do homepage a které musí zůstat samostatné?
- [ ] Které pluginové widgety nahradíme vlastní implementací a které zachováme?
- [ ] Stačí pro všechny zamýšlené animace CSS a nativní API, nebo existuje doložený případ pro `motion`?
- [ ] Jaký je schválený proces uchovávání a mazání citlivých údajů přihlášek?

