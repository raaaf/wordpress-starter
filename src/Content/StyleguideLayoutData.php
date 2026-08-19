<?php

declare(strict_types=1);

namespace WordpressStarter\Content;

/**
 * Styleguide layout data factory.
 *
 * Pure data class: no WordPress hooks, no side effects. Generates the ACF
 * Flexible Content layout arrays used by the styleguide page. Image IDs are
 * injected at construction time from the media library import step.
 */
class StyleguideLayoutData
{
    /**
     * @param array<string, int> $imageIds Keyed as placeholder_N and logo_N
     */
    public function __construct(private readonly array $imageIds)
    {
    }

    /**
     * Build the full list of styleguide layouts for ACF Flexible Content.
     *
     * @return array<int, array<string, mixed>>
     */
    public function build(): array
    {
        $layouts = [];

        // Die Design-System-Referenz (Typografie, Farben, Schatten, Komponenten)
        // steht nicht mehr hier. Sie wird von templates/page-styleguide.blade.php
        // aus den echten Komponenten und den aktiven Tokens gerendert. Als HTML in
        // einem WYSIWYG-Feld war sie eine Kopie, die zwangslaeufig abdriftete.
        //
        // Was hier bleibt, ist die Galerie der Flexible-Layouts: die entsteht nur
        // aus ACF-Daten und rendert dort durch ihre eigenen Templates.

        // =====================================================================
        // TEIL 3: FLEXIBLE CONTENT LAYOUTS - HERO
        // =====================================================================


        $layouts[] = $this->getHeroLayoutData();

        // =====================================================================
        // TEIL 4: LAYOUT & TEXT
        // =====================================================================

        $layouts[] = $this->layout('one_column', [
            'label' => '',
            'content' => '<h2>Layout &amp; Text</h2><p>Verschiedene Spalten-Layouts für die Inhaltsstrukturierung.</p>',
            'background_color' => 'secondary',
        ]);

        $layouts[] = $this->getOneColumnLayoutData();
        $layouts[] = $this->getOneColumnImageLayoutData();
        $layouts[] = $this->getTwoColumnsLayoutData();
        $layouts[] = $this->getThreeColumnsLayoutData();
        $layouts[] = $this->getFourColumnsLayoutData();
        $layouts[] = $this->getOneThirdTwoThirdsLayoutData();
        $layouts[] = $this->getTwoThirdsOneThirdLayoutData();
        $layouts[] = $this->getTwoColumnsImagesLayoutData();
        $layouts[] = $this->getThreeColumnsImagesLayoutData();
        $layouts[] = $this->getFourColumnsImagesLayoutData();
        $layouts[] = $this->getAlertLayoutData();
        $layouts[] = $this->getEmbedLayoutData();
        $layouts[] = $this->getDividerLayoutData();

        // =====================================================================
        // TEIL 5: INTERAKTIVE ELEMENTE
        // =====================================================================

        $layouts[] = $this->layout('one_column', [
            'label' => '',
            'content' => '<h2>Interaktive Elemente</h2><p>Layouts mit Benutzerinteraktion wie Akkordeons und Tabs.</p>',
            'background_color' => 'primary',
        ]);

        $layouts[] = $this->getAccordionLayoutData();
        $layouts[] = $this->getTabsLayoutData();

        // =====================================================================
        // TEIL 6: KARTEN & INHALTE
        // =====================================================================

        $layouts[] = $this->layout('one_column', [
            'label' => '',
            'content' => '<h2>Karten &amp; Inhalte</h2><p>Layouts zur Darstellung von Features, Team, Preisen und mehr.</p>',
            'background_color' => 'secondary',
        ]);

        $layouts[] = $this->getCardsLayoutData();
        $layouts[] = $this->getTestimonialsLayoutData();
        $layouts[] = $this->getQuoteLayoutData();
        $layouts[] = $this->getTeamLayoutData();
        $layouts[] = $this->getStatsLayoutData();
        $layouts[] = $this->getPricingLayoutData();
        $layouts[] = $this->getTimelineLayoutData();
        $layouts[] = $this->getPostsLayoutData();

        // =====================================================================
        // TEIL 7: MEDIEN
        // =====================================================================

        $layouts[] = $this->layout('one_column', [
            'label' => '',
            'content' => '<h2>Medien</h2><p>Layouts für Bilder, Videos und Galerien.</p>',
            'background_color' => 'primary',
        ]);

        $layouts[] = $this->getImageLayoutData();
        $layouts[] = $this->getGalleryLayoutData();
        $layouts[] = $this->getBeforeAfterLayoutData();
        $layouts[] = $this->getVideoLayoutData();
        $layouts[] = $this->getLogoSliderLayoutData();

        // =====================================================================
        // TEIL 8: KONTAKT & STANDORT
        // =====================================================================

        $layouts[] = $this->layout('one_column', [
            'label' => '',
            'content' => '<h2>Kontakt &amp; Standort</h2><p>Layouts für Kontaktformulare und Kartenansichten.</p>',
            'background_color' => 'secondary',
        ]);

        $layouts[] = $this->getContactFormLayoutData();
        $layouts[] = $this->getNewsletterLayoutData();
        $layouts[] = $this->getMapLayoutData();

        // =====================================================================
        // TEIL 9: CALL-TO-ACTION
        // =====================================================================

        $layouts[] = $this->layout('one_column', [
            'label' => '',
            'content' => '<h2>Call-to-Action</h2><p>Auffällige Handlungsaufforderungen für wichtige Konversionen.</p>',
            'background_color' => 'primary',
        ]);

        $layouts[] = $this->getCtaLayoutData();
        $layouts[] = $this->getButtonLayoutData();

        // =====================================================================
        // TEIL 10: DATEN & TABELLEN
        // =====================================================================

        $layouts[] = $this->layout('one_column', [
            'label' => '',
            'content' => '<h2>Daten &amp; Tabellen</h2><p>Strukturierte Darstellung von tabellarischen Daten.</p>',
            'background_color' => 'secondary',
        ]);

        $layouts[] = $this->getTableLayoutData();

        // Pflichtabdeckung: jede Auswahlmoeglichkeit mindestens einmal.
        $layouts = array_merge($layouts, $this->getVariantCatalog());
        $layouts = array_merge($layouts, $this->getStateCatalog());

        return $layouts;
    }

    // =========================================================================
    // LAYOUT DATA GENERATORS
    // =========================================================================

    /** @return array<string, mixed> */
    private function getHeroLayoutData(): array
    {
        return $this->layout('hero', [
            'variant' => 'background',
            'badge' => 'Design System',
            'title' => 'Willkommen auf unserer Website',
            'copy' => 'Wir bauen dir Lösungen, die zu deinen Anforderungen passen. Mit langjähriger Erfahrung und einem Team, das zuhört.',
            'background_image' => $this->imageId(1),
            'overlay_opacity' => 70,
            'cta_primary' => ['title' => 'Mehr erfahren', 'url' => '#features', 'target' => ''],
            'cta_secondary' => ['title' => 'Kontakt aufnehmen', 'url' => '#kontakt', 'target' => ''],
        ]);
    }

    /** @return array<string, mixed> */
    private function getOneColumnLayoutData(): array
    {
        return $this->layout('one_column', [
            'label' => 'Über uns',
            'content' => '<h3>Einspaltiger Inhalt</h3><p>Ein Beispiel für einen einspaltigen Textblock. Hier kannst du längere Texte, Überschriften und andere Inhalte platzieren. Der Text fließt über die gesamte verfügbare Breite.</p><p>Nutze dieses Layout für Einleitungen, ausführliche Beschreibungen oder Mitteilungen, die die volle Aufmerksamkeit brauchen.</p>',
            'background_color' => 'primary',
        ]);
    }

    /** @return array<string, mixed> */
    private function getOneColumnImageLayoutData(): array
    {
        return $this->layout('one_column_image', [
            'show_section_header' => false,
            'section_chip' => '',
            'section_headline' => '',
            'section_description' => '',
            'section_alignment' => 'center',
            'label' => 'Fallstudie',
            'image' => $this->imageId(1),
            'content' => '<h4>Projekt Alpha</h4><p>Eine ausführliche Fallstudie mit Bild und Beschreibung. Dieses Layout eignet sich für Referenzen, Case Studies oder einzelne Highlight-Projekte.</p>',
            'accordion' => [
                ['title' => 'Welche Herausforderung gab es?', 'content' => '<p>Der Kunde benötigte eine skalierbare Lösung für wachsende Anforderungen bei gleichbleibender Ladezeit.</p>'],
                ['title' => 'Wie wurde sie gelöst?', 'content' => '<p>Durch eine modulare Architektur und gezielte Performance-Optimierungen konnte die Ladezeit spürbar reduziert werden.</p>'],
            ],
            'background_color' => 'primary',
        ]);
    }

