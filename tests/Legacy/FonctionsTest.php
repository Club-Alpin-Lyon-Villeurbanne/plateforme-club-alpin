<?php

namespace App\Tests\Legacy;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FonctionsTest extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        // Charger fonctions.php (nécessite le kernel pour LegacyContainer)
        require_once __DIR__ . '/../../legacy/app/fonctions.php';
    }

    // ==========================================
    // html_utf8() - 126 usages
    // ==========================================

    public function testHtmlUtf8EscapesHtml(): void
    {
        $this->assertEquals('&lt;script&gt;', html_utf8('<script>'));
    }

    public function testHtmlUtf8EscapesQuotes(): void
    {
        $this->assertEquals('&quot;test&quot;', html_utf8('"test"'));
    }

    public function testHtmlUtf8HandlesNull(): void
    {
        $this->assertEquals('', html_utf8(null));
    }

    public function testHtmlUtf8PreservesUtf8(): void
    {
        $this->assertEquals('Caf&eacute;', html_utf8('Café'));
    }

    // ==========================================
    // formater() - 13 usages
    // ==========================================

    public function testFormaterType1LowercaseWithSpaces(): void
    {
        // Note: Type 1 garde les espaces (contrairement au commentaire dans le code)
        $this->assertEquals('mon titre', formater('Mon Titre', 1));
    }

    public function testFormaterType2CamelCase(): void
    {
        $this->assertEquals('MonTitre', formater('mon titre', 2));
    }

    public function testFormaterType3Slug(): void
    {
        $this->assertEquals('mon-titre', formater('Mon Titre', 3));
    }

    public function testFormaterType3RemovesAccents(): void
    {
        $this->assertEquals('cafe-creme', formater('Café Crème', 3));
    }

    public function testFormaterType4Filename(): void
    {
        $this->assertEquals('mon-fichier.pdf', formater('Mon Fichier.pdf', 4));
    }

    // ==========================================
    // mois() et jour() - formatage dates
    // ==========================================

    public function testMoisReturnsJanvier(): void
    {
        $this->assertEquals('Janvier', mois(1));
    }

    public function testMoisReturnsDecembre(): void
    {
        $this->assertEquals('Décembre', mois(12));
    }

    public function testMoisReturnsEmptyForInvalid(): void
    {
        $this->assertEquals('', mois(13));
        $this->assertEquals('', mois(0));
    }

    public function testJourReturnsLundi(): void
    {
        $this->assertEquals('Lundi', jour(1));
    }

    public function testJourReturnsDimanche(): void
    {
        $this->assertEquals('Dimanche', jour(7));
    }

    public function testJourShortMode(): void
    {
        $this->assertEquals('Lun', jour(1, 'short'));
    }

    // ==========================================
    // limiterTexte() - troncature
    // ==========================================

    public function testLimiterTexteShortText(): void
    {
        $this->assertEquals('Hello', limiterTexte('Hello', 100));
    }

    public function testLimiterTexteTruncatesAtSpace(): void
    {
        $text = 'Ceci est un texte assez long pour être tronqué';
        $result = limiterTexte($text, 20);
        $this->assertLessThanOrEqual(30, strlen($result));
    }

    public function testLimiterTexteStripsHtml(): void
    {
        $text = '<p>Hello <strong>World</strong></p>';
        $this->assertEquals('Hello World', limiterTexte($text, 100));
    }

    // ==========================================
    // wd_remove_accents() - accents
    // ==========================================

    public function testWdRemoveAccentsBasic(): void
    {
        $this->assertEquals('cafe', wd_remove_accents('café'));
    }

    public function testWdRemoveAccentsNoel(): void
    {
        $this->assertEquals('noel', wd_remove_accents('noël'));
    }

    // ==========================================
    // formatSize() - tailles fichiers
    // ==========================================

    public function testFormatSizeBytes(): void
    {
        $this->assertEquals('500.00 o', formatSize(500));
    }

    public function testFormatSizeKilobytes(): void
    {
        $this->assertEquals('1.00 Ko', formatSize(1024));
    }

    public function testFormatSizeMegabytes(): void
    {
        $this->assertEquals('1.00 Mo', formatSize(1024 * 1024));
    }

    // ==========================================
    // isMail() - validation email
    // ==========================================

    public function testIsMailValid(): void
    {
        $this->assertTrue(isMail('test@example.com'));
    }

    public function testIsMailInvalid(): void
    {
        $this->assertFalse(isMail('not-an-email'));
    }

    public function testIsMailNull(): void
    {
        $this->assertFalse(isMail(null));
    }

    // ==========================================
    // getYearsSinceDate() - calcul âge
    // ==========================================

    public function testGetYearsSinceDate(): void
    {
        $tenYearsAgo = strtotime('-10 years');
        $this->assertEquals(10, getYearsSinceDate($tenYearsAgo));
    }

    public function testGetYearsSinceDateNull(): void
    {
        $this->assertEquals('inconnu', getYearsSinceDate(null));
    }
}
