<?php

namespace App\HtmlSanitizer;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Sanitizes iframe src attributes to only allow specific video hosting domains.
 * Images are not affected by this sanitizer (they allow all HTTPS domains).
 */
class IframeSrcSanitizer implements AttributeSanitizerInterface
{
    private const ALLOWED_IFRAME_HOSTS = [
        'youtube.com',
        'www.youtube.com',
        'youtube-nocookie.com',
        'www.youtube-nocookie.com',
        'vimeo.com',
        'player.vimeo.com',
        'dailymotion.com',
        'www.dailymotion.com',
        'loom.com',
        'www.loom.com',
        'luna.loom.com',
        'cdn.loom.com',
    ];

    public function getSupportedElements(): ?array
    {
        return ['iframe'];
    }

    public function getSupportedAttributes(): ?array
    {
        return ['src'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        if ('iframe' !== $element || 'src' !== $attribute) {
            return $value;
        }

        $parsedUrl = parse_url($value);

        if (false === $parsedUrl || !isset($parsedUrl['host'])) {
            // Return empty string to clear the src attribute (null causes issues with chained sanitizers)
            return '';
        }

        // Only allow HTTPS
        if (!isset($parsedUrl['scheme']) || 'https' !== $parsedUrl['scheme']) {
            return '';
        }

        $host = strtolower($parsedUrl['host']);

        if (in_array($host, self::ALLOWED_IFRAME_HOSTS, true)) {
            return $value;
        }

        // Unauthorized domain: clear the src
        return '';
    }
}
