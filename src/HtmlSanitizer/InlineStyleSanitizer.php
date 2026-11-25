<?php

namespace App\HtmlSanitizer;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Conserve uniquement les styles inline produits par l'éditeur CKEditor
 * (taille, police, couleur du texte et couleur de fond) sur les balises <span>.
 *
 * Toute autre propriété CSS, ou toute valeur qui ne correspond pas au format attendu
 * (url(), expression(), var(), échappements...), est supprimée.
 */
class InlineStyleSanitizer implements AttributeSanitizerInterface
{
    private const COLOR_PATTERN = '/^(#[0-9a-f]{3,8}|(rgb|rgba|hsl|hsla)\(\s*[\d.%\s,\/]+\)|[a-z]+)$/i';

    private const ALLOWED_PROPERTIES = [
        'font-size' => '/^\d{1,3}(\.\d{1,2})?(px|pt|em|rem|%)$/',
        'font-family' => '/^[a-z0-9 ,\'"\-]+$/i',
        'color' => self::COLOR_PATTERN,
        'background-color' => self::COLOR_PATTERN,
    ];

    public function getSupportedElements(): ?array
    {
        return ['span'];
    }

    public function getSupportedAttributes(): ?array
    {
        return ['style'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        $declarations = [];

        foreach (explode(';', $value) as $declaration) {
            if (!str_contains($declaration, ':')) {
                continue;
            }

            [$property, $propertyValue] = array_map('trim', explode(':', $declaration, 2));
            $property = strtolower($property);

            $pattern = self::ALLOWED_PROPERTIES[$property] ?? null;
            if (null === $pattern || !preg_match($pattern, $propertyValue)) {
                continue;
            }

            $declarations[] = $property . ':' . $propertyValue . ';';
        }

        return [] === $declarations ? null : implode('', $declarations);
    }
}