    /** @return array<string, mixed> */
    private function getTwoColumnsLayoutData(): array
    {
        return $this->layout('two_columns', [
            'column_1' => '<h4>Linke Spalte</h4><p>Dies ist der Inhalt der linken Spalte. Beide Spalten haben die gleiche Breite (50/50). Ideal für vergleichende Darstellungen oder parallele Informationen.</p>',
            'column_2' => '<h4>Rechte Spalte</h4><p>Dies ist der Inhalt der rechten Spalte. Die Spalten passen sich automatisch an die Bildschirmgröße an und werden auf mobilen Geräten untereinander angezeigt.</p>',
            'background_color' => 'secondary',
        ]);
    }

    /** @return array<string, mixed> */
    private function getThreeColumnsLayoutData(): array
    {
        return $this->layout('three_columns', [
            'column_1' => '<h4>Spalte 1</h4><p>Erste von drei gleichmäßig verteilten Spalten. Perfekt für die Darstellung von drei Hauptthemen oder Produkten.</p>',
            'column_2' => '<h4>Spalte 2</h4><p>Die mittlere Spalte eignet sich gut für das wichtigste Element, da sie automatisch im Fokus des Betrachters liegt.</p>',
            'column_3' => '<h4>Spalte 3</h4><p>Die dritte Spalte rundet das Layout ab. Auf kleineren Bildschirmen stapeln sich die Spalten vertikal.</p>',
            'background_color' => 'primary',
        ]);
    }

    /** @return array<string, mixed> */
    private function getFourColumnsLayoutData(): array
    {
        return $this->layout('four_columns', [
            'column_1' => '<h4>Spalte 1</h4><p>Erste von vier Spalten für kompakte Inhalte.</p>',
            'column_2' => '<h4>Spalte 2</h4><p>Zweite Spalte mit kurzem Inhalt.</p>',
            'column_3' => '<h4>Spalte 3</h4><p>Dritte Spalte für weitere Infos.</p>',
            'column_4' => '<h4>Spalte 4</h4><p>Vierte Spalte zum Abschluss.</p>',
            'background_color' => 'tertiary',
        ]);
    }

    /** @return array<string, mixed> */
    private function getOneThirdTwoThirdsLayoutData(): array
    {
        return $this->layout('one_third_two_thirds', [
            'column_1' => '<h4>Schmal</h4><p>Diese schmale Spalte (1/3) eignet sich für Nebensachen, Navigationen oder ergänzende Informationen.</p>',
            'column_2' => '<h4>Breit</h4><p>Die breite Spalte (2/3) nimmt den Hauptinhalt auf. Dieses asymmetrische Layout lenkt die Aufmerksamkeit auf den wichtigeren Teil und eignet sich gut für Artikel mit Seitenleiste.</p>',
            'background_color' => 'primary',
        ]);
    }

    /** @return array<string, mixed> */
    private function getTwoThirdsOneThirdLayoutData(): array
    {
        return $this->layout('two_thirds_one_third', [
            'column_1' => '<h4>Hauptinhalt</h4><p>Die breite linke Spalte (2/3) enthält den Hauptinhalt. Dieses Layout ist das Gegenstück zum vorherigen Block und bietet Flexibilität bei der Seitengestaltung.</p>',
            'column_2' => '<h4>Sidebar</h4><p>Die schmalere rechte Spalte (1/3) kann für Zusatzinformationen genutzt werden.</p>',
            'background_color' => 'secondary',
        ]);
    }

    /** @return array<string, mixed> */
    private function getTwoColumnsImagesLayoutData(): array
    {
        return $this->layout('two_columns_images', [
            'image_1' => $this->imageId(2),
            'column_1' => '<h4>Projekt A</h4><p>Beschreibung des ersten Projekts mit Bild. Die Karte kombiniert visuelle und textliche Elemente.</p>',
            'image_2' => $this->imageId(3),
            'column_2' => '<h4>Projekt B</h4><p>Beschreibung des zweiten Projekts. Beide Karten sind gleich groß und wirken ausgewogen.</p>',
            'background_color' => 'primary',
        ]);
    }

    /** @return array<string, mixed> */
    private function getThreeColumnsImagesLayoutData(): array
    {
        return $this->layout('three_columns_images', [
            'show_section_header' => true,
            'section_chip' => 'Portfolio',
            'section_headline' => 'Unsere Projekte im Überblick',
            'section_description' => 'Drei ausgewählte Referenzen mit Bild und kurzer Beschreibung.',
            'section_alignment' => 'center',
            'label_1' => '',
            'image_1' => $this->imageId(2),
            'column_1' => '<h3>Projekt A</h3><p>Relaunch einer Unternehmenswebsite mit Fokus auf Performance und Barrierefreiheit.</p>',
            'accordion_1' => [],
            'label_2' => '',
            'image_2' => $this->imageId(3),
            'column_2' => '<h3>Projekt B</h3><p>Entwicklung eines individuellen Mitgliederbereichs mit geschütztem Download-Center.</p>',
            'accordion_2' => [],
            'label_3' => '',
            'image_3' => $this->imageId(4),
            'column_3' => '<h3>Projekt C</h3><p>Konzeption und Umsetzung eines mehrsprachigen Onlineshops.</p>',
            'accordion_3' => [],
            'background_color' => 'secondary',
        ]);
    }

    /** @return array<string, mixed> */
    private function getFourColumnsImagesLayoutData(): array
    {
        return $this->layout('four_columns_images', [
            'show_section_header' => false,
            'section_chip' => '',
            'section_headline' => '',
            'section_description' => '',
            'section_alignment' => 'center',
            'label_1' => 'Schritt 1',
            'image_1' => $this->imageId(5),
            'column_1' => '<h4>Beratung</h4><p>Analyse der Anforderungen und Zielsetzung.</p>',
            'accordion_1' => [],
            'label_2' => 'Schritt 2',
            'image_2' => $this->imageId(6),
            'column_2' => '<h4>Konzeption</h4><p>Erarbeitung von Struktur und Design.</p>',
            'accordion_2' => [],
            'label_3' => 'Schritt 3',
            'image_3' => $this->imageId(1),
            'column_3' => '<h4>Umsetzung</h4><p>Technische Realisierung des Projekts.</p>',
            'accordion_3' => [],
            'label_4' => 'Schritt 4',
            'image_4' => $this->imageId(2),
            'column_4' => '<h4>Übergabe</h4><p>Launch und Einweisung in die Pflege der Inhalte.</p>',
            'accordion_4' => [],
            'background_color' => 'brand-subtle',
        ]);
    }

    /** @return array<string, mixed> */
    private function getQuoteLayoutData(): array
    {
        return $this->layout('quote', [
            'quote' => 'Die Foerderung hat uns den Start ermoeglicht, und die Begleitung danach hat uns gehalten.',
            'author' => 'Maria Beispiel',
            'role' => 'Projektleiterin',
            'image' => $this->imageId(1),
            'size' => 'md',
            'background_color' => 'secondary',
        ]);
    }

    /** @return array<string, mixed> */
    private function getAlertLayoutData(): array
    {
        return $this->layout('alert', [
            'variant' => 'info',
            'title' => 'Bitte beachten',
            'content' => '<p>Ein Hinweis steht immer ueber dem Abschnitt, auf den er sich bezieht.</p>',
            'background_color' => 'primary',
        ]);
    }

    /** @return array<string, mixed> */
    private function getEmbedLayoutData(): array
    {
        // YouTube ist von Haus aus freigegeben, deshalb zeigt die Demo einen Host,
        // der ohne Eintrag in den Einstellungen ohnehin laedt.
        return $this->layout('embed', [
            'title' => 'Eingebetteter Inhalt',
            'url' => 'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ',
            'iframe_title' => 'Beispiel-Einbettung',
            'aspect_ratio' => '16-9',
            'background_color' => 'primary',
        ]);
    }

    /** @return array<string, mixed> */
    private function getDividerLayoutData(): array
    {
        return $this->layout('divider', ['background_color' => 'brand-subtle']);
    }

    /** @return array<string, mixed> */
    private function getAccordionLayoutData(): array
    {
        return $this->layout('accordion', [
            'accordion' => [
                ['title' => 'Was bietet ihr an?', 'content' => '<p>Von der Beratung über die Umsetzung bis zur langfristigen Betreuung. Der Fokus liegt auf Lösungen, die zu deinem Vorhaben passen, nicht auf Bausätzen von der Stange.</p>'],
                ['title' => 'Wie lange dauert ein typisches Projekt?', 'content' => '<p>Die Projektdauer hängt vom Umfang ab. Kleinere Projekte können innerhalb weniger Wochen abgeschlossen werden, während umfangreichere Vorhaben mehrere Monate in Anspruch nehmen können. Wir erstellen immer einen realistischen Zeitplan.</p>'],
                ['title' => 'Wie erreiche ich euch?', 'content' => '<p>Telefonisch, per E-Mail oder über das Kontaktformular. Wir melden uns in der Regel innerhalb von 24 Stunden.</p>'],
                ['title' => 'Gibt es eine Mindestvertragslaufzeit?', 'content' => '<p>Nein. Die Vertragsmodelle sind flexibel und ohne lange Bindung, du kannst auch projektweise buchen.</p>'],
            ],
            'background_color' => 'primary',
        ]);
    }

