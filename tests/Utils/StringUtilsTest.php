<?php

namespace App\Tests\Utils;

use App\Utils\StringUtils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StringUtilsTest extends TestCase
{
    #[DataProvider('nomsEquivalents')]
    public function testNormalizeNameIgnoresSpacesHyphensAccentsAndCase(string $site, string $ffcam): void
    {
        $this->assertSame(StringUtils::normalizeName($site), StringUtils::normalizeName($ffcam));
    }

    public static function nomsEquivalents(): array
    {
        return [
            'tiret dans le prénom' => ['Jean-Pierre', 'Jean Pierre'],
            'espace dans le nom' => ['DU PONT', 'DUPONT'],
            'accents et majuscules' => ['Hélène', 'HELENE'],
            'apostrophe' => ["D'ARTAGNAN", 'Dartagnan'],
        ];
    }

    public function testNormalizeNameKeepsDifferentNamesApart(): void
    {
        $this->assertNotSame(StringUtils::normalizeName('Marie'), StringUtils::normalizeName('Paul'));
    }

    public function testNormalizeNameOfEmptyNameIsEmpty(): void
    {
        $this->assertSame('', StringUtils::normalizeName(null));
        $this->assertSame('', StringUtils::normalizeName(' - '));
    }
}
