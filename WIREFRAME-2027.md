# Wireframy redesignu 2027

Návrh vychází z auditu obsahu a současných značkových prvků. Nejde o změnu šablony, databáze ani WordPress obsahu. Po schválení se z něj stanou tokeny ve `theme.json` a implementační zadání pro šablony.

## Designový směr

- Typ: one-page prezentace tábora pro rodiče a děti.
- Vizuální jazyk: noční modrý základ, autentické roční fotografie a jeden jantarový akcent odvozený z plamene v logu.
- Logo je hlavní vizuální autorita; roční plakát může přinést vlastní barvy jen ve svém vyhrazeném obrazovém prostoru.
- Variance 5: asymetrie pouze v hero a obrazových blocích, nikoli v navigaci ani formulářích.
- Pohyb 3: hover, focus a krátké přechody. Bez parallaxu, automaticky běžících efektů a scroll handlerů.
- Hustota 5: praktické informace zůstávají snadno dostupné, ale nejsou přilepené do permanentního sidebaru.
- Typografie: zachovat Bariol pro čitelnost a roční display font jen pro název konkrétního ročníku.

## Mobilní homepage

```text
+----------------------------------+
| logo                     MENU    |
+----------------------------------+
|                                  |
|  trailer náhled / roční fotografie|
|                                  |
|  Tábor 2026                      |
|  Zombie Land - Splzov             |
|  15.-28. srpna | 7-17 let        |
|  6 300 Kč                         |
|  [ Přihlásit se ]                 |
+----------------------------------+
| Nejnovější aktuality              |
| článek 1                          |
| článek 2                          |
| článek 3              [Všechny]   |
+----------------------------------+
| O táboře                          |
| krátké představení + foto          |
+----------------------------------+
| Program                            |
| aktivity | prostředí | lidé        |
+----------------------------------+
| Pro rodiče                        |
| dokumenty | praktické info | FAQ   |
+----------------------------------+
| Minulé ročníky         [Archiv]   |
+----------------------------------+
| Kontakt + sociální sítě           |
+----------------------------------+
| Chcete nám poslat dotaz?          |
+----------------------------------+
| patička                           |
+----------------------------------+
```

Menu se otevírá jako vertikální seznam a po volbě kotvy se zavře. CTA není pevně přilepené na obrazovku, dokud hero opouští viewport. Při zavřené nebo skončené registraci se CTA nahradí neutrální informací o ročníku.

## Desktopová homepage

```text
+------------------------------------------------------------------------+
| logo       O táboře  Program  Pro rodiče  Kontakt   Archiv  Přihlásit |
+------------------------------------------------------------------------+
|                                                                        |
|   Tábor 2026                         [ hlavní roční obraz / trailer ]|
|   Zombie Land - Splzov                                                 |
|   anotace                                                             |
|   termín | věk | cena                                                  |
|   [ Přihlásit se ]  [ Přehrát trailer ]                               |
|                                                                        |
+------------------------------------------------------------------------+
| Nejnovější aktuality            článek 1 | článek 2 | článek 3        |
+------------------------------------------------------------------------+
| O táboře                         fotografie + příběh                  |
+------------------------------------------------------------------------+
| Program                          obrazová mozaika aktivit a lidí      |
+------------------------------------------------------------------------+
| Pro rodiče                       dokumenty | praktické informace | FAQ|
+------------------------------------------------------------------------+
| Minulé ročníky                   2-4 karty + odkaz na archiv          |
+------------------------------------------------------------------------+
| Kontakt                          kontakty | sociální sítě              |
+------------------------------------------------------------------------+
| Chcete nám poslat dotaz?         jméno | e-mail | dotaz | odeslat     |
+------------------------------------------------------------------------+
```

Navigace zůstává na jednom řádku a je sticky. Hlavní roční obraz není dekorativní pozadí, má rezervovaný prostor a popisný alternativní text. Trailer se vloží až po volbě uživatele.

## Archiv `/minule-rocniky/`

