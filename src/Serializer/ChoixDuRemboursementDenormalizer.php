<?php

namespace App\Serializer;

use App\Entity\ExpenseReport;
use App\Security\ExpenseReportActor;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Ignore le choix entre don et remboursement envoyé par qui ne doit pas le connaître (le
 * responsable de la note) : refuser un choix différent de celui enregistré, et accepter le même,
 * le lui dévoilerait.
 */
final class ChoixDuRemboursementDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    private const DEJA_APPELE = 'CHOIX_DU_REMBOURSEMENT_DENORMALIZER_DEJA_APPELE';

    public function __construct(
        private readonly ExpenseReportActor $actor,
    ) {
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $context[self::DEJA_APPELE] = true;
        unset($data['refundRequired']);

        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        $note = $context[AbstractNormalizer::OBJECT_TO_POPULATE] ?? null;

        return !isset($context[self::DEJA_APPELE])
            && \is_array($data)
            && \array_key_exists('refundRequired', $data)
            && $note instanceof ExpenseReport
            && $note->getType()->estHorsSortie()
            && !$this->actor->voitLeChoixDuRemboursement($note);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [ExpenseReport::class => false];
    }
}