    /** @return array<string, mixed> */
    private function getTabsLayoutData(): array
    {
        return $this->layout('tabs', [
            'title' => '',
            'tabs' => [
                ['title' => 'Übersicht', 'icon' => 'eye', 'content' => '<h3>Allgemeine Informationen</h3><p>Dies ist der Inhalt des ersten Tabs. Tabs eignen sich hervorragend, um zusammengehörige Informationen zu strukturieren und übersichtlich darzustellen, ohne die Seite mit zu viel Text zu überladen.</p>'],
                ['title' => 'Funktionen', 'icon' => 'check', 'content' => '<h3>Unsere Funktionen</h3><ul><li>Automatische Anpassung an alle Geräte</li><li>Schnelle Ladezeiten</li><li>Benutzerfreundliche Oberfläche</li><li>Regelmäßige Updates</li></ul>'],
                ['title' => 'Preise', 'icon' => 'calendar', 'content' => '<h3>Preisgestaltung</h3><p>Die Preise richten sich nach dem Umfang deines Vorhabens. Schreib uns für ein individuelles Angebot.</p>'],
            ],
            'background_color' => 'secondary',
        ]);
    }

    /** @return array<string, mixed> */
    private function getCardsLayoutData(): array
    {
        return $this->layout('cards', [
            'title' => 'Unsere Leistungen',
            'cards' => [
                ['icon' => 'user', 'title' => 'Beratung', 'content' => 'Beratung, die bei deinen Zielen anfängt und nicht bei unserem Baukasten.', 'link' => ['title' => 'Mehr erfahren', 'url' => '#', 'target' => '']],
                ['icon' => 'check', 'title' => 'Umsetzung', 'content' => 'Umsetzung deiner Projekte, verlässlich und mit aktueller Technik.', 'link' => ['title' => 'Details ansehen', 'url' => '#', 'target' => '']],
                ['icon' => 'phone', 'title' => 'Support', 'content' => 'Betreuung über den Launch hinaus, mit Support, der antwortet.', 'link' => ['title' => 'Kontakt', 'url' => '#', 'target' => '']],
            ],
            'columns' => '3',
            'background_color' => 'primary',
        ]);
    }

    /** @return array<string, mixed> */
    private function getTestimonialsLayoutData(): array
    {
        return $this->layout('testimonials', [
            'title' => 'Das sagen unsere Kunden',
            'testimonials' => [
                ['quote' => 'Die Zusammenarbeit war von Anfang an professionell und unkompliziert. Das Ergebnis hat unsere Erwartungen übertroffen.', 'author' => 'Maria Müller', 'role' => 'Geschäftsführerin, Beispiel GmbH', 'image' => $this->imageId(1)],
                ['quote' => 'Schnelle Reaktionszeiten und kompetente Beratung. Wir können das Team uneingeschränkt empfehlen.', 'author' => 'Thomas Schmidt', 'role' => 'Projektleiter, Muster AG', 'image' => $this->imageId(2)],
            ],
            'columns' => '2',
            'background_color' => 'brand-subtle',
        ]);
    }

    /** @return array<string, mixed> */
    private function getTeamLayoutData(): array
    {
        return $this->layout('team', [
            'title' => 'Unser Team',
            'members' => [
                ['image' => $this->portraitId(), 'name' => 'Anna Weber', 'position' => 'Geschäftsführerin', 'bio' => 'Seit 2015 führt Anna das Unternehmen mit Leidenschaft.', 'email' => 'anna@beispiel.de', 'linkedin' => 'https://linkedin.com/in/beispiel'],
                ['image' => $this->portraitId(), 'name' => 'Michael Braun', 'position' => 'Technischer Leiter', 'bio' => 'Michael verantwortet alle technischen Entwicklungen.', 'email' => 'michael@beispiel.de', 'linkedin' => ''],
                ['image' => $this->portraitId(), 'name' => 'Sarah Klein', 'position' => 'Marketing Managerin', 'bio' => 'Sarah sorgt für die Sichtbarkeit unserer Projekte.', 'email' => 'sarah@beispiel.de', 'linkedin' => 'https://linkedin.com/in/beispiel'],
            ],
            'columns' => '3',
            'background_color' => 'primary',
        ]);
    }

    /** @return array<string, mixed> */
    private function getStatsLayoutData(): array
    {
        return $this->layout('stats', [
            'title' => 'Zahlen & Fakten',
            'stats' => [
                ['number' => 250, 'suffix' => '+', 'label' => 'Zufriedene Kunden', 'icon' => ''],
                ['number' => 15, 'suffix' => '', 'label' => 'Jahre Erfahrung', 'icon' => ''],
                ['number' => 500, 'suffix' => '+', 'label' => 'Projekte abgeschlossen', 'icon' => ''],
                ['number' => 98, 'suffix' => '%', 'label' => 'Kundenzufriedenheit', 'icon' => ''],
            ],
            'background_color' => 'secondary',
        ]);
    }

    /** @return array<string, mixed> */
    private function getPricingLayoutData(): array
    {
        return $this->layout('pricing_table', [
            'title' => 'Unsere Pakete',
            'plans' => [
                ['name' => 'Starter', 'price' => '49 EUR', 'period' => 'Monat', 'features' => '<ul><li>Grundfunktionen</li><li>E-Mail Support</li><li>5 Projekte</li><li>1 Benutzer</li></ul>', 'cta' => ['title' => 'Auswählen', 'url' => '#', 'target' => ''], 'is_featured' => false],
                ['name' => 'Professional', 'price' => '99 EUR', 'period' => 'Monat', 'features' => '<ul><li>Alle Funktionen</li><li>Prioritäts-Support</li><li>Unbegrenzte Projekte</li><li>5 Benutzer</li><li>API-Zugang</li></ul>', 'cta' => ['title' => 'Auswählen', 'url' => '#', 'target' => ''], 'is_featured' => true],
                ['name' => 'Enterprise', 'price' => 'Auf Anfrage', 'period' => '', 'features' => '<ul><li>Individuelle Lösungen</li><li>Dedicated Support</li><li>On-Premise Option</li><li>Unbegrenzte Benutzer</li><li>SLA-Garantie</li></ul>', 'cta' => ['title' => 'Kontakt', 'url' => '#', 'target' => ''], 'is_featured' => false],
            ],
            'background_color' => 'secondary',
        ]);
    }

    /** @return array<string, mixed> */
    private function getTimelineLayoutData(): array
    {
        return $this->layout('timeline', [
            'title' => 'Unsere Geschichte',
            'events' => [
                ['year' => '2010', 'title' => 'Gründung', 'content' => '<p>Unser Unternehmen wurde mit einer Vision gegründet: innovative Lösungen für unsere Kunden zu entwickeln.</p>', 'image' => $this->imageId(1)],
                ['year' => '2015', 'title' => 'Expansion', 'content' => '<p>Wir haben unser Team erweitert und neue Standorte eröffnet, um näher an unseren Kunden zu sein.</p>', 'image' => null],
                ['year' => '2020', 'title' => 'Digitale Transformation', 'content' => '<p>Mit der Einführung neuer digitaler Dienste haben wir unseren Service auf ein neues Level gehoben.</p>', 'image' => $this->imageId(2)],
                ['year' => 'Heute', 'title' => 'Marktführer', 'content' => '<p>Heute sind wir stolz darauf, einer der führenden Anbieter in unserer Branche zu sein.</p>', 'image' => null],
            ],
            'background_color' => 'primary',
        ]);
    }

    /** @return array<string, mixed> */
    private function getImageLayoutData(): array
    {
        return $this->layout('image', [
            'image' => $this->imageId(1),
            'show_border' => false,
            'show_caption' => true,
            'background_color' => 'primary',
        ]);
    }

    /** @return array<string, mixed> */
    private function getGalleryLayoutData(): array
    {
        $images = [];
        for ($i = 1; $i <= 6; $i++) {
            $id = $this->imageId($i);
            if ($id) {
                $images[] = $id;
            }
        }

        return $this->layout('gallery', [
            'title' => 'Bildergalerie',
            'images' => $images,
            'columns' => '3',
            'background_color' => 'secondary',
        ]);
    }

    /** @return array<string, mixed> */
    private function getBeforeAfterLayoutData(): array
    {
        return $this->layout('before_after', [
            'title' => 'Vorher vs. Nachher',
            'image_before' => $this->imageId(1),
            'image_after' => $this->imageId(2),
            'label_before' => 'Vorher',
            'label_after' => 'Nachher',
            'background_color' => 'primary',
        ]);
    }

