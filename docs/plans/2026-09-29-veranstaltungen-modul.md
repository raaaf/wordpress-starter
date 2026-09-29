# Flexible-Content-Modul "Veranstaltungen" (Starter-Theme)

> **Executor-Hinweis:** Schritt für Schritt abarbeiten, jedes Verify-Kriterium
> pruefen, bevor der naechste Schritt beginnt. Bei einer STOP-Bedingung:
> anhalten und melden, nicht improvisieren.
>
> **Drift-Check (zuerst):** `git diff --stat 2cf05500..HEAD -- src/PostTypes src/Providers/PostTypeServiceProvider.php src/Providers/WelcomeServiceProvider.php src/Acf/FlexibleContent.php src/Acf/FieldDefinitions.php src/Content/StyleguideLayoutData.php templates/flexible`
> Wenn eine der genannten Dateien seit Planerstellung abweicht: aktuellen Stand
> gegen die Beschreibungen unten pruefen, bei Widerspruch STOP.

## Meta

- Geplant bei Commit `2cf05500`, 2026-09-29
- Repo: `wordpress-starter-theme` (eigenes Git-Repo unter `app/public/wp-content/themes/wordpress-starter-theme`)
- Status: Implementiert (2026-09-29), noch uncommitted im Working Tree
- **Ausdruecklich nur dieser Schritt:** kein Rollout auf die 5 Kundenthemes (goldene-strategie, stiftungs-navigator, moenius, siera-theme, fim-vertrieb). Das ist ein spaeterer, separater Schritt.
- **Live-Walkthrough-Fund (nach Executor-APPROVE):** `FieldDefinitions::dateField()` (Schritt 3) setzte `'save_format' => 'Ymd'`. In ACF Pro 6.8.10 ist `save_format` ein reiner Pre-5.0-Kompatibilitaetsschalter: er schaltet einen alten JS/PHP-Formatpfad ein, der PHP-Datumstoken nicht versteht und beim Speichern ueber die echte Editor-UI Muell erzeugte (`20261002` wurde zu `Y102`). ACF speichert `date_picker`-Werte intern immer als `Ymd`, `save_format` war unnoetig. Fix: `save_format` entfernt, nur `return_format => 'Ymd'` bleibt. Direkt behoben (ein Schluessel in einer Datei, Ursache bereits eindeutig lokalisiert), verifiziert mit einem frischen Testtermin über die echte wp-admin-UI. `composer test`/`composer lint` danach erneut gruen.

## Problem

Es gibt keinen Weg, zukuenftige Veranstaltungen strukturiert im wp-admin zu erfassen und sie auf einer Seite darzustellen. Redakteure haetten sonst nur die Wahl, Termine als Freitext in einen bestehenden Textblock zu schreiben, was weder sortierbar noch wiederverwendbar ist.

## Goal

Ein neues Flexible-Content-Layout "Veranstaltungen" steht im Starter-Theme zur Verfuegung. Redakteure legen Termine ueber einen eigenen Custom-Post-Type im wp-admin an; das Layout zeigt automatisch alle zukuenftigen Termine als Liste (links) und die naechsten drei als Teaser (rechts). Messbar:

- `composer test` und `composer lint` sind gruen.
- Das Layout erscheint im Styleguide mit Demo-Terminen und rendert sichtbar beide Spalten.
- Ein Live-Walkthrough zeigt: Termin im wp-admin anlegen → erscheint sortiert im Frontend; ab dem 4. zukuenftigen Termin erscheint zusaetzlich der Teaser mit den naechsten drei; leerer Zustand ohne Termine zeigt einen gemeinsamen Hinweistext statt leerer Flaeche.

## Non-Goals

