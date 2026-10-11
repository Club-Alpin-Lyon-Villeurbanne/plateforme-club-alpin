<?php

namespace App\Serializer;

use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Repository\ExpenseReportStatusHistoryRepository;
use App\Security\ExpenseReportActor;
use App\Service\ExpenseReportCalculator;
use App\Service\NoteDeFrais\ControlesNoteHorsSortie;
use App\Service\NoteDeFrais\ValideursNoteHorsSortie;
use App\Utils\Enums\ExpenseReportStatusEnum;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Ajoute à une note hors sortie, en lecture seule, ce que le serveur calcule : montants,
 * avertissements, valideurs, date d'envoi, dernière décision, et si l'utilisateur connecté peut
 * la valider ou la renvoyer. Une note de sortie garde exactement sa forme.
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
        private readonly ExpenseReportActor $actor,
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
                'derniereDecision' => $this->derniereDecision($object),
                'peutDecider' => ExpenseReportStatusEnum::SUBMITTED === $object->getStatus() && $this->actor->isManagerOf($object),
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

    /**
     * Le message est celui du renvoi : après correction et validation, le commentaire de statut
     * garde encore l'ancien message de renvoi.
     */
    private function derniereDecision(ExpenseReport $note): ?array
    {
        if (ExpenseReportStatusEnum::DRAFT === $note->getStatus()) {
            return null;
        }

        $decision = $this->historique->derniereDecision($note);
        if (null === $decision) {
            return null;
        }

        return [
            'statut' => $decision->getNewStatus()->value,
            'auteur' => ['prenom' => $decision->getChangedBy()?->getFirstname(), 'nom' => $decision->getChangedBy()?->getLastname()],
            'date' => $decision->getChangedAt()?->format(\DATE_ATOM),
            'message' => ExpenseReportStatusEnum::REJECTED === $decision->getNewStatus() ? $note->getStatusComment() : null,
        ];
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