    /** @return array<string, mixed> */
    private function getVideoLayoutData(): array
    {
        return $this->layout('video', [
            'source' => 'external',
            'video' => '',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            // Mit Standbild: so soll es aussehen. Eine Katalog-Instanz weiter
            // unten laesst es bewusst weg, damit beide Zustaende sichtbar sind.
            'poster' => $this->imageId(3),
            'background_color' => 'primary',
        ]);
    }

    /** @return array<string, mixed> */
    private function getLogoSliderLayoutData(): array
    {
        $logos = [];
        for ($i = 1; $i <= 6; $i++) {
            $logoId = $this->logoId($i);
            if ($logoId) {
                $logos[] = ['logo' => $logoId, 'name' => "Partner {$i}", 'link' => ''];
            }
        }

        if (empty($logos)) {
            for ($i = 1; $i <= 4; $i++) {
                $imgId = $this->imageId($i);
                if ($imgId) {
                    $logos[] = ['logo' => $imgId, 'name' => "Partner {$i}", 'link' => ''];
                }
            }
        }

        return $this->layout('logo_slider', [
            'title' => 'Unsere Partner',
            'logos' => $logos,
            'autoplay' => true,
            'background_color' => 'secondary',
        ]);
    }

    /** @return array<string, mixed> */
    private function getCtaLayoutData(): array
    {
        return $this->layout('cta', [
            'title' => 'Bereit loszulegen?',
            'content' => 'Schreib uns für ein unverbindliches Gespräch. Wir hören zu, bevor wir etwas vorschlagen.',
            'button' => ['title' => 'Jetzt Kontakt aufnehmen', 'url' => '#kontakt', 'target' => ''],
        ]);
    }

    /** @return array<string, mixed> */
    private function getButtonLayoutData(): array
    {
        return $this->layout('button', [
            'button' => ['title' => 'Beratungstermin vereinbaren', 'url' => '#kontakt', 'target' => ''],
            'variant' => 'secondary',
            'alignment' => 'center',
        ]);
    }

    /** @return array<string, mixed> */
    private function getContactFormLayoutData(): array
    {
        return $this->layout('contact_form', [
            'title' => 'Schreib uns',
            'content' => '<p>Fragen? Schreib uns über das Formular, wir melden uns zeitnah.</p>',
            'form_id' => $this->getFirstContactForm7Id(),
            'show_contact_info' => true,
            'background_color' => 'secondary',
        ]);
    }

    /** @return array<string, mixed> */
    private function getNewsletterLayoutData(): array
    {
        return $this->layout('newsletter', [
            'title' => 'Auf dem Laufenden bleiben',
            'content' => 'Viermal im Jahr ein kurzer Bericht darueber, was gefoerdert wurde.',
            'action_url' => 'https://beispiel.us1.list-manage.com/subscribe/post?u=demo&id=demo',
            'email_field' => 'EMAIL',
            'button_label' => 'Anmelden',
            'note' => 'Die Anmeldung laeuft ueber unseren Versanddienst. Abmeldung jederzeit ueber den Link in jeder E-Mail.',
            'background_color' => 'brand-subtle',
        ]);
    }

    /** @return array<string, mixed> */
    private function getMapLayoutData(): array
    {
        return $this->layout('map', [
            'title' => 'So findest du uns',
            'address' => 'Musterstraße 123, 12345 Berlin, Deutschland',
            'embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2427.924165409515!2d13.404954!3d52.520008!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47a84e373f035901%3A0x42120465b5e3b70!2sBerlin!5e0!3m2!1sde!2sde!4v1234567890',
            'height' => 400,
            'show_directions_link' => true,
            'background_color' => 'primary',
        ]);
    }

    /** @return array<string, mixed> */
    private function getPostsLayoutData(): array
    {
        return $this->layout('posts', [
            'title' => 'Aktuelle Beiträge',
            'post_type' => 'post',
            'posts_per_page' => 3,
            'category' => '',
            'show_excerpt' => true,
            'show_date' => true,
            'show_author' => false,
            'columns' => 3,
            'background_color' => 'secondary',
        ]);
    }

    /** @return array<string, mixed> */
    private function getTableLayoutData(): array
    {
        return $this->layout('table', [
            'title' => 'Preisübersicht',
            'headers' => [['label' => 'Leistung'], ['label' => 'Starter'], ['label' => 'Professional']],
            'rows' => [
                ['cells' => [['content' => 'Beratung'], ['content' => '2 Std./Monat'], ['content' => 'Unbegrenzt']]],
                ['cells' => [['content' => 'Support'], ['content' => 'E-Mail'], ['content' => 'Telefon & E-Mail']]],
                ['cells' => [['content' => 'Projekte'], ['content' => '5'], ['content' => 'Unbegrenzt']]],
                ['cells' => [['content' => 'Speicherplatz'], ['content' => '10 GB'], ['content' => '100 GB']]],
            ],
            'striped' => true,
            'bordered' => false,
            'background_color' => 'primary',
        ]);
    }

    // =========================================================================
    // DESIGN SYSTEM HTML GENERATORS
    // =========================================================================

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Build a single ACF Flexible Content layout array.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function layout(string $layoutName, array $data): array
    {
        return array_merge(['acf_fc_layout' => $layoutName], $data);
    }

    private function imageId(int $index): ?int
    {
        return $this->imageIds["placeholder_{$index}"] ?? null;
    }

    private function logoId(int $index): ?int
    {
        return $this->imageIds["logo_{$index}"] ?? null;
    }

    /**
     * Get the first Contact Form 7 form ID, or null if CF7 is not active.
     */
    private function getFirstContactForm7Id(): ?int
    {
        if (!class_exists('WPCF7')) {
            return null;
        }

        // Das vom Theme angelegte Formular bevorzugen: es siezt, kennzeichnet
        // Pflichtfelder und hat die Einwilligung. Das erste beliebige Formular
        // waere Zufall.
        $default = \WordpressStarter\PluginConfigurators\ContactForm7Configurator::defaultFormId();
        if ($default > 0) {
            return $default;
        }

        $forms = get_posts([
            'post_type' => 'wpcf7_contact_form',
            'posts_per_page' => 1,
            'orderby' => 'ID',
            'order' => 'ASC',
            'post_status' => 'publish',
        ]);

        return !empty($forms) ? (int) $forms[0]->ID : null;
    }

    // =========================================================================
    // VARIANTEN-KATALOG
    //
    // Die Instanzen oben sind die kuratierte Galerie: je Layout eine
    // Referenzdarstellung. Hier folgt die Pflichtabdeckung, damit jede
    // Auswahlmoeglichkeit mindestens einmal gerendert wird.
    //
    // Warum das noetig ist: bis 2026-08-10 zeigte der Styleguide pro Modul genau
    // eine Variante. Genau die beiden nie geseedeten Hintergrundfarben, brand und
    // inverse, waren defekt. Was der Styleguide nicht zeigt, prueft niemand.
    //
    // Abgesichert durch tests/Unit/Content/StyleguideVariantCoverageTest.php.
    // Neue Choice heisst neue Instanz hier, im selben Commit.
    // =========================================================================