- Keine echte Monatskalenderansicht (Nutzerentscheidung: einfache Terminliste im Kalender-Look statt Kalendergitter).
- Keine Einzelseiten/Permalinks fuer Veranstaltungen (kein `single-event.blade.php` in diesem Schritt — analog zur bestehenden Team-CPT-Entscheidung, siehe Konventionen).
- Keine wiederkehrenden Termine (Serientermine), keine Kategorien/Filterung nach Veranstaltungsart, keine Uhrzeiten-Bereichsangabe (nur ein Startzeitpunkt).
- Kein Rollout auf die Kundenthemes.
- Liste zeigt maximal 20 zukuenftige Termine (kein "mehr anzeigen", keine Paginierung) — siehe Known Costs.

## Out of Scope (Files)

- `app/public/wp-content/themes/{goldene-strategie,stiftungs-navigator,moenius,siera-theme,fim-vertrieb}/**` — Kundenthemes, ausdruecklich spaeterer Schritt.
- `templates/flexible/timeline.blade.php`, `templates/flexible/team.blade.php`, `templates/flexible/testimonials.blade.php` — aehnliche, aber unveraenderte Layouts. Nur als Referenz gelesen.
- `resources/images/layouts/*.png` (bestehende) — nur eine neue Datei `events.png` kommt hinzu, keine bestehende wird veraendert.
- `.claude/plans/logs/**`, `docs/plans/**` (ausser dieser Datei) — Planungsartefakte, nicht Teil der Umsetzung.

## Loesung

### Ansatz

Der CPT "Veranstaltung" (Slug `event`) folgt exakt dem bestehenden Muster von `Testimonial`/`Team` (`AbstractPostType`). Er ist **nicht public** (`$public = false`, `$hasArchive = false`) — dieselbe Begruendung wie bei `Team` (`src/PostTypes/Team.php:27-35`): es gibt in diesem Schritt kein `single-event.blade.php`, eine oeffentliche URL wuerde nur den leeren Fallback `single.blade.php` rendern. Termine bleiben ueber `show_ui` (Standard `true`) voll editierbar im wp-admin und ueber `get_posts()`/`WP_Query` abfragbar.

Das Flexible-Content-Layout "events" ist strukturell wie `posts.blade.php`: es fragt echte WP-Posts per `WP_Query` ab, nicht wie `team`/`testimonials` einen Repeater mit eigenen Sub-Feldern. Modul-eigene Felder (Ueberschrift, Textblock, Link) sind schlicht gehalten wie bei `accordion` (einfaches `textField` fuer den Titel, keine `sectionHeaderFields` mit Chip/Ausrichtung — das waere ungefragter Funktionsumfang).

Sortierung nach Datum: ACF `date_picker` speichert das Datum als `Ymd`-String (Standard-`save_format`), der lexikografisch korrekt sortiert. Das erspart eine eigene Datumskonvertierung in der Query — `orderby => meta_value` auf das `Ymd`-Feld reicht.

### Schritte

1. **CPT `Event` anlegen** — neue Datei `src/PostTypes/Event.php`, analog `src/PostTypes/Testimonial.php:15-38`:

   ```php
   class Event extends AbstractPostType
   {
       protected static string $postType = 'event';
       protected static string $singular = 'Veranstaltung';
       protected static string $plural = 'Veranstaltungen';
       protected static string $menuIcon = 'dashicons-calendar-alt';
       protected static int $menuPosition = 27;
       protected static bool $hasArchive = false;
       protected static bool $public = false; // wie Team.php:35, kein single-event Template
       protected static bool $showInRest = false; // wie Team.php:46 — public=false allein stoppt die REST-Route nicht
       protected static array $supports = ['title'];
   }
   ```

   Architektur-Befund: `AbstractPostType`s Default ist `$showInRest = true` (`AbstractPostType.php:64`), unabhaengig von `$public`. Ohne den expliziten Override wuerde `/wp-json/wp/v2/event` trotz `$public = false` oeffentlich erreichbar bleiben — genau das, was `Team.php`s eigener Kommentar vermeiden will.

   → verify: `composer lint` (phpcs/phpstan) exit 0; `grep -n "showInRest" src/PostTypes/Event.php` zeigt `false`.

