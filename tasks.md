1. Performance & Core Web Vitals

Minimaler, sauberer Code – Kein Bloat, keine ungenutzten Funktionen
CSS/JS Minifizierung und Defer – Render-blocking Ressourcen eliminieren
Critical CSS inline laden – Above-the-fold Content sofort rendern
Lazy Loading für Bilder und iFrames implementieren
WebP-Bildformat standardmäßig unterstützen
Keine Layout-Shifts (CLS = 0) – Größen für Bilder/Embeds vordefinieren
TTFB optimieren – Effizientes PHP, keine überflüssigen Datenbankabfragen
theme.json nutzen – Styles zentral definieren statt inline-CSS

2. Sicherheit

Escaping ist Pflicht – Jede dynamische Ausgabe escapen:

esc_html() für Text
esc_url() für URLs
esc_attr() für Attribute
wp_kses_post() für HTML-Inhalte


Sanitization bei Input – sanitize_text_field(), sanitize_email() etc.
Prepared Statements – $wpdb->prepare() für alle Datenbankabfragen
Nonces verwenden – CSRF-Schutz bei allen Formularen
Übersetzte Strings escapen – esc_html__(), esc_attr_e() statt __() und _e()
Keine SVG-Uploads erlauben – XSS-Risiko durch XML

3. Accessibility (WCAG 2.1 AA)

Farbkontrast mindestens 4.5:1 (normaler Text) bzw. 3:1 (großer Text)
Keyboard-Navigation vollständig – Alle interaktiven Elemente erreichbar
Focus-States sichtbar – Nie den Default-Focus entfernen ohne Ersatz
Skip-Links am Seitenanfang implementieren
Semantisches HTML – Korrekte Heading-Hierarchie (h1 → h2 → h3)
ARIA-Labels wo nötig, aber HTML-Semantik bevorzugen
Alt-Texte für alle Bilder erzwingen/unterstützen
Links unterstreichen oder 3:1 Kontrast zum umgebenden Text

4. Block-Theme & Full Site Editing

theme.json als zentrale Konfiguration – Farben, Typografie, Spacing
Block Patterns für wiederverwendbare Layouts erstellen
Custom Blocks mit block.json definieren
Local JSON für ACF – Versionskontrolle für Feldgruppen
Fluid Typography nutzen – clamp() für responsive Schriftgrößen
Native CSS Grid/Flexbox statt alte Float-Layouts

5. Responsive & Mobile-First

Mobile-First-Ansatz – Basis-Styles für Mobile, dann nach oben erweitern
Flexible Grids – Keine fixen Breiten
Touch-optimierte Buttons – Mindestens 44×44px Klickfläche
Viewport-Meta-Tag korrekt setzen
Media Queries strategisch einsetzen – Breakpoints nach Inhalt, nicht nach Geräten
Bilder responsive – srcset und sizes Attribute nutzen

6. SEO-Grundlagen

Semantisches HTML5 – <article>, <section>, <nav>, <aside>
Schema.org Structured Data unterstützen
Saubere Heading-Struktur – Eine H1 pro Seite
Title-Tag und Meta-Description editierbar machen
Canonical URLs korrekt ausgeben
Schnelle Ladezeiten – Performance ist Ranking-Faktor

7. Coding Standards

WordPress Coding Standards befolgen (PHP, CSS, JS)
Template-Hierarchie verstehen und nutzen – Dateien dort, wo WordPress sie erwartet
Hooks statt Core-Modifikationen – add_action(), add_filter()
Child-Theme-Kompatibilität – Alle Styles/Skripte enqueuebar
Keine hardcodierten Pfade – get_template_directory_uri() verwenden
Prefix für alle Funktionen – Namenskonflikte vermeiden
Übersetzbar machen – Alle Strings mit __() oder _e() wrappen

8. User Experience

Logo verlinkt zur Startseite – the_custom_logo() verwenden
Intuitive Navigation – Maximal 2-3 Ebenen tief
Beschreibende Link-Texte – Nie "Hier klicken"
Konsistentes Design – Einheitliche Farben, Typografie, Abstände
Loading States – Feedback bei Interaktionen geben
404-Seite gestalten – Hilfreiche Weiterleitung anbieten

9. Wartbarkeit & Updates

Regelmäßige Updates – Kompatibilität mit neuen WP-Versionen
Version Control (Git) – Änderungen nachvollziehbar
Changelog pflegen – Besonders bei Security-Fixes
Dokumentation – README mit Setup-Anweisungen
Staging-Umgebung – Updates vor Live-Deployment testen
CI/CD Pipeline – Automatisierte Deployments

10. Nachhaltigkeit & Zukunftssicherheit

Energieeffizientes Coding – Weniger Requests, kleinere Dateien
PHP 8.x Kompatibilität – Moderne PHP-Features nutzen
Gutenberg-First-Ansatz – Pagebuilder-Abhängigkeit vermeiden
Modularer Aufbau – Funktionen in logische Teile trennen
Plugin-Kompatibilität – Populäre Plugins testen (WooCommerce, WPML, etc.)
Privacy by Design – DSGVO-konforme Cookie-Optionen unterstützen