```text
+------------------------------------------------------------------------+
| Název archivu                                           [rok 2026 v]  |
+------------------------------------------------------------------------+
| rok | název                                                           |
|     | [ hlavní 16:9 náhled traileru, přehrání na vyžádání ]           |
|     | související články a fotografie                                 |
+------------------------------------------------------------------------+
```

Na mobilu filtr přechází nad obsah a karty se řadí pod sebe. Stav filtru je v `?rok=YYYY`; výchozí stav ukáže nejnovější ročníky. Náhled traileru je hlavní vizuál. YouTube iframe se načte až po kliknutí. Historická cena, termín a registrační CTA se nezobrazují; samostatný plakát není podmínkou zobrazení ročníku.

## Samostatné stávající URL

`/dokumenty/`, `/informace-pro-rodice/`, `/kontakt-2/`, rubriky, články a právní stránky zůstávají samostatně dostupné. Jejich hlavička používá stejné tokeny a navádí zpět na odpovídající kotvu homepage. Přihláška zůstává vizuálně mimo tuto fázi.

## Návrh tokenů k implementaci po schválení

| Token | Návrh | Účel |
| --- | --- | --- |
| `background` | `#0b1320` | hlavní noční pozadí bez čisté černi |
| `surface` | `#132238` | plochy obsahu a menu |
| `text` | `#f3f7fb` | hlavní text s vysokým kontrastem |
| `muted` | `#bfd0df` | sekundární text |
| `primary` | `#f5b942` | jediný jantarový akcent pro CTA, odkazy a focus |
| `accent` | `#c7831e` | tmavší odstín stejného akcentu pro stav a zvýraznění |
| `season-accent` | `#e6642c` pro 2026 | volitelný roční akcent omezený na sezónní média a jejich popis |
| `content` | `72rem` | šířka čtení a textových sekcí |
| `wide` | `86rem` | hero a obrazové layouty |
| `radius` | `0.75rem` | karty, vstupy a menu |
| `shadow` | `0 1.25rem 3rem rgb(0 0 0 / 0.25)` | měkké oddělení vrstev |

Tokeny neobsahují automatický dark mode. Pro zachování ročního charakteru zůstane celé rozhraní v jediné tmavé tématické rodině.

## Sdílené vzory

| Vzor | Minimální počet použití | Implementace |
| --- | ---: | --- |
| Button | více než 3 | CSS komponenta nebo sdílený PHP markup |
| SectionHeading | více než 3 | sdílený PHP markup |
| InfoChip | více než 3 | drobný sémantický inline vzor |
| PostCard | více než 3 | sdílený template part |
| TrailerPreview | více než 2 | progressive enhancement bez autoplay |
| Accordion | více než 3 | nativní `details` a `summary` |
| StickyCTA | více než 2 | `IntersectionObserver`, reduced-motion fallback |
| YearFilter | 1 | jednorázový layout v utilitách |
| QuestionForm | 2 | sdílený template part pro kontakt a homepage |

## Přístupnost a pohyb

- Každý ovladač má minimální výšku 44 px, viditelný focus a kontrast alespoň WCAG AA.
- Hierarchie je `h1` v hero, následně `h2` pro hlavní sekce a `h3` v jejich obsahu.
- Transition používají pouze `opacity` a `transform` a vypnou se pod `prefers-reduced-motion: reduce`.
- Kotvy mají `scroll-margin-top` odpovídající sticky hlavičce.
- Karta, odkaz i tlačítko mají rozlišitelné hover, focus a active stavy.

Roční akcent se nepoužívá jako druhá globální značka. Pro rok 2026 je to oranžová z plakátu, doplněná tmavým textem `#0b1320`; kontrast této dvojice je ověřen nad úrovní WCAG AA pro běžný text.

## Schvalovací hranice

Návrh homepage, archivu a logo-vedené palety byl schválen 10. listopadu 2026. Další změny šablon, CSS a TypeScriptu se řídí tímto dokumentem; změna informační architektury, URL nebo obsahu stále vyžaduje samostatné rozhodnutí.
