<?php

namespace App\Tests\Legacy;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FonctionsTest extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        require_once __DIR__ . '/../../legacy/app/fonctions.php';
    }

    // ==========================================
    // formater() - 10 usages
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

    public function testFormaterHandlesNull(): void
    {
        $this->assertEquals('', formater(null, 1));
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

    public function testWdRemoveAccentsHandlesNull(): void
    {
        $this->assertEquals('', wd_remove_accents(null));
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

    public function testFormatSizeZero(): void
    {
        $this->assertEquals('0.00 o', formatSize(0));
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
    // getArrayFirstValue()
    // ==========================================

    public function testGetArrayFirstValueReturnsFirst(): void
    {
        $this->assertEquals('a', getArrayFirstValue(['a', 'b', 'c']));
    }

    public function testGetArrayFirstValueReturnsNullForEmpty(): void
    {
        $this->assertNull(getArrayFirstValue([]));
    }

    // ==========================================
    // clearDir() - suppression récursive
    // ==========================================

    public function testClearDirNonExistentReturnsNull(): void
    {
        $this->assertNull(clearDir('/non/existent/path'));
    }
}