2. **ACF-Feldgruppe fuer den CPT** — `Event::registerFields()` analog `Testimonial::registerFields()` (`Testimonial.php:130-183`), Feldgruppe `group_event`, Felder ueber neue Helper in `FieldDefinitions.php` (Schritt 3):
   - `event_date` (Pflicht) — Datum der Veranstaltung
   - `event_time` (optional) — Uhrzeit
   - `event_location` (optional) — Ort
   - `event_description` (optional) — Kurzbeschreibung fuer den Teaser
     → verify: `composer test` (Feldregistrierung greift ohne Fehler, `acf_add_local_field_group` wird mit gueltigem Array aufgerufen — kein neuer Test noetig, bestehende Suite darf nicht rot werden).

3. **Neue Feld-Helper in `FieldDefinitions.php`** — `dateField()` und `timeField()` existieren noch nicht (Scan-Befund, keine bestehende Datums-/Uhrzeit-Feld-Definition im Repo). Nach dem Muster von `textField()` (`FieldDefinitions.php:255-292`) ergaenzen:

   ```php
   public static function dateField(string $key, string $label, string $name, bool $required = false, string $instructions = ''): array
   {
       return [
           'key' => $key, 'label' => $label, 'name' => $name, 'type' => 'date_picker',
           'instructions' => $instructions, 'required' => $required ? 1 : 0,
           'display_format' => 'd.m.Y', 'return_format' => 'Ymd', 'save_format' => 'Ymd',
       ];
   }
   public static function timeField(string $key, string $label, string $name, bool $required = false, string $instructions = ''): array
   {
       return [
           'key' => $key, 'label' => $label, 'name' => $name, 'type' => 'time_picker',
           'instructions' => $instructions, 'required' => $required ? 1 : 0,
           'display_format' => 'H:i', 'return_format' => 'H:i',
       ];
   }
   ```

   → verify: `composer lint` exit 0.

4. **Admin-Spalte "Datum"** — `Event::adminColumns()` analog `Testimonial::adminColumns()` (`Testimonial.php:43-87`), eine Spalte `event_date` mit `sortable => 'event_date'`, `sort_type => 'meta_value'` (Ymd-String, siehe Ansatz). `Event::register()` ruft `parent::register()` + `self::registerAdminColumns()`.

   Zusaetzlich (Product-Befund): Standard-Sortierung der Listenansicht auf `event_date` aufsteigend setzen, damit Redakteure nicht bei jedem Aufruf manuell auf die Spalte klicken muessen. Scope bewusst eng: ein `pre_get_posts`-Hook, der nur greift, wenn `is_admin() && $query->is_main_query() && $query->get('post_type') === 'event' && $query->get('orderby') === ''` (kein Default gesetzt) — also nur der Erstaufruf der Liste, ein manuell gewaehlter Sort (z.B. nach Titel) wird nicht ueberschrieben.
   → verify: manuelle Pruefung im wp-admin (Teil des Live-Walkthroughs): Spalte ist sortierbar UND die Liste oeffnet standardmaessig nach Datum aufsteigend sortiert.

5. **CPT registrieren** — `Event::class` in `PostTypeServiceProvider::$postTypes` aufnehmen (`PostTypeServiceProvider.php:25-29`), Import ergaenzen.
   → verify: `grep -n "Event::class" src/Providers/PostTypeServiceProvider.php` findet einen Treffer; `composer test` gruen.

