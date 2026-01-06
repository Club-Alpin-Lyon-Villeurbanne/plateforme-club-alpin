<?php

namespace App\Tests\Helper;

use App\Helper\HtmlHelper;
use PHPUnit\Framework\TestCase;

class HtmlHelperTest extends TestCase
{
    /**
     * @dataProvider escapeDataProvider
     */
    public function testEscape(?string $input, string $expected): void
    {
        $this->assertEquals($expected, HtmlHelper::escape($input));
    }

    public function escapeDataProvider(): array
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
