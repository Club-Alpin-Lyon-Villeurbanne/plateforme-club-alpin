<?php

namespace App\Tests\Legacy;

use App\Legacy\ContentHtml;
use PHPUnit\Framework\TestCase;

class ContentHtmlTest extends TestCase
{
    // ==========================================
    // slugify()
    // ==========================================

    public function testSlugifySimpleText(): void
    {
        $this->assertEquals('bonjour', ContentHtml::slugify('Bonjour'));
    }

    public function testSlugifyRemovesAccents(): void
    {
        $this->assertEquals('pourquoiadherer', ContentHtml::slugify('Pourquoi adhérer ?'));
    }

    public function testSlugifyRemovesEmojis(): void
    {
        $this->assertEquals('pourquoiadherer', ContentHtml::slugify('🎯Pourquoi adhérer ?'));
    }

    public function testSlugifyRemovesSpecialCharacters(): void
    {
        $this->assertEquals('lesactivitesdemontagne', ContentHtml::slugify('╰┈➤ Les activités de montagne'));
    }

    public function testSlugifyHandlesNbsp(): void
    {
        $this->assertEquals('tarifs', ContentHtml::slugify("\u{00A0}Tarifs"));
    }

    public function testSlugifyReturnsEmptyForEmptyString(): void
    {
        $this->assertEquals('', ContentHtml::slugify(''));
    }

    public function testSlugifyReturnsEmptyForOnlySpecialChars(): void
    {
        $this->assertEquals('', ContentHtml::slugify('🎯 ➤ !'));
    }

    public function testSlugifyTruncatesLongText(): void
    {
        $longText = str_repeat('abcdefghij', 20); // 200 chars
        $result = ContentHtml::slugify($longText);
        $this->assertEquals(100, strlen($result));
    }

    /**
     * @dataProvider realPageAnchorsProvider
     */
    public function testSlugifyMatchesExpectedAnchors(string $headingText, string $expectedSlug): void
    {
        $this->assertEquals($expectedSlug, ContentHtml::slugify($headingText));
    }

    public static function realPageAnchorsProvider(): array
    {
        return [
            'Pourquoi adhérer' => ['🎯Pourquoi adhérer ?', 'pourquoiadherer'],
            'Comment adhérer' => ['📝Comment adhérer ?', 'commentadherer'],
            'Tarifs' => ['💰Tarifs', 'tarifs'],
            'Assurance FFCAM' => ["🛡️L'assurance FFCAM", 'lassuranceffcam'],
            'Double adhésion' => ['🔁Déjà membre d\'un autre club FFCAM ?', 'dejamembredunautreclubffcam'],
            'Carte découverte' => ['🎟️Carte découverte', 'cartedecouverte'],
        ];
    }

    // ==========================================
    // addHeadingIds()
    // ==========================================

    public function testAddHeadingIdsToH1(): void
    {
        $html = '<h1>Pourquoi adhérer ?</h1>';
        $expected = '<h1 id="pourquoiadherer">Pourquoi adhérer ?</h1>';
        $this->assertEquals($expected, ContentHtml::addHeadingIds($html));
    }

    public function testAddHeadingIdsToH2(): void
    {
        $html = '<h2>Les activités</h2>';
        $expected = '<h2 id="lesactivites">Les activités</h2>';
        $this->assertEquals($expected, ContentHtml::addHeadingIds($html));
    }

    public function testAddHeadingIdsToH3(): void
    {
        $html = '<h3>Comment faire ?</h3>';
        $expected = '<h3 id="commentfaire">Comment faire ?</h3>';
        $this->assertEquals($expected, ContentHtml::addHeadingIds($html));
    }

    public function testAddHeadingIdsPreservesExistingId(): void
    {
        $html = '<h1 id="custom-id">Mon titre</h1>';
        $this->assertEquals($html, ContentHtml::addHeadingIds($html));
    }

    public function testAddHeadingIdsPreservesExistingIdWithOtherAttrs(): void
    {
        $html = '<h1 class="title" id="custom" style="color:red">Mon titre</h1>';
        $this->assertEquals($html, ContentHtml::addHeadingIds($html));
    }

    public function testAddHeadingIdsDoesNotFalsePositiveOnDataId(): void
    {
        $html = '<h1 data-id="123">Mon titre</h1>';
        $result = ContentHtml::addHeadingIds($html);
        // data-id ne doit PAS empêcher l'ajout d'un vrai id
        $this->assertStringContainsString('id="montitre"', $result);
        $this->assertStringContainsString('data-id="123"', $result);
    }

    public function testAddHeadingIdsDoesNotFalsePositiveOnAriaId(): void
    {
        $html = '<h2 aria-labelledby-id="ref">Titre</h2>';
        $result = ContentHtml::addHeadingIds($html);
        $this->assertStringContainsString('id="titre"', $result);
    }

