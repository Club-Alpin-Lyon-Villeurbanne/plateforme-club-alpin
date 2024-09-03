<?php

namespace App\Serializer;

use App\Entity\Evt;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class EventNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly NormalizerInterface $normalizer
    ) {
    }

    public function normalize($object, ?string $format = null, array $context = []): array
    {
        $data = $this->normalizer->normalize($object, $format, $context);

        // Conversion des timestamps en dates
        $timestampFields = ['tsp', 'tspEnd'];
        foreach ($timestampFields as $field) {
            if (isset($data[$field]) && is_numeric($data[$field])) {
                $data[$field] = date('Y-m-d H:i:s', $data[$field]);
            }
        }

        return $data;
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Evt;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Evt::class => true,
        ];
    }
}