    /**
     * Alle Auswahlwerte, die die kuratierte Galerie nicht zeigt.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getVariantCatalog(): array
    {
        $catalog = [];

        $catalog[] = $this->layout('one_column', [
            'content' => '<h2>Varianten-Katalog</h2><p>Ab hier folgt je Auswahlmöglichkeit eine Instanz. Diese Sektionen sind Prüfmaterial, kein Gestaltungsvorschlag.</p>',
            'background_color' => 'secondary',
        ]);

        // --- Hero: die beiden Varianten, die oben fehlen ---------------------
        $catalog[] = $this->layout('hero', [
            'variant' => 'centered',
            'badge' => 'Variante',
            'title' => 'Hero, zentriert',
            'copy' => 'Diese Variante stellt Text und Buttons mittig über einer Flächenfarbe dar.',
            'cta_primary' => ['title' => 'Primär', 'url' => '#', 'target' => ''],
            'cta_secondary' => ['title' => 'Sekundär', 'url' => '#', 'target' => ''],
            'background_color' => 'secondary',
        ]);

        $catalog[] = $this->layout('hero', [
            'variant' => 'split',
            'badge' => 'Variante',
            'title' => 'Hero, geteilt',
            'copy' => 'Text links, Bild rechts. Auf kleinen Bildschirmen stapeln sich beide Hälften.',
            'image' => $this->imageId(2),
            'cta_primary' => ['title' => 'Primär', 'url' => '#', 'target' => ''],
            'background_color' => 'primary',
        ]);

        // --- Sektionskopf an, Ausrichtung links -------------------------------
        // Beide Werte fehlen in der kuratierten Galerie, weil dort der Default
        // greift. Je Layout eine Instanz deckt beide ab.
        $header = static fn (string $headline): array => [
            'show_section_header' => true,
            'section_chip' => 'Sektionskopf',
            'section_headline' => $headline,
            'section_description' => 'Sektionskopf eingeschaltet, linksbündig ausgerichtet.',
            'section_alignment' => 'left',
        ];

        $catalog[] = $this->layout('one_column', array_merge($header('Eine Spalte mit Sektionskopf'), [
            'content' => '<p>Textblock unter einem linksbündigen Sektionskopf.</p>',
            'background_color' => 'primary',
        ]));

        $catalog[] = $this->layout('two_columns', array_merge($header('Zwei Spalten mit Sektionskopf'), [
            'column_1' => '<p>Linke Spalte.</p>',
            'column_2' => '<p>Rechte Spalte.</p>',
            'background_color' => 'secondary',
        ]));

        $catalog[] = $this->layout('three_columns', array_merge($header('Drei Spalten mit Sektionskopf'), [
            'column_1' => '<p>Erste Spalte.</p>',
            'column_2' => '<p>Zweite Spalte.</p>',
            'column_3' => '<p>Dritte Spalte.</p>',
            'background_color' => 'primary',
        ]));

        $catalog[] = $this->layout('four_columns', array_merge($header('Vier Spalten mit Sektionskopf'), [
            'column_1' => '<p>Erste Spalte.</p>',
            'column_2' => '<p>Zweite Spalte.</p>',
            'column_3' => '<p>Dritte Spalte.</p>',
            'column_4' => '<p>Vierte Spalte.</p>',
            'background_color' => 'secondary',
        ]));

        $catalog[] = $this->layout('one_third_two_thirds', array_merge($header('1/3 + 2/3 mit Sektionskopf'), [
            'column_1' => '<p>Schmale Spalte.</p>',
            'column_2' => '<p>Breite Spalte.</p>',
            'background_color' => 'primary',
        ]));

        $catalog[] = $this->layout('two_thirds_one_third', array_merge($header('2/3 + 1/3 mit Sektionskopf'), [
            'column_1' => '<p>Breite Spalte.</p>',
            'column_2' => '<p>Schmale Spalte.</p>',
            'background_color' => 'secondary',
        ]));

        $catalog[] = $this->layout('one_column_image', array_merge($header('Eine Spalte mit Bild und Sektionskopf'), [
            'image' => $this->imageId(3),
            'content' => '<p>Bild und Text unter einem linksbündigen Sektionskopf.</p>',
            'accordion' => [],
            'background_color' => 'primary',
        ]));

        $catalog[] = $this->layout('two_columns_images', array_merge($header('Zwei Spalten mit Bildern und Sektionskopf'), [
            'label_1' => '',
            'image_1' => $this->imageId(2),
            'column_1' => '<p>Erste Karte.</p>',
            'accordion_1' => [],
            'label_2' => '',
            'image_2' => $this->imageId(3),
            'column_2' => '<p>Zweite Karte.</p>',
            'accordion_2' => [],
            'background_color' => 'secondary',
        ]));

        $catalog[] = $this->layout('four_columns_images', array_merge($header('Vier Spalten mit Bildern und Sektionskopf'), [
            'label_1' => '',
            'image_1' => $this->imageId(1),
            'column_1' => '<p>Erste Karte.</p>',
            'accordion_1' => [],
            'label_2' => '',
            'image_2' => $this->imageId(2),
            'column_2' => '<p>Zweite Karte.</p>',
            'accordion_2' => [],
            'label_3' => '',
            'image_3' => $this->imageId(3),
            'column_3' => '<p>Dritte Karte.</p>',
            'accordion_3' => [],
            'label_4' => '',
            'image_4' => $this->imageId(4),
            'column_4' => '<p>Vierte Karte.</p>',
            'accordion_4' => [],
            'background_color' => 'primary',
        ]));

        // Hier fehlt der ausgeschaltete Sektionskopf, weil die kuratierte
        // Instanz ihn einschaltet.
        $catalog[] = $this->layout('three_columns_images', [
            'show_section_header' => false,
            'section_chip' => '',
            'section_headline' => '',
            'section_description' => '',
            'section_alignment' => 'left',
            'label_1' => '',
            'image_1' => $this->imageId(2),
            'column_1' => '<h3>Ohne Sektionskopf</h3><p>Erste Karte.</p>',
            'accordion_1' => [],
            'label_2' => '',
            'image_2' => $this->imageId(3),
            'column_2' => '<h3>Ohne Sektionskopf</h3><p>Zweite Karte.</p>',
            'accordion_2' => [],
            'label_3' => '',
            'image_3' => $this->imageId(4),
            'column_3' => '<h3>Ohne Sektionskopf</h3><p>Dritte Karte.</p>',
            'accordion_3' => [],
            'background_color' => 'secondary',
        ]);

        // --- Hintergrundflaechen brand und inverse ---------------------------
        // Stufe 2 der Abdeckung: beide Flaechen mindestens einmal, auf einem
        // textlastigen und einem kartenbasierten Modul.
        $catalog[] = $this->layout('one_column', [
            'content' => '<h3>Fläche: Markenfarbe</h3><p>Fließtext auf der Markenfläche. Diese Kombination war bis zum Audit vom 10.08.2026 nie im Styleguide zu sehen.</p>',
            'background_color' => 'brand',
        ]);

        $catalog[] = $this->layout('one_column', [
            'content' => '<h3>Fläche: Dunkel (Invers)</h3><p>Fließtext auf der dunklen Fläche. Ebenfalls nie geprüft, bis der Kontrastscan sie eingefordert hat.</p>',
            'background_color' => 'inverse',
        ]);

        // Karten auf der Markenflaeche: die Sektion faerbt ihre Schrift hell, die
        // Karte bringt eine helle Flaeche mit. Ohne eigene Schriftfarbe auf der
        // Karte stand hier weisser Text auf weissem Grund.
        $catalog[] = $this->layout('cards', [
            'title' => 'Karten auf der Markenfläche',
            'cards' => [
                ['icon' => 'check', 'title' => 'Erste Karte', 'content' => 'Kartentext auf der Markenfläche. Er darf nicht die helle Schrift der Sektion erben.', 'link' => null],
                ['icon' => 'star', 'title' => 'Zweite Karte', 'content' => 'Prüft, dass die Karte ihre eigene Schriftfarbe mitbringt.', 'link' => null],
            ],
            'columns' => '2',
            'background_color' => 'brand',
        ]);

        $catalog[] = $this->layout('cards', [
            'title' => 'Karten auf dunkler Fläche',
            'cards' => [
                ['icon' => 'check', 'title' => 'Erste Karte', 'content' => 'Karte auf inverser Fläche.', 'link' => null],
                ['icon' => 'star', 'title' => 'Zweite Karte', 'content' => 'Prüft die Abgrenzung der Kartenfläche.', 'link' => null],
            ],
            'columns' => '2',
            'background_color' => 'inverse',
        ]);

        // Der CTA hatte seine Flaeche fest verdrahtet. Beide Varianten stehen hier,
        // weil nur der Vergleich zeigt, dass die Karte ihre Markenfarbe behaelt.
        $catalog[] = $this->layout('cta', [
            'title' => 'CTA auf sekundärer Fläche',
            'content' => 'Die Sektion faerbt sich, die Karte bleibt auf der Markenflaeche.',
            'button' => ['title' => 'Kontakt aufnehmen', 'url' => '#kontakt', 'target' => ''],
            'background_color' => 'secondary',
        ]);

        $catalog[] = $this->layout('cta', [
            'title' => 'CTA auf dunkler Fläche',
            'content' => 'Dieselbe Karte auf der inversen Flaeche.',
            'button' => ['title' => 'Kontakt aufnehmen', 'url' => '#kontakt', 'target' => ''],
            'background_color' => 'inverse',
        ]);

        // Akkordeon mit allen drei Schaltern anders herum als im Standardfall
        // darueber: erster Eintrag offen, mehrere gleichzeitig offen, und ohne
        // FAQ-Auszeichnung, weil das hier keine Fragen und Antworten sind.
        $catalog[] = $this->layout('accordion', [
            'accordion' => [
                ['title' => 'Erster Eintrag, beim Laden offen', 'content' => '<p>Dieser Eintrag ist ohne Klick sichtbar.</p>'],
                ['title' => 'Zweiter Eintrag', 'content' => '<p>Er bleibt offen, wenn ein dritter geoeffnet wird.</p>'],
                ['title' => 'Dritter Eintrag', 'content' => '<p>Mehrere Eintraege duerfen hier gleichzeitig offen stehen.</p>'],
            ],
            'first_open' => true,
            'allow_multiple' => true,
            'faq_schema' => false,
            'background_color' => 'secondary',
        ]);

        // Kartenstile: Erhoeht ist der Standard und steht schon oben, hier die
        // beiden anderen auf derselben Flaeche, damit der Unterschied auffaellt.
        $catalog[] = $this->layout('cards', [
            'title' => 'Karten im Umriss',
            'cards' => [
                ['icon' => 'check', 'title' => 'Umriss', 'content' => 'Nur eine Linie, kein Schatten.', 'link' => null],
                ['icon' => 'star', 'title' => 'Umriss', 'content' => 'Ruhiger auf dichten Seiten.', 'link' => null],
            ],
            'columns' => '2',
            'card_style' => 'outlined',
            'background_color' => 'primary',
        ]);

        $catalog[] = $this->layout('cards', [
            'title' => 'Karten gefüllt',
            'cards' => [
                ['icon' => 'check', 'title' => 'Gefüllt', 'content' => 'Eine Stufe ueber der Sektionsflaeche.', 'link' => null],
                ['icon' => 'star', 'title' => 'Gefüllt', 'content' => 'Traegt auch ohne Schatten.', 'link' => null],
            ],
            'columns' => '2',
            'card_style' => 'filled',
            'background_color' => 'primary',
        ]);

        // Beitraege als Liste und die beiden Sortierungen neben "Neueste zuerst",
        // die der Standardfall oben schon zeigt.
        $catalog[] = $this->layout('posts', [
            'title' => 'Beiträge als Liste, älteste zuerst',
            'post_type' => 'post',
            'posts_per_page' => 3,
            'category' => '',
            'show_excerpt' => true,
            'show_date' => true,
            'show_author' => false,
            'columns' => 3,
            'post_layout' => 'list',
            'orderby' => 'date_asc',
            'background_color' => 'primary',
        ]);

        $catalog[] = $this->layout('posts', [
            'title' => 'Beiträge nach Titel sortiert',
            'post_type' => 'post',
            'posts_per_page' => 3,
            'category' => '',
            'show_excerpt' => false,
            'show_date' => false,
            'show_author' => false,
            'columns' => 3,
            'orderby' => 'title',
            'background_color' => 'secondary',
        ]);

        // Preistabelle mit Umschalter: das dritte Paket hat bewusst keinen
        // Jahrespreis und muss im Jahrestarif seinen Monatspreis behalten.
        $catalog[] = $this->layout('pricing_table', [
            'title' => 'Pakete mit Umschalter Monat und Jahr',
            'plans' => [
                ['name' => 'Basis', 'price' => '19 EUR', 'period' => 'Monat', 'price_yearly' => '190 EUR', 'period_yearly' => 'Jahr', 'features' => '<ul><li>Grundfunktionen</li><li>E-Mail Support</li></ul>', 'is_featured' => false, 'cta' => ['title' => 'Wählen', 'url' => '#', 'target' => '']],
                ['name' => 'Plus', 'price' => '39 EUR', 'period' => 'Monat', 'price_yearly' => '390 EUR', 'period_yearly' => 'Jahr', 'features' => '<ul><li>Alle Funktionen</li><li>Prioritäts-Support</li></ul>', 'is_featured' => true, 'cta' => ['title' => 'Wählen', 'url' => '#', 'target' => '']],
                ['name' => 'Auf Anfrage', 'price' => 'Individuell', 'period' => '', 'price_yearly' => '', 'period_yearly' => '', 'features' => '<ul><li>Individuelle Lösung</li></ul>', 'is_featured' => false, 'cta' => ['title' => 'Kontakt', 'url' => '#', 'target' => '']],
            ],
            'billing_toggle' => true,
            'background_color' => 'primary',
        ]);

        // Seitenverhaeltnisse neben 16:9, das der Standardfall oben schon zeigt,
        // dazu ein selbst gehostetes Video, das stumm in Schleife laeuft.
        $catalog[] = $this->layout('video', [
            'source' => 'wordpress',
            'video' => $this->imageIds['video_demo'] ?? null,
            'video_url' => '',
            'poster' => $this->imageId(2),
            'aspect_ratio' => '4-3',
            'autoplay' => true,
            'loop' => true,
            'background_color' => 'primary',
        ]);

        $catalog[] = $this->layout('video', [
            'source' => 'wordpress',
            'video' => $this->imageIds['video_demo'] ?? null,
            'video_url' => '',
            'poster' => $this->imageId(3),
            'aspect_ratio' => '1-1',
            'background_color' => 'secondary',
        ]);

        $catalog[] = $this->layout('video', [
            'source' => 'wordpress',
            'video' => $this->imageIds['video_demo'] ?? null,
            'video_url' => '',
            'poster' => $this->imageId(4),
            'aspect_ratio' => '21-9',
            'background_color' => 'primary',
        ]);

        $catalog[] = $this->layout('button', [
            'button' => ['title' => 'Termin vereinbaren', 'url' => '#kontakt', 'target' => ''],
            'button_secondary' => ['title' => 'Unterlagen ansehen', 'url' => '#downloads', 'target' => ''],
            'variant' => 'primary',
            'size' => 'md',
            'alignment' => 'center',
            'background_color' => 'primary',
        ]);

        // Hinweise: alle vier Arten, der letzte zusaetzlich schliessbar.
        $catalog[] = $this->layout('alert', [
            'variant' => 'success',
            'title' => 'Antrag eingegangen',
            'content' => '<p>Der gruene Hinweis bestaetigt etwas, das geklappt hat.</p>',
            'background_color' => 'primary',
        ]);

        $catalog[] = $this->layout('alert', [
            'variant' => 'warning',
            'title' => 'Frist laeuft ab',
            'content' => '<p>Der gelbe Hinweis warnt, ohne einen Fehler zu melden.</p>',
            'background_color' => 'primary',
        ]);

        $catalog[] = $this->layout('alert', [
            'variant' => 'error',
            'title' => 'Antrag unvollstaendig',
            'content' => '<p>Der rote Hinweis meldet einen Fehler und traegt role="alert".</p>',
            'dismissible' => true,
            'background_color' => 'primary',
        ]);

        // Einzelzitat gross und ohne Bild: die zweite Groesse und der Fall ohne
        // Portrait, den die Instanz oben nicht zeigt.
        $catalog[] = $this->layout('quote', [
            'quote' => 'Ein grosses Zitat traegt den Abschnitt allein.',
            'author' => 'Jonas Muster',
            'role' => '',
            'image' => null,
            'size' => 'lg',
            'background_color' => 'brand-subtle',
        ]);

        $catalog[] = $this->layout('newsletter', [
            'title' => '',
            'content' => '',
            'action_url' => 'https://beispiel.us1.list-manage.com/subscribe/post?u=demo&id=demo',
            'email_field' => 'EMAIL',
            'button_label' => 'Eintragen',
            'note' => 'Ohne Ueberschrift und Text bleibt nur die Leiste.',
            'background_color' => 'secondary',
        ]);

        // Einbettung in den drei uebrigen Verhaeltnissen.
        $catalog[] = $this->layout('embed', [
            'title' => 'Einbettung 4:3',
            'url' => 'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ',
            'iframe_title' => 'Beispiel-Einbettung',
            'aspect_ratio' => '4-3',
            'background_color' => 'secondary',
        ]);

        $catalog[] = $this->layout('embed', [
            'title' => 'Einbettung 1:1',
            'url' => 'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ',
            'iframe_title' => 'Beispiel-Einbettung',
            'aspect_ratio' => '1-1',
            'background_color' => 'primary',
        ]);

        $catalog[] = $this->layout('embed', [
            'title' => 'Einbettung mit fester Höhe',
            'url' => 'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ',
            'iframe_title' => 'Beispiel-Einbettung',
            'aspect_ratio' => 'fixed',
            'height' => 320,
            'background_color' => 'secondary',
        ]);

        // Bildbreiten neben dem Standard, und eine Sektion ueber die volle Breite.
        $catalog[] = $this->layout('image', [
            'image' => $this->imageId(2),
            'show_border' => false,
            'show_caption' => false,
            'width' => 'narrow',
            'background_color' => 'primary',
        ]);

        $catalog[] = $this->layout('image', [
            'image' => $this->imageId(3),
            'show_border' => false,
            'show_caption' => false,
            'width' => 'wide',
            'section_width' => 'full',
            'background_color' => 'secondary',
        ]);

        // Lange Tabelle: kompakt und mit mitscrollender Kopfzeile. Kurz reicht
        // dafuer nicht, die Kopfzeile bliebe sichtbar, ohne je zu kleben.
        $catalog[] = $this->layout('table', [
            'title' => 'Kompakt mit mitscrollender Kopfzeile',
            'headers' => [['label' => 'Jahr'], ['label' => 'Anträge'], ['label' => 'Bewilligt'], ['label' => 'Summe']],
            'rows' => array_map(
                static fn (int $i): array => [
                    'cells' => [
                        ['content' => (string) ( 2010 + $i )],
                        ['content' => (string) ( 40 + $i * 3 )],
                        ['content' => (string) ( 20 + $i * 2 )],
                        ['content' => number_format(120000 + $i * 5000, 0, ',', '.') . ' EUR'],
                    ],
                ],
                range(0, 15)
            ),
            'striped' => true,
            'bordered' => false,
            'compact' => true,
            'sticky_header' => true,
            'background_color' => 'primary',
        ]);

        // --- Abstaende -------------------------------------------------------
        // Die Stufen sind nur im Vergleich lesbar, deshalb stehen sie direkt
        // untereinander und alle auf derselben Flaeche.
        $catalog[] = $this->layout('one_column', [
            'content' => '<h3>Abstand: Kompakt</h3><p>Kleinste Stufe, fuer eng gesetzte Folgeabschnitte.</p>',
            'background_color' => 'secondary',
            'section_spacing' => 'sm',
        ]);

        $catalog[] = $this->layout('one_column', [
            'content' => '<h3>Abstand: Groß</h3><p>Groesste Stufe, fuer Abschnitte, die fuer sich stehen sollen.</p>',
            'background_color' => 'secondary',
            'section_spacing' => 'xl',
        ]);

        $catalog[] = $this->layout('one_column', [
            'content' => '<h3>Abstand: Ohne</h3><p>Ohne eigenen Innenabstand, fuer direkt aneinander stossende Flaechen.</p>',
            'background_color' => 'secondary',
            'section_spacing' => 'none',
        ]);

        // --- Spaltenzahlen ---------------------------------------------------
        $catalog[] = $this->layout('cards', [
            'title' => 'Karten, vier Spalten',
            'cards' => [
                ['icon' => 'user', 'title' => 'Eins', 'content' => 'Vierspaltiges Kartenraster.', 'link' => null],
                ['icon' => 'check', 'title' => 'Zwei', 'content' => 'Vierspaltiges Kartenraster.', 'link' => null],
                ['icon' => 'phone', 'title' => 'Drei', 'content' => 'Vierspaltiges Kartenraster.', 'link' => null],
                ['icon' => 'mail', 'title' => 'Vier', 'content' => 'Vierspaltiges Kartenraster.', 'link' => null],
            ],
            'columns' => '4',
            'background_color' => 'primary',
        ]);

        $catalog[] = $this->layout('testimonials', [
            'title' => 'Kundenstimme, eine Spalte',
            'source' => 'manual',
            'testimonials' => [
                ['quote' => 'Einspaltige Kundenstimme über die volle Breite.', 'author' => 'Maria Beispiel', 'role' => 'Geschäftsführerin', 'image' => $this->imageId(1)],
            ],
            'columns' => '1',
            'background_color' => 'secondary',
        ]);

        $catalog[] = $this->layout('testimonials', [
            'title' => 'Kundenstimmen aus der Verwaltung',
            'source' => 'cpt',
            'columns' => '3',
            'background_color' => 'primary',
        ]);

        $catalog[] = $this->layout('team', [
            'title' => 'Team aus der Verwaltung',
            'source' => 'cpt',
            'columns' => '2',
            'background_color' => 'secondary',
        ]);

        $catalog[] = $this->layout('team', [
            'title' => 'Team, vier Spalten',
            'source' => 'manual',
            'members' => [
                ['image' => $this->portraitId(), 'name' => 'Person A', 'position' => 'Rolle', 'bio' => '', 'email' => '', 'linkedin' => ''],
                ['image' => $this->portraitId(), 'name' => 'Person B', 'position' => 'Rolle', 'bio' => '', 'email' => '', 'linkedin' => ''],
                ['image' => $this->portraitId(), 'name' => 'Person C', 'position' => 'Rolle', 'bio' => '', 'email' => '', 'linkedin' => ''],
                ['image' => $this->portraitId(), 'name' => 'Person D', 'position' => 'Rolle', 'bio' => '', 'email' => '', 'linkedin' => ''],
            ],
            'columns' => '4',
            'background_color' => 'primary',
        ]);

        $catalog[] = $this->layout('gallery', [
            'title' => 'Galerie, zwei Spalten',
            'images' => array_values(array_filter([$this->imageId(1), $this->imageId(2)])),
            'columns' => '2',
            'background_color' => 'secondary',
        ]);

        $catalog[] = $this->layout('gallery', [
            'title' => 'Galerie, vier Spalten',
            'images' => array_values(array_filter([$this->imageId(1), $this->imageId(2), $this->imageId(3), $this->imageId(4)])),
            'columns' => '4',
            'background_color' => 'primary',
        ]);

        $catalog[] = $this->layout('gallery', [
            'title' => 'Galerie, fünf Spalten',
            'images' => array_values(array_filter([$this->imageId(1), $this->imageId(2), $this->imageId(3), $this->imageId(4), $this->imageId(5)])),
            'columns' => '5',
            'background_color' => 'secondary',
        ]);

        // --- Beitraege --------------------------------------------------------
        $catalog[] = $this->layout('posts', [
            'title' => 'Seiten statt Beiträge, zwei Spalten, ohne Auszug und Datum',
            'post_type' => 'page',
            'posts_per_page' => 2,
            'category' => '',
            'show_excerpt' => false,
            'show_date' => false,
            'show_author' => true,
            'columns' => '2',
            'background_color' => 'primary',
        ]);

        $catalog[] = $this->layout('posts', [
            'title' => 'Beiträge, vier Spalten',
            'post_type' => 'post',
            'posts_per_page' => 4,
            'category' => '',
            'columns' => '4',
            'background_color' => 'secondary',
        ]);

        // --- Medien -----------------------------------------------------------
        $catalog[] = $this->layout('image', [
            'image' => $this->imageId(2),
            'show_border' => true,
            'show_caption' => false,
            'background_color' => 'primary',
        ]);

        $catalog[] = $this->layout('video', [
            'source' => 'wordpress',
            'video' => $this->imageIds['video_demo'] ?? null,
            'video_url' => '',
            'captions_language' => 'en',
            'poster' => $this->imageId(4),
            'background_color' => 'secondary',
        ]);

        $catalog[] = $this->layout('video', [
            'source' => 'url',
            'video' => '',
            'video_url' => '',
            'video_file_url' => $this->videoUrl(),
            'captions_language' => 'fr',
            'poster' => $this->imageId(5),
            'background_color' => 'primary',
        ]);

        $catalog[] = $this->layout('video', [
            'source' => 'external',
            'video' => '',
            'video_url' => 'https://vimeo.com/76979871',
            'captions_language' => 'es',
            // Ohne Standbild: zeigt, wie die Einwilligungsflaeche ohne Bild
            // aussieht, damit der Unterschied im Styleguide nebeneinander steht.
            'poster' => null,
            'background_color' => 'secondary',
        ]);

        $catalog[] = $this->layout('video', [
            'source' => 'external',
            'video' => '',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'captions_language' => 'it',
            'background_color' => 'primary',
        ]);

        // --- Trenner ----------------------------------------------------------
        $catalog[] = $this->layout('divider', ['style' => 'dots']);
        $catalog[] = $this->layout('divider', ['style' => 'wave']);
        $catalog[] = $this->layout('divider', ['style' => 'space', 'height' => 80]);

        // --- Button -----------------------------------------------------------
        $catalog[] = $this->layout('button', [
            'button' => ['title' => 'Primär, klein, linksbündig', 'url' => '#', 'target' => ''],
            'variant' => 'primary',
            'size' => 'sm',
            'alignment' => 'left',
        ]);

        $catalog[] = $this->layout('button', [
            'button' => ['title' => 'Dezent, groß, rechtsbündig', 'url' => '#', 'target' => ''],
            'variant' => 'ghost',
            'size' => 'lg',
            'alignment' => 'right',
        ]);

        $catalog[] = $this->layout('button', [
            'button' => ['title' => 'Invertiert über volle Breite', 'url' => '#', 'target' => ''],
            'variant' => 'inverse',
            'full_width' => true,
        ]);

        $catalog[] = $this->layout('button', [
            'button' => ['title' => 'Warnung', 'url' => '#', 'target' => ''],
            'variant' => 'danger',
        ]);

        // --- Restliche Gegenzustaende ----------------------------------------
        $catalog[] = $this->layout('table', [
            'title' => 'Tabelle mit Rahmen, ohne Zebrastreifen',
            'headers' => [['label' => 'Merkmal'], ['label' => 'Wert']],
            'rows' => [
                ['cells' => [['content' => 'Zebrastreifen'], ['content' => 'aus']]],
                ['cells' => [['content' => 'Rahmen'], ['content' => 'an']]],
            ],
            'striped' => false,
            'bordered' => true,
            'background_color' => 'secondary',
        ]);

        $catalog[] = $this->layout('contact_form', [
            'title' => 'Formular ohne Kontaktdaten',
            'content' => '<p>Gleiches Modul, aber ohne die Kontaktkarte daneben.</p>',
            'form_id' => $this->getFirstContactForm7Id(),
            'show_contact_info' => false,
            'background_color' => 'primary',
        ]);

        $catalog[] = $this->layout('map', [
            'title' => 'Karte ohne Routen-Link',
            'address' => 'Musterstraße 123, 12345 Berlin, Deutschland',
            'embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2427.924165409515!2d13.404954!3d52.520008!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47a84e373f035901%3A0x42120465b5e3b70!2sBerlin!5e0!3m2!1sde!2sde!4v1234567890',
            'height' => 300,
            'show_directions_link' => false,
            'background_color' => 'secondary',
        ]);

        $logos = [];
        for ($i = 1; $i <= 4; $i++) {
            $logoId = $this->logoId($i) ?? $this->imageId($i);
            if ($logoId) {
                $logos[] = ['logo' => $logoId, 'name' => "Partner {$i}", 'link' => ''];
            }
        }

        $catalog[] = $this->layout('logo_slider', [
            'title' => 'Logos ohne Autoplay',
            'logos' => $logos,
            'autoplay' => false,
            'background_color' => 'primary',
        ]);

        return $catalog;
    }

    /**
     * URL des importierten Demo-Videos, leer wenn keins vorhanden ist.
     */
    private function videoUrl(): string
    {
        $id = $this->imageIds['video_demo'] ?? null;

        return $id ? (string) wp_get_attachment_url( (int) $id) : '';
    }

