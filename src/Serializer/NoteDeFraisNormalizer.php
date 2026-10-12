<?php

namespace App\Serializer;

use App\Entity\ExpenseReport;
use App\Entity\ExpenseReportStatusHistory;
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
 * Ajoute à une note, en lecture seule, ce que seul le serveur sait calculer (montants, et pour une
 * note hors sortie son circuit et ses contrôles), et retire le choix entre don et remboursement
 * pour qui ne doit pas le connaître. Liste des champs : docs/api.md.
 */
final class NoteDeFraisNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const DEJA_APPELE = 'NOTE_DE_FRAIS_NORMALIZER_DEJA_APPELE';

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

        $donnees = array_merge($donnees, $this->calculator->montants($object));
        if (!$object->getType()->estHorsSortie()) {
            return $donnees;
        }

        if (!$this->actor->voitLeChoixDuRemboursement($object)) {
            unset($donnees['refundRequired']);
        }

        return array_merge($donnees, [
            'lignesAffichees' => $this->calculator->lignesAffichees($object),
            'avertissements' => $this->controles->avertissements($object),
            'dateEnvoi' => $this->dateEnvoi($object)?->format(\DATE_ATOM),
            'valideurs' => ValideursNoteHorsSortie::exposer($this->valideurs->pour($object)),
            'derniereDecision' => $this->derniereDecision($object),
            'validationResponsable' => $this->validationResponsable($object),
            'libelleComptable' => ExpenseReportStatusEnum::DRAFT === $object->getStatus() ? null : $object->libelleComptable(),
            // La trésorerie décide dans compta-club : le site ne lui propose rien.
            'peutDecider' => ExpenseReportStatusEnum::SUBMITTED === $object->getStatus() && $this->actor->isResponsableOf($object),
        ]);
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
            'niveau' => $decision->getNiveau()?->value,
            'auteur' => $this->auteur($decision),
            'date' => $decision->getChangedAt()?->format(\DATE_ATOM),
            'message' => $decision->getMessage(),
        ];
    }

    private function validationResponsable(ExpenseReport $note): ?array
    {
        if (ExpenseReportStatusEnum::DRAFT === $note->getStatus()) {
            return null;
        }

        $validation = $this->historique->preValidationDepuisLeDernierEnvoi($note);
        if (null === $validation) {
            return null;
        }

        return ['auteur' => $this->auteur($validation), 'date' => $validation->getChangedAt()?->format(\DATE_ATOM)];
    }

    /**
     * @return array{prenom: ?string, nom: ?string}
     */
    private function auteur(ExpenseReportStatusHistory $ligne): array
    {
        return ['prenom' => $ligne->getChangedBy()?->getFirstname(), 'nom' => $ligne->getChangedBy()?->getLastname()];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return !isset($context[self::DEJA_APPELE]) && $data instanceof ExpenseReport;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [ExpenseReport::class => false];
    }
}
