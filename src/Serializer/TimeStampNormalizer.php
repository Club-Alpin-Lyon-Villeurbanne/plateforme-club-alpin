<?php

namespace App\Serializer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class TimeStampNormalizer implements NormalizerInterface
{

    public const FORMAT_KEY = 'datetime_format';

    public function normalize($data, ?string $format = null, array $context = []): string
    { 
        return  date($context[self::FORMAT_KEY], $data);
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return is_numeric($data) && isset($context[self::FORMAT_KEY]) && is_string($context[self::FORMAT_KEY]);
    }
}
