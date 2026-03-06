# Design: Multisite-Isolation via ThemeContext

**Datum:** 2026-03-03
**Status:** Approved
**Betrifft:** wordpress-starter-theme, goldene-strategie, stiftungs-navigator, moenius

---

## Problem

Alle vier Themes teilen denselben Option-Key-Prefix `wp_starter_`. In einer WordPress
Multisite mit Child-Themes von moenius führt das zu zwei konkreten Bugs.

### Bug 1 — Option-Key-Kollision beim Theme-Wechsel

Jede Site hat ihre eigene `wp_N_options`-Tabelle — `get_option()` ist also per-Site.
Das Problem entsteht beim Theme-Wechsel auf einer Site:

- Site hat `goldene-strategie` → `wp_starter_content_setup_complete = true`
- Admin wechselt zu `moenius` → liest denselben Key → denkt Setup ist fertig → läuft nicht
- Oder umgekehrt: Key nicht gesetzt → Setup läuft doppelt → überschreibt bestehenden Content

### Bug 2 — Fehlender Site-Kontext-Guard in content-generierenden Methoden

`handleRerunContentSetup()`, `handleGenerateDemoPosts()` etc. prüfen nur
`current_user_can('manage_options')`. In Multisite hat ein Super-Admin diese
Berechtigung auf jeder Site. Wenn er eine gespeicherte Tools-URL von Site A auf
Site B öffnet, läuft der Content-Generator auf der falschen Site.

Dazu: `acf/init` → `maybePrefillAcfOptions()` feuert auf jeder Site beim ersten
Admin-Besuch. Wenn `wp_starter_acf_prefill_pending` durch einen Bug auf mehreren
Sites gesetzt ist, schreibt es ACF-Options auf alle.

---

## Was nicht kollidiert (bewusste Abgrenzung)

In WP Multisite läuft pro HTTP-Request immer nur ein Theme. Deshalb sind folgende
Dinge kein Problem und werden nicht geändert:

- **AJAX-Action-Namen** — nur das Theme der aktuellen Site registriert sie
- **Filter-/Action-Hook-Namen** — nur ein Theme pro Request aktiv
- **Transients** — per-Site isoliert
- **Cron-Hook-Namen** — per-Site-Cron-Array
- **Admin-Page-Slugs** (`theme-options`, `wp-starter-setup`) — jede Site hat eigenes Admin
- **Nonce-Strings** — kein Sicherheitsproblem

---

## Lösung: `ThemeContext`-Klasse

Eine neue zentrale Klasse `ThemeContext` in jedem Theme. Sie ist die einzige
Quelle der Wahrheit für Theme-Slug, Option-Keys und Site-Validierung.

### API

```php
class ThemeContext {
    // Theme-Slug aus get_template(): 'moenius', 'goldene-strategie'
    public static function slug(): string;

    // Prefix für Keys (Bindestriche → Unterstriche): 'moenius', 'goldene_strategie'
    public static function prefix(): string;

    // Theme-spezifischer Option-Key: 'moenius_content_setup_complete'
    public static function optionKey(string $key): string;

    // true wenn dieses Theme (via get_template()) auf der aktuellen Site aktiv ist.
    // Schützt gegen Super-Admin-Cross-Site-Aktionen und acf/init-Übergriffe.
    public static function isActiveOnCurrentSite(): bool;

    // Einmalige Migration alter wp_starter_* Keys → neue theme-spezifische Keys.
    // Läuft beim ersten boot() nach dem Update. Alte Keys bleiben erhalten.
    public static function migrate(): void;
}
```

### Implementierungsdetails

**`slug()`:** Gibt `get_template()` zurück — damit funktioniert es auch wenn ein
WP-Child-Theme aktiv ist (Child-Theme → `get_template()` = Parent-Slug).

**`prefix()`:** `str_replace('-', '_', self::slug())` — gültige PHP/MySQL-Identifier.

**`optionKey(string $key)`:** `self::prefix() . '_' . $key`

**`isActiveOnCurrentSite()`:** `get_template() === self::slug()` — einfach und zuverlässig.
Fängt den Fall ab dass auf einer Site kurzzeitig ein anderes Theme aktiv ist.

**`migrate()`:**

1. Guard: wenn `{prefix}_migration_done` gesetzt → return
2. Map: `wp_starter_*` → `{prefix}_*` für alle bekannten Keys
3. Für jeden Eintrag: falls neuer Key leer und alter Key vorhanden → kopieren
4. Alte Keys werden **nicht gelöscht** (Rollback-Sicherheit)
5. `{prefix}_migration_done` setzen

---

## Betroffene Dateien (pro Theme)

| Datei                                                    | Änderung                                            |
| -------------------------------------------------------- | --------------------------------------------------- |
| `src/ThemeContext.php`                                   | Neu erstellen                                       |
| `src/Application.php`                                    | `ThemeContext::migrate()` vor `registerProviders()` |
| `src/Providers/WelcomeServiceProvider.php`               | 5 OPTION\_\*-Konstanten + Guards                    |
| `src/Providers/PluginServiceProvider.php`                | Keys + Guards in 4 Methoden                         |
| `src/PluginConfigurators/AbstractPluginConfigurator.php` | Option-Keys                                         |
| `src/Acf/Options.php`                                    | Option-Keys                                         |

CronServiceProvider und PluginConfiguratorServiceProvider werden **nicht** geändert —
ihre Hook-Namen kollidieren in der Praxis nicht (s. Abgrenzung oben).

---

## Migrations-Keys (vollständige Liste)

| Alter Key                            | Neuer Key (Beispiel moenius)      |
| ------------------------------------ | --------------------------------- |
| `wp_starter_content_setup_complete`  | `moenius_content_setup_complete`  |
| `wp_starter_theme_activated`         | `moenius_theme_activated`         |
| `wp_starter_welcome_dismissed`       | `moenius_welcome_dismissed`       |
| `wp_starter_styleguide_page_id`      | `moenius_styleguide_page_id`      |
| `wp_starter_styleguide_images`       | `moenius_styleguide_images`       |
| `wp_starter_acf_prefill_pending`     | `moenius_acf_prefill_pending`     |
| `wp_starter_dismissed_plugin_notice` | `moenius_dismissed_plugin_notice` |
| `wp_starter_activation_redirect`     | `moenius_activation_redirect`     |
| `wp_starter_configured_{slug}`       | `moenius_configured_{slug}`       |
| `wp_starter_setup_complete`          | `moenius_setup_complete`          |

---

## Deployment-Reihenfolge

1. `wordpress-starter-theme` — als Referenz-Implementierung
2. `goldene-strategie`, `stiftungs-navigator`, `moenius` — parallel, identische Änderungen

Die Migration läuft automatisch beim ersten `boot()` nach dem Theme-Update auf dem
Remote-Server. Kein manuelles Script nötig.

---

## Nicht in Scope

- Änderungen an Child-Themes auf dem Remote-Server (die haben keinen eigenen PHP-Code)
- Änderungen an AJAX-Action-Namen, Nonces, Filter-Hooks
- Rückmigration (Alte Keys löschen) — bewusst ausgelassen
