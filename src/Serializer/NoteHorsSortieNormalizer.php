<?php

namespace App\Serializer;

use App\Entity\ExpenseReport;
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
 * Ajoute à une note hors sortie, en lecture seule, ce que le serveur calcule : montants, lignes
 * telles qu'affichées, avertissements, valideurs, date d'envoi, dernière décision, et si
 * l'utilisateur connecté peut la valider ou la renvoyer. Retire le choix entre don et
 * remboursement pour qui ne doit pas le connaître. Une note de sortie garde exactement sa forme.
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

        if (!$this->actor->voitLeChoixDuRemboursement($object)) {
            unset($donnees['refundRequired']);
        }

        return array_merge(
            $donnees,
            $this->calculator->montantsNoteHorsSortie($object),
            [
                'lignesAffichees' => $this->calculator->lignesAffichees($object),
                'avertissements' => $this->controles->avertissements($object),
                'dateEnvoi' => $this->dateEnvoi($object)?->format(\DATE_ATOM),
                'valideurs' => ValideursNoteHorsSortie::exposer($this->valideurs->pour($object)),
                'derniereDecision' => $this->derniereDecision($object),
                'peutDecider' => ExpenseReportStatusEnum::SUBMITTED === $object->getStatus()
                    && ($this->actor->isManagerOf($object) || $this->actor->isResponsableOf($object)),
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
            'message' => $decision->getMessage(),
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