6. **Modul-Felder definieren** — `FieldDefinitions::eventsFields(string $prefix)` analog `accordionFields()`-Kopf (`FieldDefinitions.php:1622-1630`, nur der Titel-Teil, kein Repeater):
   - `textField` fuer Ueberschrift (optional, wie bei `accordion` kein Pflichtfeld)
   - `wysiwygField` fuer Textblock (optional)
   - `linkField` fuer Link (optional)
   - `...self::displaySettingsFields($prefix)` am Ende (Hintergrundfarbe etc., wie jedes andere Layout)
     → verify: `composer test` gruen, `FlexibleContent::withTabs()` greift automatisch (≥3 Inhaltsfelder), kein manueller Tab-Code noetig.

7. **Neues Flexible-Content-Layout registrieren** — `FlexibleContent.php`: neue private Methode `eventsLayout()` analog `accordionLayout()` (`FlexibleContent.php:718-729`), category `interactive` (Nachbarschaft zu `team`/`testimonials`, ebenfalls CPT-gestuetzte Layouts), `acfe_flexible_thumbnail => 'events.png'`, `sub_fields => FieldDefinitions::eventsFields('flex_events')` (aus Schritt 6, daher diese Reihenfolge: Felder vor Layout-Registrierung, sonst referenziert `eventsLayout()` eine noch nicht existierende Methode — Evaluator-Befund). In `getLayouts()` (`FlexibleContent.php:457-464`) nach `self::teamLayout()` einreihen.
   → verify: `grep -n "eventsLayout" src/Acf/FlexibleContent.php` zeigt Definition + Aufruf; `composer test` gruen.

8. **Query als CPT-Methode, nicht inline im Blade** (Architektur-Befund: bricht sonst mit dem etablierten Muster `Testimonial::getTestimonials()`/`Team::getTeamMembers()`, ist inline schwerer testbar). Neue Methode `Event::getUpcomingEvents(int $limit = -1): array` in `src/PostTypes/Event.php`:

   ```php
   public static function getUpcomingEvents(int $limit = -1): array
   {
       $today = current_time('Ymd');
       $posts = self::all([
           'posts_per_page' => $limit,
           'orderby' => ['event_date_clause' => 'ASC'],
           'meta_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
               'event_date_clause' => ['key' => 'event_date', 'value' => $today, 'compare' => '>='],
           ],
       ]);

       return array_map(
           fn($p) => ['id' => $p->ID, 'title' => get_the_title($p), ...get_fields($p->ID)],
           $posts
       );
   }
   ```

   Risiko-Befund umgesetzt: benannte `meta_query`-Klausel (`event_date_clause`) wird direkt in `orderby` wiederverwendet statt zusaetzlich ein Top-Level `meta_key`/`orderby` zu setzen — spart den doppelten `wp_postmeta`-Join und ist die sauberere Vorlage fuer kuenftige datumsbasierte Layouts.
   → verify: `composer lint` exit 0; neuer Unit-Test (siehe Schritt 9).

9. **Unit-Test fuer die Datums-Annahme** (Risiko-Befund: `save_format => 'Ymd'` ist eine Annahme, die je nach ACF-Pro-/SCF-Version abweichen koennte, `composer.json` listet keines der beiden Pakete explizit). Ein Test in `tests/Unit/PostTypes/EventTest.php`: Event-Post anlegen, `update_field('event_date', ...)`, `get_field('event_date', $postId)` lesen, gegen das erwartete `Ymd`-Format pruefen. Das macht die STOP-Bedingung unten reproduzierbar statt einmalig-manuell.
   → verify: `composer test tests/Unit/PostTypes/EventTest.php` gruen.

