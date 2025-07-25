<?php

namespace App\Serializer;

use App\Entity\Article;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class TimeStampNormalizer implements NormalizerInterface
{
    public const FORMAT_KEY = 'datetime_format';

    public function normalize($data, ?string $format = null, array $context = []): string
    {
        return date($context[self::FORMAT_KEY], $data);
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return is_int($data) && isset($context[self::FORMAT_KEY]) && \is_string($context[self::FORMAT_KEY]);
    }

    public function getSupportedTypes(?string $format): array
    {
        // TODO: Don't how to return the correct type here, as integer this is not a class. ['integer' => true] is not working
        return [
            '*' => true,
        ];
    }
}
