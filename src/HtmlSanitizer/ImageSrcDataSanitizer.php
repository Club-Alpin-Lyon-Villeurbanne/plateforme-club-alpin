<?php

namespace App\HtmlSanitizer;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

class ImageSrcDataSanitizer implements AttributeSanitizerInterface
{
    public function getSupportedElements(): ?array
    {
        return ['img'];
    }

    public function getSupportedAttributes(): ?array
    {
        return ['src'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        if ('img' !== $element || 'src' !== $attribute) {
            return $value;
        }

        if (str_starts_with($value, 'data:image/') || str_starts_with($value, 'https://') || (!str_contains($value, '://') && !str_starts_with($value, 'data:'))) {
            return $value;
        }

        // Reject everything else
        return '';
    }
}
