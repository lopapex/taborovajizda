# Design audit a návrh 2027

Audit podle `.agents/skills/design-taste-frontend` (sekce 11, Redesign protocol). Měřeno 11. 11. 2026 na `taborovajizda-local.local` (1024 px a 360 px). Uživatel návrh schválil 11. 11. 2026 a ve stejný den byl implementován (viz „Stav implementace“ na konci).

## Design read

Čtu to jako redesign se zachováním značky: web dětského letního tábora pro rodiče (rozhodují) a děti (motivace). Jazyk má být dobrodružný a teplý, ale důvěryhodný. Stavíme na nativním CSS, Tailwind v4 utilitách, písmu Bariol, ročním display fontu a paletě noční modré s jantarovým akcentem z loga.

- **Režim:** Redesign - Preserve (zachovat logo, barvy, Bariol, roční display font, URL, kotvy a hlas textů).
- **Dials:** `DESIGN_VARIANCE 6`, `MOTION_INTENSITY 4`, `VISUAL_DENSITY 4`. Rodiče potřebují rychle najít fakta, děti atmosféru. Odpovídá to schválenému wireframu (5 / 3 / 5) posunutému o krok k živějšímu vzhledu.
- **Design systém:** žádný balík (viz rozhodnutí v `REDESIGN-2027.md`). Vlastní PHP markup a tokeny v `theme.json`.

## Současný stav

| Oblast | Zjištění |
| --- | --- |
| Značka | Logo s ohněm, noční modrá `#0b1320` / `#132238`, akcent `#f5b942`, text `#f3f7fb`, Bariol, roční display font (Zombie Land). Silný základ, zachovat. |
| Délka stránky | 10 883 px na 1024 px. O táboře má 2 927 px a Program 2 253 px, dohromady téměř polovinu stránky. |
| Rytmus sekcí | Všech 6 sekcí pod hero má stejný vzor: eyebrow, nadpis a pod ním obsah. Skill požaduje aspoň 4 různé layouty. |
| Eyebrow štítky | 7 sekcí a 7 eyebrow štítků; limit je ceil(7/3) = 3. Navíc 80 drobných štítků celkem (datumy, „Lidé“ na každé kartě). |
| Tvary | Mix poloměrů 3 px, 6 px, 0,5 rem, 16 px, 50 % a pilulka. Chybí jednotné pravidlo. |
| Typografie | Kořenové písmo vychází na 21 px, `h1` 82 px, `h2` 51 px. Hierarchie funguje, ale měřítko je na 1024 px příliš velké a ubírá místo. |
| Aktuality | Bez náhledového obrázku ukazuje karta obří logo, které na tmavém pozadí působí prázdně. Šipky carouselu překrývají okraj karet. |
| O táboře | Celý obsah statické homepage včetně velkého plakátu a nadpisů v ročním fontu. Rozpor v textu: eyebrow „Od roku 1997“, obsah „již od roku 2014“. |
| Lidé | Karty s fotkou a bez fotky mají různou výšku i rozvržení. |
| Pro rodiče | FAQ (nativní `details`) a dokumenty pod sebou. Čitelné, ale je to dlouhý sloupec textu. |
| Pomlčky | 16 výskytů `—` a `–`, skoro všechny v obsahu WordPressu (názvy článků, texty). Jeden ze šablony: věk „7–17 let“. |

### Zachovat

- Hero s rozdělením text vlevo a trailer vpravo, fakta o ročníku a stav přihlášek.
- Záložky aktivit, okno ročníku v archivu, modální okna aktualit a lidí.
- Noční paletu, jeden akcent a Bariol. Roční display font jen pro název ročníku.

### Odstranit nebo opravit

- Eyebrow nad každou sekcí; ponechat nejvýše 3 (hero, Program, Kontakt).
- Smíšené poloměry rohů.
- Obří logo jako náhradu chybějícího obrázku.
- Rozpor 1997 / 2014. Potvrzeno 11. 11. 2026: správný rok je 2014 (web už uvádí jen 2014).

## Navržená struktura (kotvy a pořadí beze změny)

| Sekce | Layout | Změna |
| --- | --- | --- |
| Hero | Rozdělený, text a trailer | Fakta jako jeden řádek kompaktních údajů; menší `h1` (max. 2 řádky); bez dalšího textu pod CTA. |
| Aktuality | Horizontální pás karet se scroll-snapem | Karta bez obrázku dostane typografickou variantu (datum a titulek na tónovaném povrchu) místo loga. Šipky budou mimo karty. |
| O táboře | Rozdělený, krátký úvod a jeden obraz | Úvod zkrátit na 2-3 odstavce z existujícího textu, zbytek do rozbalovacího „Celý příběh tábora“. Plakát ročníku jako jediný obraz. Obsah se nemaže, jen se skryje do rozbalení. |
| Program | Záložky a řada portrétů | Záložky beze změny. Lidé jako jednotné karty s portrétem a jménem (bez úryvku); bez fotky se použijí iniciály na tónovaném poli, ne prázdné místo. |
| Pro rodiče | Dva sloupce | Vlevo FAQ, vpravo panel „Dokumenty ke stažení“ (seznam souborů). Na mobilu pod sebou. |
| Minulé ročníky | Mřížka videí | Beze změny, jen jednotné rohy a typografie. |
| Kontakt | Rozdělený, údaje a formulář | Sjednotit pilulky sociálních sítí s tvarovým systémem. |

Výsledkem je 5 různých rodin layoutu na 7 sekcí a odhadem o 35-45 % kratší stránka.