10. **Blade-Template** `templates/flexible/events.blade.php`, Struktur analog `templates/flexible/accordion.blade.php` (Section-Aufbau), Datenabruf ueber `Event::getUpcomingEvents()`:

    ```php
    @php
        $title = get_sub_field('title');
        $text = get_sub_field('text');
        $link = get_sub_field('link');
        $fetched = \WordpressStarter\PostTypes\Event::getUpcomingEvents(21); // 1 mehr als das Limit, um echten Overflow von "genau 20" zu unterscheiden (Evaluator-Befund: off-by-one)
        $overflow = count($fetched) > 20;
        $upcoming = array_slice($fetched, 0, 20); // hartes Limit, siehe Known Costs
        $preview = array_slice($upcoming, 0, 3);
        $showPreview = count($upcoming) > 3; // Design-Befund: bei <=3 waere die Vorschau eine reine Kopie der Liste
    @endphp
    @if($overflow)
        @php \WordpressStarter\Providers\LogServiceProvider::warning('Events-Layout: 20er-Limit erreicht, weitere Termine werden nicht angezeigt.'); @endphp
    @endif
    ```

    Layout-Details (Design-Befunde):
    - Zwei Spalten via bestehendem Grid-Pattern (z.B. wie in `cards.blade.php`/`two-columns.blade.php`, keine neue CSS-Struktur). Eigene Unter-Ueberschriften pro Spalte, die den Zweck benennen (z.B. "Alle Termine" links, "Naechste Termine" rechts), damit die Wiederholung der ersten drei Eintraege nicht wie ein Fehler wirkt.
    - **Bei `$showPreview === false`** (≤3 zukuenftige Termine): rechte Spalte wird nicht gerendert, die linke Liste zeigt bereits alles — keine zwei identischen Drei-Item-Bloecke nebeneinander.
    - **Datum-Badge-Format:** Tag zweistellig gross + Monat abgekuerzt klein (z.B. "15" / "Mär"); Jahr wird nur eingeblendet, wenn es vom aktuellen Jahr abweicht (Edge Case Jahreswechsel). Semantisches `<time datetime="...">` um das Datum (Evaluator-Befund: Barrierefreiheit), nicht nur gestyltes `<div>`/`<span>`.
    - **Ab mehr als 8 Eintraegen** in der linken Liste: kompakte Zeilendarstellung statt grosser Kalenderblatt-Badges, damit 20 Eintraege nicht zur Bildschirmwand werden.
    - **Overflow-Hinweis:** wenn `$overflow` (20 Termine erreicht, es koennten mehr existieren), statischer Text "+ weitere Termine" ohne Linkziel am Ende der Liste (kein Klickziel noetig, nur Transparenz).
    - **Leerer Zustand:** `@if(empty($upcoming))` → EIN gemeinsamer Hinweistext ueber die Breite beider Spalten (nicht zwei separate Texte — Liste und Teaser haben dieselbe Datenquelle, ein leerer Zustand ist nie pro Spalte unterschiedlich), Modul-Ueberschrift/Text/Link bleiben sichtbar.
      → verify: `npm run lint` gruen; primaer Live-Walkthrough (Schritt 14).

11. **Styleguide-Demodaten** — `StyleguideLayoutData::getEventsLayoutData()` analog `getPostsLayoutData()` (`StyleguideLayoutData.php:640-652`), eingebunden in `build()` (`StyleguideLayoutData.php:97-101`) nach `getTeamLayoutData()`. Da das Layout echte CPT-Posts abfragt und keine Sub-Feld-Repeater-Daten, liefert diese Methode nur `title`/`text`/`link`/`background_color` fuer die Modul-Felder selbst — die Termine selbst kommen aus Schritt 12.
    → verify: `grep -n "getEventsLayoutData" src/Content/StyleguideLayoutData.php` zeigt Definition + Aufruf.

12. **Demo-Veranstaltungen seeden** — `WelcomeServiceProvider::createDemoCptEntries()` (`WelcomeServiceProvider.php:803-836`) um einen Event-Block erweitern, analog dem Testimonials-Block: 4-5 Termine mit **relativen** Zukunftsdaten (`gmdate('Ymd', strtotime('+N days'))`, nicht fest verdrahtet, sonst veraltet die Demo), `STYLEGUIDE_DEMO_POST_META_KEY`-Marker, mindestens ein Termin ohne `event_description` (zeigt den Fallback im Teaser). `deleteDemoCptEntries()` (`WelcomeServiceProvider.php:880-894`) um `'event'` im `post_type`-Array (Zeile 883, aktuell `['testimonial', 'team_member']`) ergaenzen, sonst haeufen sich bei jedem Lauf Dubletten.
    → verify: `grep -n "'event'" src/Providers/WelcomeServiceProvider.php` zeigt beide Stellen (Insert + Delete-Filter).

