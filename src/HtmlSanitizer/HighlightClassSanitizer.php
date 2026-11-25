<?php

namespace App\HtmlSanitizer;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Conserve uniquement les classes de surlignage produites par l'éditeur CKEditor
 * (marker-yellow, pen-red...) sur les balises <mark>.
 */
class HighlightClassSanitizer implements AttributeSanitizerInterface
{
    private const HIGHLIGHT_CLASS_PATTERN = '/^(marker|pen)-[a-z]+$/';

    public function getSupportedElements(): ?array
    {
        return ['mark'];
    }

    public function getSupportedAttributes(): ?array
    {
        return ['class'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        $classes = array_filter(
            preg_split('/\s+/', trim($value)) ?: [],
            static fn (string $class): bool => 1 === preg_match(self::HIGHLIGHT_CLASS_PATTERN, $class),
        );

        return [] === $classes ? null : implode(' ', $classes);
    }
}
