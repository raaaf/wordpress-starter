# Major-Dependency-Upgrades (6 Themes) — right-sized

## Problem

Beim Dependency-Pass zurueckgehaltene Major-Bumps (phpunit 13, eslint 10, typescript 6, illuminate 13, symfony 8) + PHPStan 2.2 stehen offen. Reine Hygiene, keine Dringlichkeit (Security via phpseclib ist bereits ausgeliefert). Tech-Debt identisch ueber alle 6 Themes (Sync-Regel, `wordpress-starter-theme` ist Basis).

## Ziel

Pro Theme `composer phpstan/phpcs/phpunit` + `npm lint/test/build` gruen mit aktualisierten Majors, keine Blade-Runtime-Regression. Vorgehen wie beim phpseclib-Update: Pilot auf Starter, CI gruen, dann auf 5 Client-Themes kopieren.

## Nicht-Ziele

- `php_codesniffer 4` (wpcs 3.x blockiert, kein PHPCS-4-Support) — bleibt auf 3.x.
- Keine Sync-Scripts, Drift-CI, Rollback-Runbooks, PHP-Version-Code-Guards, Staging. Solo-Setup mit gruenem CI braucht das nicht.
- Keine Features, kein Refactoring ueber das Migrations-Minimum.

## Loesung

### Phase 1 — Dev-Tooling (nur CI-Risiko, kein Runtime)

Auf Starter, ein gebuendelter `chore`-Commit (kein Release noetig, da rein dev):

1. **tsconfig**: `"moduleResolution": "node"` -> `"bundler"` (Pflicht, sonst bricht TS6 den Build). `strict` bleibt `true`.
2. **npm-Majors**: typescript 6, eslint 10 + `@eslint/js` + typescript-eslint, lint-staged 17, archiver 8, cssnano 8, rollup-plugin-visualizer 7, `@types/wordpress__blocks` 15. `npm run lint` + `build` + `test` gruen; bei TS6-Typfehlern in `resources/js` einzeln fixen.
3. **phpstan 2.1 -> 2.2** + die 2 echten Findings: Return-Typen von `ColorPaletteGenerator::toFigmaTokenFormat()` und `MemberArea/Areas::all()` (letzteres nur fim/moenius) annotieren.
   - Der `enableReleaseAssets`-Eintrag in `phpstan.neon` ist KEIN 2.2-Finding, sondern ein bestehendes `ignoreErrors`-Pattern. Erst relevant, wenn plugin-update-checker auf 5.7 bumpt (Namespace `v5p6` -> `v5p7`). Hier nichts zu tun.
4. **phpunit 11 -> 13**: Test-Code an entfernte APIs anpassen (Data-Provider-Attribute, Mock-Semantik), `phpunit.xml`-Attribute/Schema gegenpruefen. Sanity: `composer test` Anzahl Tests vorher == nachher (kein stiller Verlust).

### Phase 2 — Runtime (illuminate 13 + symfony 8)

Auf Starter, ein `fix`-Commit (loest Patch-Release aus, damit das mitgelieferte `vendor/` auf Produktion kommt):

1. `composer.json`: `"php": "^8.2"` -> `"^8.3"` (illuminate 13 verlangt 8.3), illuminate `^12` -> `^13`, symfony `^7` -> `^8`.
2. `composer update`, `compiled/` (Blade-Cache) leeren.
3. **Verify**: `composer phpstan` + `composer test` + `npm run build` + `npm run test:e2e`. E2E gruen = Blade rendert. `src/BladeApplication.php` (`extends Container`) im Blick behalten — falls illuminate 13 Container-Interna aendert, faellt es hier oder im E2E auf.

### Propagation (nach jeder Phase, Starter gruen)

Wie beim phpseclib-Update: pro Client-Theme `composer update` der betroffenen Pakete + identische Code-Fixes uebernehmen + npm-Update, dann `git add` (composer.lock, package-lock.json, vendor, geaenderte src/tests), commit, push. CI je Theme gruen abwarten.

## Eine Vorab-Pruefung, die wirklich zaehlt

Vor Phase 2 (illuminate 13 braucht PHP 8.3): einmal die Live-PHP-Version je Kundenseite checken (`php -v` / WP-Site-Health). Laeuft eine Seite auf < 8.3, dort erst PHP hochziehen, bevor das Theme-Update raus geht. Sonst Fatal Error nach dem Update.

## Edge Cases

- **Multisite (moenius + siera, ein Install)**: beide im selben Zeitfenster updaten, nicht tagelang gemischt (zwei illuminate-Versionen im selben PHP-Prozess = Konflikt).
- **illuminate 13 zieht symfony 8**: ein Commit, nicht trennen.
- **Rollback**: Release ist published -> kein Un-Release. Bei Bruch: Commit reverten (inkl. composer.json + lock + vendor) -> neuer Patch-Release rollt vorwaerts.

## Reihenfolge

Phase 1 komplett (alle 6) -> dann Phase 2. Innerhalb Phase 1: Starter -> 5 Client-Themes. Jeder Schritt ein isolierter Commit pro Theme, damit ein CI-Bruch sofort zuzuordnen ist.