13. **Layout-Thumbnail** — `resources/images/layouts/events.png` erzeugen via `scripts/generate-layout-thumbnails.php` (bestehendes Script, laeuft gegen die lokale Site). Falls das Script in dieser Umgebung nicht lauffaehig ist (braucht eine laufende Local-Site): Platzhalter-PNG committen und das als bekannten Folgeschritt in "Known Costs" vermerken statt den Schritt stillschweigend auszulassen.
    → verify: Datei `resources/images/layouts/events.png` existiert.

14. **Live-Walkthrough** (siehe CLAUDE.md, "wire up UI"-Kriterium: kein Test, sondern Live-Abnahme). Vorab protokollieren: aktives ACF-Plugin (ACF Pro oder SCF) + Version (Risiko-Befund, fuer die Nachvollziehbarkeit der `Ymd`-Annahme).
    - Ein Termin (Datum in 3 Tagen) → Liste zeigt ihn, KEINE rechte Vorschau-Spalte (≤3 Termine, Design-Befund).
    - Zweiten und dritten Termin anlegen (weiter in der Zukunft, ≥4 zukuenftige Termine insgesamt) → jetzt erscheint die rechte Vorschau-Spalte mit den ersten 3, deutlich als "Naechste Termine" beschriftet, linke Liste zeigt weiterhin alle.
    - Termin mit Datum in der Vergangenheit anlegen → taucht in keiner Spalte auf.
    - Termin im naechsten Kalenderjahr anlegen → Datum-Badge zeigt die Jahreszahl mit an.
    - 21. zukuenftigen Termin anlegen (Testdaten) → Liste zeigt weiterhin nur 20, Overflow-Hinweis "+ weitere Termine" erscheint, Log-Eintrag wird geschrieben.
    - Alle Termine loeschen/auf Entwurf setzen → EIN gemeinsamer Hinweistext ueber beide Spalten, Ueberschrift/Text/Link bleiben sichtbar.
    - Im wp-admin: Event-Liste oeffnen → standardmaessig nach Datum aufsteigend sortiert; Spalte "Datum" manuell umsortieren funktioniert.
    - `wp-json/wp/v2/event` aufrufen → 404/nicht erreichbar (REST-Sperre greift).
      → verify: Schritt-fuer-Schritt-Abnahme mit dem Nutzer, ein Punkt nach dem anderen.

15. **Tests + Lint** — `composer test` und `composer lint` vollstaendig (nicht nur gefiltert) vor Abschluss.
    → verify: beide exit 0.

### Betroffene Dateien

- `src/PostTypes/Event.php` — neu, CPT-Klasse inkl. `getUpcomingEvents()` (Schritt 1, 4, 8)
- `src/Acf/FieldDefinitions.php` — `dateField()`, `timeField()`, `eventsFields()` ergaenzt (Schritt 3, 7)
- `src/Providers/PostTypeServiceProvider.php:25-29` — `Event::class` ergaenzt (Schritt 5)
- `src/Acf/FlexibleContent.php:457-464` + neue `eventsLayout()`-Methode — Layout registriert (Schritt 6)
- `templates/flexible/events.blade.php` — neu (Schritt 10)
- `tests/Unit/PostTypes/EventTest.php` — neu, Datumsformat-Test (Schritt 9)
- `src/Content/StyleguideLayoutData.php:97-101` + neue `getEventsLayoutData()`-Methode (Schritt 11)
- `src/Providers/WelcomeServiceProvider.php:803-836,883` — Demo-Events + Delete-Filter (Schritt 12)
- `resources/images/layouts/events.png` — neu (Schritt 13)