    public function testAddHeadingIdsWithNestedTags(): void
    {
        $html = '<h2><strong>Nouvelle adhésion</strong> 🆕</h2>';
        $result = ContentHtml::addHeadingIds($html);
        $this->assertStringContainsString('id="nouvelleadhesion"', $result);
        $this->assertStringContainsString('<strong>Nouvelle adhésion</strong>', $result);
    }

    public function testAddHeadingIdsWithEmoji(): void
    {
        $html = '<h1>🎯Pourquoi adhérer ?</h1>';
        $result = ContentHtml::addHeadingIds($html);
        $this->assertStringContainsString('id="pourquoiadherer"', $result);
    }

    public function testAddHeadingIdsWithHtmlEntities(): void
    {
        $html = '<h1>&nbsp;📝Comment adhérer ?</h1>';
        $result = ContentHtml::addHeadingIds($html);
        $this->assertStringContainsString('id="commentadherer"', $result);
    }

    public function testAddHeadingIdsPreservesExistingAttributes(): void
    {
        $html = '<h1 class="page-title" style="color:red">Mon titre</h1>';
        $result = ContentHtml::addHeadingIds($html);
        $this->assertStringContainsString('class="page-title"', $result);
        $this->assertStringContainsString('style="color:red"', $result);
        $this->assertStringContainsString('id="montitre"', $result);
    }

    public function testAddHeadingIdsMultipleHeadings(): void
    {
        $html = '<h1>Premier</h1><p>Du texte</p><h2>Deuxième</h2>';
        $result = ContentHtml::addHeadingIds($html);
        $this->assertStringContainsString('<h1 id="premier">Premier</h1>', $result);
        $this->assertStringContainsString('<h2 id="deuxieme">Deuxième</h2>', $result);
        $this->assertStringContainsString('<p>Du texte</p>', $result);
    }

    public function testAddHeadingIdsNoHeadings(): void
    {
        $html = '<p>Pas de titre ici</p><div>Contenu</div>';
        $this->assertEquals($html, ContentHtml::addHeadingIds($html));
    }

    public function testAddHeadingIdsEmptyHeading(): void
    {
        $html = '<h1>&nbsp;</h1>';
        // Le heading vide ne devrait pas recevoir d'id vide
        $this->assertEquals($html, ContentHtml::addHeadingIds($html));
    }

    public function testAddHeadingIdsSkipsEmptySlug(): void
    {
        $html = '<h2><strong>&nbsp; &nbsp; &nbsp;</strong></h2>';
        // Que des espaces/nbsp → slug vide → pas d'id
        $this->assertEquals($html, ContentHtml::addHeadingIds($html));
    }

    public function testAddHeadingIdsDuplicateSlugsGetSuffix(): void
    {
        $html = '<h1>Section</h1><p>texte</p><h1>Section</h1><p>texte</p><h1>Section</h1>';
        $result = ContentHtml::addHeadingIds($html);
        $this->assertStringContainsString('<h1 id="section">Section</h1>', $result);
        $this->assertStringContainsString('<h1 id="section2">Section</h1>', $result);
        $this->assertStringContainsString('<h1 id="section3">Section</h1>', $result);
    }

    public function testAddHeadingIdsDuplicatesWithDifferentLevels(): void
    {
        $html = '<h1>Contact</h1><h2>Contact</h2>';
        $result = ContentHtml::addHeadingIds($html);
        $this->assertStringContainsString('<h1 id="contact">', $result);
        $this->assertStringContainsString('<h2 id="contact2">', $result);
    }

    public function testAddHeadingIdsRealWorldContent(): void
    {
        $html = <<<'HTML'
<ul><li><a href="/pages/adhesion.html#pourquoiadherer">Pourquoi adhérer ?</a></li></ul>
<h1>🎯Pourquoi adhérer ?</h1>
<p>Du contenu ici...</p>
<h1>&nbsp;📝Comment adhérer ?</h1>
<p>Plus de contenu...</p>
<h1>&nbsp;💰Tarifs</h1>
HTML;

        $result = ContentHtml::addHeadingIds($html);

        // Les h1 ont reçu des ids
        $this->assertStringContainsString('<h1 id="pourquoiadherer">', $result);
        $this->assertStringContainsString('<h1 id="commentadherer">', $result);
        $this->assertStringContainsString('<h1 id="tarifs">', $result);

        // Le reste du contenu est intact
        $this->assertStringContainsString('href="/pages/adhesion.html#pourquoiadherer"', $result);
        $this->assertStringContainsString('<p>Du contenu ici...</p>', $result);
    }
}
