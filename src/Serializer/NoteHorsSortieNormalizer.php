<?php

namespace App\Serializer;

use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Repository\ExpenseReportStatusHistoryRepository;
use App\Service\ExpenseReportCalculator;
use App\Service\NoteDeFrais\ControlesNoteHorsSortie;
use App\Service\NoteDeFrais\ValideursNoteHorsSortie;
use App\Utils\Enums\ExpenseReportStatusEnum;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Ajoute à une note hors sortie, en lecture seule, ses montants et ses avertissements calculés
 * par le serveur. Une note de sortie garde exactement sa forme.
 */
final class NoteHorsSortieNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const DEJA_APPELE = 'NOTE_HORS_SORTIE_NORMALIZER_DEJA_APPELE';

    public function __construct(
        private readonly ExpenseReportCalculator $calculator,
        private readonly ControlesNoteHorsSortie $controles,
        private readonly ValideursNoteHorsSortie $valideurs,
        private readonly ExpenseReportStatusHistoryRepository $historique,
    ) {
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $context[self::DEJA_APPELE] = true;
        $donnees = $this->normalizer->normalize($object, $format, $context);

        if (!\is_array($donnees)) {
            return $donnees;
        }

        return array_merge(
            $donnees,
            $this->calculator->montantsNoteHorsSortie($object),
            [
                'avertissements' => $this->controles->avertissements($object),
                'dateEnvoi' => $this->dateEnvoi($object)?->format(\DATE_ATOM),
                'valideurs' => array_map(
                    static fn (User $valideur) => ['id' => $valideur->getId(), 'prenom' => $valideur->getFirstname(), 'nom' => $valideur->getLastname()],
                    $this->valideurs->pour($object),
                ),
            ],
        );
    }

    /**
     * Une requête par note envoyée : la liste d'un demandeur reste de quelques dizaines de lignes.
     */
    private function dateEnvoi(ExpenseReport $note): ?\DateTimeImmutable
    {
        if (ExpenseReportStatusEnum::DRAFT === $note->getStatus()) {
            return null;
        }

        return $this->historique->dateDernierEnvoi($note);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return !isset($context[self::DEJA_APPELE])
            && $data instanceof ExpenseReport
            && $data->getType()->estHorsSortie();
    }

    public function getSupportedTypes(?string $format): array
    {
        return [ExpenseReport::class => false];
    }
}