## Tokeny

- **Tvary:** karty a média 12 px (`--wp--custom--radius`), tlačítka a pole 8 px, kruh jen pro ikonová tlačítka (šipky, play). Pilulky se nepoužijí.
- **Typografie:** základ 18 px, `h1` `clamp(2.75rem, 6vw, 5rem)`, `h2` `clamp(2rem, 4vw, 3.25rem)`, odstavce max. 65 znaků.
- **Barvy:** beze změny; jantar zůstává jediný akcent, oranžová `accent` jen pro sezónní detaily.
- **Rytmus:** sekce `clamp(4rem, 8vw, 6.5rem)`, oddělení sekcí jen mezerou a jednou jemnou linkou tam, kde mění layout.

## Animace (až po designu)

Krátký vstup hera, otevření dialogů, odhalení rozbalených ročníků, hover a focus karet a plynulý scroll ke kotvám. Vše jen přes CSS `transform` a `opacity`, pod `prefers-reduced-motion: reduce` nic. Základ je připravený v `app.css`; v `REDESIGN-2027.md` se odškrtne až po dokončení designu.

## K rozhodnutí uživatele

1. Schválit strukturu a tokeny výše.
2. ~~Potvrdit správný rok založení~~ Potvrzeno: 2014.
3. Úprava textů ve WordPressu (pomlčky v názvech, zkrácení úvodu) proběhne v samostatném redakčním kroku. Šablona obsah jen přeskládá.

## Stav implementace (11. 11. 2026)

Ověřeno na 1024 px a 360 px v `taborovajizda-local.local`:

- **Měřítko:** stránky redesignu (`body.tj-home`) mají základ 18 px, starší šablony dál 16pt. `h1` má na 1024 px 61 px a na mobilu se vejde na 2 řádky.
- **Eyebrow štítky:** zůstaly 3 (hero, Program, Kontakt). Data a roky na kartách používají tichý styl `tj-home-meta`.
- **Tvary:** karty a média 12 px, ovládací prvky 8 px (`--tj-radius-card`, `--tj-radius-control`). Jiné hodnoty mají jen skrytá pole formuláře.
- **Hero:** fakta jsou v jednom kompaktním pruhu. Na mobilu má fotka svislý tmavý překryv, aby byl text čitelný.
- **Aktuality:** karty bez obrázku mají textovou variantu s akcentovým pruhem místo loga a šipky carouselu jsou nad pásem karet.
- **O táboře:** z obsahu homepage se zobrazí úvodní odstavce a první obrázek, zbytek je v rozbalovacím „Celý příběh tábora“. Obsah se nemaže.
- **Lidé:** mřížka portrétů (6 sloupců na desktopu, 2 na mobilu), jméno a role z názvu „Jméno (role)“. Bez fotky se zobrazí iniciály. 14 z 18 profilů zatím nemá náhledový obrázek.
- **Pro rodiče:** FAQ vlevo, panel „Dokumenty ke stažení“ vpravo (na desktopu sticky, na mobilu pod FAQ).
- **Kontakt:** formulář má tmavá pole a primární tlačítko, sociální sítě mají tvar ovládacích prvků.
- **Délka stránky:** 10 883 px → 7 759 px (-29 %). Nejdelší sekcí je teď Program (2 680 px), protože záložky ukazují celé texty aktivit. Dalším krokem může být zkrácení textů aktivit při redakční revizi.
- **Kontroly:** `npm run check`, PHP lint, HTTP 200 na 8 inventarizovaných URL, bez vodorovného přetečení, tlačítka se nezalamují, v textech šablony nejsou pomlčky.

## Druhé kolo podle zpětné vazby (11. 11. 2026)

- **Paleta „Slunečné léto“** (vybral uživatel z variant A/B/C): pozadí `#f4f9ff`, plochy bílé, text `#17324d`, vedlejší text `#4a6378`, slunce `#ffb703` (jen plochy a tlačítka s tmavým textem, 7,5:1), mandarinka `#fb5607` a nebe `#219ebc` (jen dekorace), odkazy `#0f6f94` (5,3:1). Starší šablony (`#taborovajizda_body`) dočasně drží původní noční tokeny v `compat.css`.
- **Atmosféra:** roční ilustrace prosvítá světlými pásy sekcí, nadpisy sekcí a název ročníku jsou v ročním fontu.
- **Navigace:** odkazy jako tlačítka s hover stavem a zvýrazněním aktuální sekce (IntersectionObserver).
- **Aktuality a Lidé:** stránkovací carousel bez posuvníku; šipky posunou o počet viditelných karet (aktuality 3/2/1, lidé 6/4/2).
- **Co zažijeme:** 4 karty skupin s fotkou z galerie a seznamem aktivit, celý obsah v okně.
- **O táboře:** „Celý příběh tábora“ se otevírá v okně.
- **Pro rodiče:** dva stejně vysoké sloupce; dokumenty za tlačítkem v okně, pod nimi panel s telefonem.
- **Spojení:** formulář vlevo, vpravo kontakty, ikonová tlačítka sociálních sítí (ikony WordPress Social Links) a mapa OpenStreetMap se špendlíkem podle `mapCoordinates` v `season.json`.
- **Přihláška:** popisky nad poli, české datum (den, měsíc, rok), 3 kroky s validací, oznámení o uzavřeném ročníku. Názvy, hodnoty a pořadí polí i právní texty beze změny.
- **K potvrzení:** `mapCoordinates` pochází z mapy parkoviště v aktualitě o návštěvním dni, ne ze středu tábora.