**Beziehungs-Sweep:** Kein bestehendes Modell-Feld wird umbenannt/entfernt, nur neue Felder/Klassen ergaenzt — kein weiterer Sweep noetig.

### Konventionen

- **CPT-Registrierung:** `AbstractPostType` (`src/PostTypes/AbstractPostType.php`), exakt wie `Testimonial`. Pflicht laut Projekt-`CLAUDE.md`, Abschnitt "Custom Post Types".
- **ACF in PHP, kein JSON-Sync** (`CLAUDE.md`: "ACF fields defined in PHP, not JSON (version control)") — `acf_add_local_field_group()`, kein `acf-json`.
- **Feldlabels auf Deutsch** (`CLAUDE.md`: "Field labels/instructions in German").
- **`FlexibleContent::withTabs()`** splittet automatisch ab dem ersten Feld aus `DISPLAY_FIELDS` — kein manueller Tab-Code im neuen Layout noetig, `displaySettingsFields()` einfach ans Ende haengen.
- **Gutenberg deaktiviert**, Classic Editor + ACF Flexible Content ist das Content-Modell — betrifft dieses Modul nicht direkt, nur zur Einordnung.
- **`{{ }}`-Escaping** in Blade entspricht `esc_html()` (`CLAUDE.md`, Abschnitt Blade Directives) — Ausgaben der Event-Felder normal uber `{{ }}`, nicht vor-escapen.
- **Admin-Spalten:** deklaratives `adminColumns()`-Array, keine direkten Hook-Registrierungen (`AbstractPostType.php:261-382`), exakt wie `Testimonial::adminColumns()`.

## Edge Cases

- **Kein zukuenftiger Termin vorhanden:** EIN gemeinsamer Hinweistext ueber beide Spalten (Nutzerentscheidung + Design-Befund gegen doppelte Texte), Modul-Ueberschrift/Text/Link bleiben sichtbar.
- **1-3 zukuenftige Termine:** keine rechte Vorschau-Spalte (Design-Befund), linke Liste zeigt alle.
- **Termin ohne Kurzbeschreibung:** Teaser zeigt Datum + Titel ohne Beschreibungszeile, kein leerer Absatz.
- **Termin ohne Uhrzeit:** Liste/Teaser zeigen nur das Datum, keine "00:00"-Anzeige.
- **Mehr als 20 zukuenftige Termine:** hartes `posts_per_page`-Limit in `Event::getUpcomingEvents()` (Schritt 8) verhindert eine unbegrenzt lange Liste; Overflow-Hinweistext + Log-Eintrag statt stillem Abschneiden (Product-/Risiko-Befund).
- **Termin im naechsten Kalenderjahr:** Datum-Badge zeigt die Jahreszahl zusaetzlich an (Design-Befund).
- **Mehr als 8 zukuenftige Termine in der Liste:** kompakte Zeilendarstellung statt grosser Badges (Design-Befund).
- **Termin mit Datum = heute:** zaehlt als "zukuenftig" (`compare => '>='`), erscheint in Liste und potenziell im Teaser.

## Known Costs

- Kein `single-event.blade.php`: falls spaeter doch Einzelseiten fuer Veranstaltungen gebraucht werden, muss `$public`/`$hasArchive` nachtraeglich umgestellt und ein Template ergaenzt werden. Bewusst nicht in diesem Schritt, da nicht angefragt.
- Keine echte Kalenderansicht: falls spaeter ein Monatsraster gewuenscht wird, ist das ein eigenes UI-Stueck (JS-Kalenderkomponente), nicht in diesem Modul enthalten.
- Layout-Thumbnail evtl. nur als Platzhalter, falls das Screenshot-Script in dieser Umgebung nicht lauffaehig ist (Schritt 13).
- Liste zeigt maximal 20 zukuenftige Termine, darueber hinaus nur ein Text-Hinweis (kein "mehr anzeigen", keine Paginierung, siehe Non-Goals) — bewusster Tradeoff fuer den erwarteten Starter-Anwendungsfall.

