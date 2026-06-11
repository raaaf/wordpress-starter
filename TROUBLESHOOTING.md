# Troubleshooting / Problemlösung

Diese Anleitung hilft bei häufigen Problemen mit dem WP-Starter Theme.

## Vite / HMR Probleme

### Vite HMR verbindet nicht

**Symptom:** CSS/JS-Änderungen werden nicht automatisch im Browser aktualisiert.

**Lösungen:**

1. **Prüfe ob Vite läuft:**

   ```bash
   npm run dev
   ```

   Die Konsole sollte `VITE ready` anzeigen.

2. **Prüfe die Konsole im Browser:**
   - Öffne die Entwicklertools (F12)
   - Schau in der Konsole nach WebSocket-Fehlern
   - HMR verbindet zu `localhost:5180`

3. **Bei Local by Flywheel:**
   - Stelle sicher, dass deine Site unter `http://` (nicht `https://`) läuft
   - Oder konfiguriere Vite für HTTPS in `vite.config.js`

4. **Cache leeren:**
   ```bash
   # Vite Cache löschen
   rm -rf node_modules/.vite
   npm run dev
   ```

### Assets laden nicht im Frontend

**Symptom:** CSS fehlt, JavaScript funktioniert nicht.

**Lösungen:**

1. **Development-Modus:** Stelle sicher, dass `npm run dev` läuft

2. **Production-Modus:** Erstelle die Assets:

   ```bash
   npm run build
   ```

3. **Prüfe das Manifest:**
   - Nach dem Build sollte `.vite/manifest.json` existieren
   - Falls nicht, führe `npm run build` erneut aus

## Blade-Template Probleme

### Template wird nicht aktualisiert

**Symptom:** Änderungen an `.blade.php` Dateien werden nicht angezeigt.

**Lösungen:**

1. **Cache leeren:**

   ```bash
   # Compiled-Ordner leeren
   rm -rf compiled/*
   ```

2. **Bei Produktiv-Sites:**
   - WordPress-Cache leeren (falls Caching-Plugin aktiv)
   - Browser-Cache leeren (Strg+Shift+R)

### "View not found" Fehler

**Symptom:** `View [xyz] not found` Fehlermeldung.

**Lösungen:**

1. **Prüfe den Dateinamen:**
   - Datei muss `.blade.php` Endung haben
   - Datei muss in `templates/` liegen

2. **Prüfe Groß-/Kleinschreibung:**
   - Linux/Mac sind case-sensitive
   - `Page.blade.php` ≠ `page.blade.php`

## ACF / Block Probleme

### ACF-Felder erscheinen nicht

**Symptom:** Im Editor sind keine ACF-Felder sichtbar.

**Lösungen:**

1. **ACF Pro installiert?**
   - Gehe zu Plugins → Prüfe ob "Advanced Custom Fields PRO" aktiv ist
   - Das Theme funktioniert nur mit der PRO-Version

2. **Feldgruppen registriert?**
   - Die Felder werden beim Theme-Start registriert
   - Deaktiviere und aktiviere das Theme neu

3. **JSON-Sync Konflikt:**
   - Lösche Dateien in `acf-json/` falls vorhanden
   - Felder werden programmatisch definiert, nicht via JSON

### Block erscheint nicht im Editor

**Symptom:** Ein Theme-Block ist nicht im Block-Einfüger verfügbar.

**Lösungen:**

1. **Prüfe block.json:**
   - Jeder Block braucht eine valide `block.json` im `blocks/BLOCKNAME/` Ordner

2. **Plugin-Abhängigkeit nicht erfüllt:**
   - Manche Blöcke brauchen Plugins (z.B. Contact Form 7)
   - Prüfe `"requires"` in der block.json

3. **Cache leeren:**
   - WordPress Admin → Einstellungen → Permalinks → Speichern (leert Block-Cache)

## PHP Fehler

### "Class not found" Fehler

**Symptom:** `Class 'WordpressStarter\...' not found`

**Lösungen:**

1. **Composer Autoload aktualisieren:**

   ```bash
   composer dump-autoload
   ```

2. **Abhängigkeiten installieren:**
   ```bash
   composer install
   ```

### Speicherlimit erreicht

**Symptom:** `Allowed memory size exhausted`

**Lösung:**
Erhöhe das PHP-Speicherlimit in `wp-config.php`:

```php
define('WP_MEMORY_LIMIT', '256M');
```

## Node.js Probleme

### "npm install" schlägt fehl

**Lösungen:**

1. **Node.js Version prüfen:**

   ```bash
   node --version  # Sollte 18+ sein
   ```

2. **node_modules löschen und neu installieren:**

   ```bash
   rm -rf node_modules package-lock.json
   npm install
   ```

3. **npm Cache leeren:**
   ```bash
   npm cache clean --force
   npm install
   ```

## Allgemeine Tipps

### Debug-Modus aktivieren

Füge in `wp-config.php` hinzu:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Fehler werden dann in `wp-content/debug.log` geloggt.

### Alle Caches leeren

```bash
# Theme-Caches
rm -rf compiled/*
rm -rf node_modules/.vite

# Neu bauen
npm run build

# WordPress transients löschen (über WP-CLI)
wp transient delete --all
```

### Hilfe holen

Falls diese Lösungen nicht helfen:

1. Prüfe die Browser-Konsole (F12) auf JavaScript-Fehler
2. Prüfe `wp-content/debug.log` auf PHP-Fehler
3. Prüfe die Terminal-Ausgabe von `npm run dev`
