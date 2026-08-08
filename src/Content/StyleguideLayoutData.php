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

        $layouts[] = $this->layout('one_column', [
            'label' => '',
            'content' => '<h2>Flexible Content Layouts</h2><p>Alle 28 verfügbaren Layouts für den Seitenaufbau.</p>',
            'background_color' => 'primary',
        ]);

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
            'copy' => 'Wir bieten Ihnen maßgeschneiderte Lösungen für Ihre individuellen Anforderungen. Mit langjähriger Erfahrung und einem engagierten Team stehen wir Ihnen zur Seite.',
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
            'content' => '<h3>Einspaltiger Inhalt</h3><p>Dies ist ein Beispiel für einen einspaltigen Textblock. Hier können Sie längere Texte, Überschriften und andere Inhalte platzieren. Der Text fließt über die gesamte verfügbare Breite.</p><p>Nutzen Sie dieses Layout für Einleitungstexte, ausführliche Beschreibungen oder wichtige Mitteilungen, die die volle Aufmerksamkeit des Lesers erfordern.</p>',
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
    private function getDividerLayoutData(): array
    {
        return $this->layout('divider', ['background_color' => 'brand-subtle']);
    }

    /** @return array<string, mixed> */
    private function getAccordionLayoutData(): array
    {
        return $this->layout('accordion', [
            'accordion' => [
                ['title' => 'Was bieten Sie an?', 'content' => '<p>Wir bieten ein breites Spektrum an Dienstleistungen, von der Beratung über die Umsetzung bis hin zur langfristigen Betreuung. Unser Fokus liegt auf maßgeschneiderten Lösungen für Ihre spezifischen Anforderungen.</p>'],
                ['title' => 'Wie lange dauert ein typisches Projekt?', 'content' => '<p>Die Projektdauer hängt vom Umfang ab. Kleinere Projekte können innerhalb weniger Wochen abgeschlossen werden, während umfangreichere Vorhaben mehrere Monate in Anspruch nehmen können. Wir erstellen immer einen realistischen Zeitplan.</p>'],
                ['title' => 'Wie kann ich Sie kontaktieren?', 'content' => '<p>Sie können uns telefonisch, per E-Mail oder über das Kontaktformular auf unserer Website erreichen. Wir melden uns in der Regel innerhalb von 24 Stunden bei Ihnen.</p>'],
                ['title' => 'Gibt es eine Mindestvertragslaufzeit?', 'content' => '<p>Nein, wir bieten flexible Vertragsmodelle ohne lange Bindungszeiten. Sie können unsere Dienste auch projektbasiert in Anspruch nehmen.</p>'],
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
                ['title' => 'Preise', 'icon' => 'calendar', 'content' => '<h3>Preisgestaltung</h3><p>Unsere Preise richten sich nach dem Umfang Ihrer Anforderungen. Kontaktieren Sie uns für ein individuelles Angebot.</p>'],
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
                ['icon' => 'user', 'title' => 'Beratung', 'content' => 'Professionelle Beratung für Ihre individuellen Anforderungen und Ziele.', 'link' => ['title' => 'Mehr erfahren', 'url' => '#', 'target' => '']],
                ['icon' => 'check', 'title' => 'Umsetzung', 'content' => 'Zuverlässige Umsetzung Ihrer Projekte mit modernsten Technologien.', 'link' => ['title' => 'Details ansehen', 'url' => '#', 'target' => '']],
                ['icon' => 'phone', 'title' => 'Support', 'content' => 'Langfristige Betreuung und schneller Support für Ihren Erfolg.', 'link' => ['title' => 'Kontakt', 'url' => '#', 'target' => '']],
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
                ['image' => $this->imageId(3), 'name' => 'Anna Weber', 'position' => 'Geschäftsführerin', 'bio' => 'Seit 2015 führt Anna das Unternehmen mit Leidenschaft.', 'email' => 'anna@beispiel.de', 'linkedin' => 'https://linkedin.com/in/beispiel'],
                ['image' => $this->imageId(4), 'name' => 'Michael Braun', 'position' => 'Technischer Leiter', 'bio' => 'Michael verantwortet alle technischen Entwicklungen.', 'email' => 'michael@beispiel.de', 'linkedin' => ''],
                ['image' => $this->imageId(5), 'name' => 'Sarah Klein', 'position' => 'Marketing Managerin', 'bio' => 'Sarah sorgt für die Sichtbarkeit unserer Projekte.', 'email' => 'sarah@beispiel.de', 'linkedin' => 'https://linkedin.com/in/beispiel'],
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
            'content' => 'Kontaktieren Sie uns noch heute für ein unverbindliches Beratungsgespräch. Wir freuen uns darauf, gemeinsam mit Ihnen Ihre Ziele zu erreichen.',
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
            'title' => 'Kontaktieren Sie uns',
            'content' => '<p>Haben Sie Fragen oder möchten Sie mehr erfahren? Füllen Sie einfach das Formular aus und wir melden uns schnellstmöglich bei Ihnen.</p>',
            'form_id' => $this->getFirstContactForm7Id(),
            'show_contact_info' => true,
            'background_color' => 'secondary',
        ]);
    }

    /** @return array<string, mixed> */
    private function getMapLayoutData(): array
    {
        return $this->layout('map', [
            'title' => 'So finden Sie uns',
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

        $forms = get_posts([
            'post_type' => 'wpcf7_contact_form',
            'posts_per_page' => 1,
            'orderby' => 'ID',
            'order' => 'ASC',
            'post_status' => 'publish',
        ]);

        return !empty($forms) ? (int) $forms[0]->ID : null;
    }
}
