<?php

namespace App\Tests\Helper;

use App\Helper\HtmlHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HtmlHelperTest extends TestCase
{
    #[DataProvider('descriptionsDuJournal')]
    public function testEscapeAdminLogDescription(string $description, string $attendu): void
    {
        $this->assertSame($attendu, html_entity_decode(HtmlHelper::escapeAdminLogDescription($description), \ENT_QUOTES, 'UTF-8'));
    }

    public static function descriptionsDuJournal(): array
    {
        return [
            'ancien format nettoyé' => ['Modif élément : <i>accueil</i>', 'Modif élément : accueil'],
            'nouveau format inchangé' => ['Modif élément : « accueil »', 'Modif élément : « accueil »'],
            'balise i dans un nouveau code conservée' => ['Modif élément : « <i>bloc</i> »', 'Modif élément : « <i>bloc</i> »'],
            'autre balise conservée' => ['Modif élément : « bloc<b> »', 'Modif élément : « bloc<b> »'],
            'autre description inchangée' => ['Création de la page <i>test</i>', 'Création de la page <i>test</i>'],
        ];
    }

    public function testEscapeAdminLogDescriptionEchappeLeCodeDeLAncienFormat(): void
    {
        $this->assertSame(
            'Modif &eacute;l&eacute;ment : &lt;script&gt;',
            HtmlHelper::escapeAdminLogDescription('Modif élément : <i><script></i>')
        );
    }

    #[DataProvider('escapeDataProvider')]
    public function testEscape(?string $input, string $expected): void
    {
        $this->assertEquals($expected, HtmlHelper::escape($input));
    }

    public static function escapeDataProvider(): array
    {
        return [
            'escapes HTML tags' => [
                '<script>alert("xss")</script>',
                '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;',
            ],
            'escapes double quotes' => [
                '"test"',
                '&quot;test&quot;',
            ],
            'escapes single quotes' => [
                "it's a test",
                'it&#039;s a test',
            ],
            'escapes ampersand' => [
                'foo & bar',
                'foo &amp; bar',
            ],
            'escapes less than and greater than' => [
                '1 < 2 > 0',
                '1 &lt; 2 &gt; 0',
            ],
            'handles null value' => [
                null,
                '',
            ],
            'handles empty string' => [
                '',
                '',
            ],
            'preserves regular text' => [
                'Hello World',
                'Hello World',
            ],
            'handles UTF-8 characters' => [
                'Café crème',
                'Caf&eacute; cr&egrave;me',
            ],
            'handles complex UTF-8' => [
                'Événement à Noël',
                '&Eacute;v&eacute;nement &agrave; No&euml;l',
            ],
            'handles mixed content' => [
                '<div class="test">Café & Thé</div>',
                '&lt;div class=&quot;test&quot;&gt;Caf&eacute; &amp; Th&eacute;&lt;/div&gt;',
            ],
        ];
    }

    public function testEscapePreventXss(): void
    {
        $maliciousInputs = [
            '<script>document.cookie</script>',
            '<img src="x" onerror="alert(1)">',
            '<a href="javascript:alert(1)">click</a>',
            '"><script>alert(1)</script>',
            "'; DROP TABLE users; --",
        ];

        foreach ($maliciousInputs as $input) {
            $escaped = HtmlHelper::escape($input);
            $this->assertStringNotContainsString('<script>', $escaped);
            $this->assertStringNotContainsString('<img', $escaped);
            $this->assertStringNotContainsString('<a ', $escaped);
        }
    }
}