    /**
     * ID des Hochformat-Platzhalters, faellt auf ein Querformat zurueck.
     */
    private function portraitId(): ?int
    {
        return $this->imageIds['portrait'] ?? $this->imageId(1);
    }

    // =========================================================================
    // ZUSTAENDE
    //
    // Die Instanzen oben zeigen, wie ein Layout aussieht, wenn alles ausgefuellt
    // ist. Kaputt geht es aber woanders: beim fehlenden Bild, beim Text, der
    // dreimal so lang ist wie gedacht, beim Repeater mit genau einem Eintrag.
    // Diese Faelle entstehen auf jeder Kundenseite und wurden nie geprueft.
    //
    // Erkennbar am Anker `<layout>-zustand-<name>`: der Umschalter gruppiert sie
    // dadurch getrennt von den Varianten. Ein eigenes ACF-Feld dafuer waere im
    // Editor jeder Kundenseite sichtbar, ohne dort je einen Zweck zu haben.
    // =========================================================================

    /**
     * Randfaelle je Modul.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getStateCatalog(): array
    {
        $langerText = 'Ein absichtlich sehr langer Text, der prüfen soll, was passiert, wenn '
            . 'jemand die vorgesehene Länge deutlich überschreitet. Er läuft über mehrere '
            . 'Zeilen, sprengt Kartenhöhen, schiebt Schaltflächen nach unten und zeigt, ob '
            . 'die Nachbarn in derselben Zeile mitwachsen oder auseinanderfallen.';

        return [
            $this->layout('one_column', [
                'content' => '<h2>Zustände</h2><p>Ab hier folgen Randfaelle: fehlende Bilder, '
                    . 'überlange Texte, Wiederholungsfelder mit einem einzigen Eintrag. Sie '
                    . 'gehören zur Abnahme, nicht in den Gestaltungsvorschlag.</p>',
                'background_color' => 'secondary',
            ]),

            // Karten ohne Bild und ohne Icon: bleibt die Hoehe stabil?
            $this->layout('cards', [
                'section_headline' => 'Karten ohne Bild',
                'show_section_header' => true,
                'columns' => '3',
                'cards' => [
                    ['title' => 'Ohne Icon', 'content' => 'Diese Karte hat weder Icon noch Bild.', 'icon' => '', 'link' => null],
                    ['title' => 'Ohne Icon', 'content' => 'Auch hier fehlt beides.', 'icon' => '', 'link' => null],
                    ['title' => 'Mit langem Text', 'content' => $langerText, 'icon' => '', 'link' => null],
                ],
                'background_color' => 'primary',
                'section_anchor' => 'cards-zustand-ohne-bild',
            ]),

            // Genau ein Eintrag in einem Raster fuer drei.
            $this->layout('cards', [
                'section_headline' => 'Karten, ein Eintrag',
                'show_section_header' => true,
                'columns' => '3',
                'cards' => [
                    ['title' => 'Allein', 'content' => 'Ein einzelner Eintrag in einem Raster für drei.', 'icon' => 'check', 'link' => null],
                ],
                'background_color' => 'secondary',
                'section_anchor' => 'cards-zustand-ein-eintrag',
            ]),

            // Fuenf Kennzahlen: die letzte Zeile ist unvollstaendig.
            $this->layout('stats', [
                'title' => 'Fünf Kennzahlen',
                'stats' => [
                    ['number' => 12, 'suffix' => '', 'label' => 'Jahre', 'icon' => ''],
                    ['number' => 340, 'suffix' => '+', 'label' => 'Projekte', 'icon' => ''],
                    ['number' => 98, 'suffix' => '%', 'label' => 'Zufriedenheit', 'icon' => ''],
                    ['number' => 25, 'suffix' => '', 'label' => 'Mitarbeitende', 'icon' => ''],
                    ['number' => 7, 'suffix' => '', 'label' => 'Standorte', 'icon' => ''],
                ],
                'background_color' => 'primary',
                'section_anchor' => 'stats-zustand-letzte-zeile',
            ]),

            // Vier Preisplaene: dieselbe Frage bei drei Spalten.
            $this->layout('pricing_table', [
                'title' => 'Vier Preispläne',
                'plans' => [
                    ['name' => 'Basis', 'price' => '19', 'period' => 'Monat', 'features' => "Eine Position\nZweite Position", 'is_featured' => false, 'cta' => ['title' => 'Wählen', 'url' => '#', 'target' => '']],
                    ['name' => 'Plus', 'price' => '39', 'period' => 'Monat', 'features' => "Eine Position\nZweite Position", 'is_featured' => true, 'cta' => ['title' => 'Wählen', 'url' => '#', 'target' => '']],
                    ['name' => 'Pro', 'price' => '79', 'period' => 'Monat', 'features' => "Eine Position\nZweite Position", 'is_featured' => false, 'cta' => ['title' => 'Wählen', 'url' => '#', 'target' => '']],
                    ['name' => 'Maximal', 'price' => '149', 'period' => 'Monat', 'features' => "Eine Position\nZweite Position", 'is_featured' => false, 'cta' => ['title' => 'Wählen', 'url' => '#', 'target' => '']],
                ],
                'background_color' => 'secondary',
                'section_anchor' => 'pricing-table-zustand-letzte-zeile',
            ]),

            // Team ohne Portraits und mit leeren Kontaktfeldern.
            $this->layout('team', [
                'section_headline' => 'Team ohne Porträts',
                'show_section_header' => true,
                'source' => 'manual',
                'columns' => '3',
                'members' => [
                    ['name' => 'Ohne Bild', 'position' => 'Rolle', 'bio' => 'Kein Portrait hinterlegt.', 'image' => null, 'email' => '', 'linkedin' => ''],
                    ['name' => 'Ohne Bild und ohne Rolle', 'position' => '', 'bio' => '', 'image' => null, 'email' => '', 'linkedin' => ''],
                    ['name' => 'Mit langer Biografie', 'position' => 'Rolle', 'bio' => $langerText, 'image' => null, 'email' => '', 'linkedin' => ''],
                ],
                'background_color' => 'primary',
                'section_anchor' => 'team-zustand-ohne-bild',
            ]),

            // Eine einzelne Kundenstimme in voller Breite.
            $this->layout('testimonials', [
                'section_headline' => 'Kundenstimme ohne Foto',
                'show_section_header' => true,
                'source' => 'manual',
                'columns' => '1',
                'testimonials' => [
                    ['quote' => $langerText, 'author' => 'Ohne Foto', 'role' => '', 'image' => null, 'rating' => 0],
                ],
                'background_color' => 'secondary',
                'section_anchor' => 'testimonials-zustand-ohne-foto',
            ]),

            // Akkordeon mit einem einzigen Eintrag.
            $this->layout('accordion', [
                'section_headline' => 'Akkordeon, ein Eintrag',
                'show_section_header' => true,
                'accordion' => [
                    ['title' => 'Einziger Eintrag', 'content' => '<p>Ein Akkordeon mit genau einem Eintrag.</p>'],
                ],
                'background_color' => 'primary',
                'section_anchor' => 'accordion-zustand-ein-eintrag',
            ]),

            // Galerie mit einem Bild in einem Raster fuer drei.
            $this->layout('gallery', [
                'section_headline' => 'Galerie, ein Bild',
                'show_section_header' => true,
                'columns' => '3',
                'images' => array_filter([$this->imageId(1)]),
                'background_color' => 'secondary',
                'section_anchor' => 'gallery-zustand-ein-bild',
            ]),
        ];
    }
}
