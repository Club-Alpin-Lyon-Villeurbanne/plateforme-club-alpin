<?php

namespace App\Serializer;

use ApiPlatform\State\Pagination\PaginatorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class PaginationMetadataNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'PAGINATION_METADATA_NORMALIZER_ALREADY_CALLED';

    public function normalize($object, ?string $format = null, array $context = []): array
    {
        $context[self::ALREADY_CALLED] = true;

        $data = [];
        foreach ($object as $item) {
            $data[] = $this->normalizer->normalize($item, $format, $context);
        }

        return [
            'data' => $data,
            'meta' => [
                'page' => $object->getCurrentPage(),
                'perPage' => $object->getItemsPerPage(),
                'total' => $object->getTotalItems(),
                'pages' => (int) ceil($object->getTotalItems() / $object->getItemsPerPage()),
            ],
        ];
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return !isset($context[self::ALREADY_CALLED])
            && $data instanceof PaginatorInterface
            && 'json' === $format;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            PaginatorInterface::class => true,
        ];
    }
}