## Done Criteria

- [ ] `composer test` → exit 0 (volle Suite, nicht gefiltert), inkl. 1 neuer Test (`EventTest.php`, Schritt 9)
- [ ] `composer lint` → exit 0
- [ ] `npm run lint` → exit 0
- [ ] `npm run test:styleguide` (axe-Check gegen die Styleguide-Seite, siehe Theme-CLAUDE.md) → keine neuen Verstoesse im "Veranstaltungen"-Modul (Evaluator-Befund: das Layout ist sonst nirgends gegen a11y geprueft)
- [ ] `grep -rn "eventsLayout\|getEventsLayoutData\|Event::class\|getUpcomingEvents\|showInRest" src/` → mind. 5 Treffer (Layout, Styleguide-Daten, Provider-Registrierung, Query-Methode, REST-Sperre)
- [ ] Live-Walkthrough (Schritt 14) vollstaendig abgenommen, inkl. REST-Sperre und Admin-Default-Sortierung
- [ ] `git status` zeigt ausschliesslich die unter "Betroffene Dateien" gelisteten Pfade (plus ggf. bereits laufende, unabhaengige Aenderungen im Working Tree bleiben unangetastet — siehe Meta/Drift-Check)

## STOP Conditions

- Eine der unter "Betroffene Dateien" genannten Stellen weicht vom beschriebenen Stand ab (Drift-Check schlaegt an).
- Ein Verify-Kriterium schlaegt nach einem ernsthaften Fixversuch zweimal fehl.
- Der Fix wuerde eine Out-of-Scope-Datei beruehren (insbesondere ein Kundentheme).
- Die Annahme "ACF `date_picker` mit `save_format => 'Ymd'` sortiert per `meta_value` korrekt" erweist sich als falsch (z.B. weil ACF-Version/SCF ein anderes Speicherformat erzwingt, sichtbar am roten `EventTest.php` aus Schritt 9) — dann Query-Ansatz neu bewerten, nicht stillschweigend auf `meta_value_num` umstellen.

## Wartungshinweise

- Ein Rollout auf die 5 Kundenthemes ist ein separater, spaeterer Schritt (Ausblick, nicht Teil dieses Plans). Vor dem Rollout: pruefen, ob `moenius`/`fim-vertrieb` (Mitgliederbereich) zusaetzliche Felder brauchen (z.B. Sichtbarkeit nur fuer eingeloggte Mitglieder) — das war in diesem Plan ausdruecklich nicht Scope.
- Die Sync-Regel aus dem Projekt-Gedaechtnis ("Aenderungen an gemeinsamen Dateien immer auf alle 6 Themes anwenden") gilt fuer dieses neue Modul noch nicht, da es zum Zeitpunkt dieses Plans nur im Starter existiert.

## Nachträge nach der Umsetzung

- Darstellung Variante B: der nächste Termin groß als Karte, weitere Termine als Liste darunter, maximal 8, dazu der Hinweis "+ weitere Termine".
- Feld Art mit Vor Ort, Online und Hybrid; das Feld Ort erscheint nur bei Vor Ort und Hybrid.
- Die Adresse ist ein Google-Maps-Link in der Meta-Zeile, in derselben Schrift und Farbe wie die Meta-Zeile.
- Ein optionaler Link pro Termin: Button in der Karte, Titel-Link in der Liste.
- Optionales Beitragsbild pro Termin, nur in der großen Karte. In der Liste bewusst nicht, bei 64px liest sich kein Motiv.
- ACF date_picker ohne save_format: speichert immer Ymd (save_format hatte "Y102" gespeichert).
- Admin-Spalte heißt "Termin" statt "Datum".
