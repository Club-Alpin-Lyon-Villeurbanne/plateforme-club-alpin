<?php

namespace App\Validator\ExpenseReport;

use App\Entity\ExpenseReport;
use App\Utils\Enums\ExpenseReportTypeEnum;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Structure d'une note selon son type : une note de sortie a sa sortie et rien d'autre ; une
 * note hors sortie (formation, achat) a une commission visible, un objet, une date et une liste
 * de lignes.
 */
class CoherenceValidator
{
    public function __construct(
        private readonly ExpenseReportOriginalState $originalState,
    ) {
    }

    public function validate(ExpenseReport $note, ExecutionContextInterface $context): void
    {
        if (!$note->getType()->estHorsSortie()) {
            $this->validerNoteDeSortie($note, $context);

            return;
        }

        if (null !== $note->getEvent()) {
            $this->refuser($context, 'event', 'Une note hors sortie n\'est pas rattachée à une sortie.');
        }
        $commission = $note->getCommission();
        if (null === $commission) {
            $this->refuser($context, 'commission', 'La commission est obligatoire.');
        } elseif (!$commission->getVis() && $commission !== $this->originalState->value($note, 'commission')) {
            // Vérifié quand la commission est choisie : la masquer ensuite ne bloque pas ses notes en cours.
            $this->refuser($context, 'commission', 'Cette commission ne peut pas recevoir de note de frais.');
        }
        if ('' === trim((string) $note->getObjet())) {
            $this->refuser($context, 'objet', 'L\'objet est obligatoire.');
        }
        if (null === $note->getDateDepense()) {
            $this->refuser($context, 'dateDepense', 'La date est obligatoire.');
        }
        if (null !== $note->getLienFormation() && ExpenseReportTypeEnum::FORMATION !== $note->getType()) {
            $this->refuser($context, 'lienFormation', 'Un lien vers une formation ne concerne que les formations.');
        }

        $this->validerLignes($note, $context);
    }

    private function validerNoteDeSortie(ExpenseReport $note, ExecutionContextInterface $context): void
    {
        if (null === $note->getEvent()) {
            $this->refuser($context, 'event', 'Une note de sortie doit être rattachée à sa sortie.');
        }

        $champsHorsSortie = [
            'commission' => $note->getCommission(),
            'objet' => $note->getObjet(),
            'dateDepense' => $note->getDateDepense(),
            'lienFormation' => $note->getLienFormation(),
            'motPourResponsable' => $note->getMotPourResponsable(),
        ];
        foreach ($champsHorsSortie as $champ => $valeur) {
            if (null !== $valeur) {
                $this->refuser($context, $champ, 'Ce champ ne concerne que les notes hors sortie.');
            }
        }
    }

    /**
     * Forme des lignes seulement : les montants strictement positifs et les justificatifs
     * sont exigés à la soumission.
     */
    private function validerLignes(ExpenseReport $note, ExecutionContextInterface $context): void
    {
        if (null === $note->getDetails()) {
            return;
        }

        $details = json_decode($note->getDetails(), true);
        if (!\is_array($details) || !\is_array($details['lignes'] ?? null) || !array_is_list($details['lignes'])) {
            $this->refuser($context, 'details', 'Les dépenses doivent être une liste de lignes.');

            return;
        }

        $naturesPermises = $note->getType()->naturesPermises();
        foreach ($details['lignes'] as $index => $ligne) {
            $numero = $index + 1;

            if (!\is_array($ligne) || !\is_string($ligne['id'] ?? null) || '' === $ligne['id']) {
                $this->refuser($context, 'details', "Ligne {$numero} : l'identifiant de la ligne manque.");

                continue;
            }

            $montant = $ligne['montant'] ?? null;
            if (null !== $montant && (!(\is_int($montant) || \is_float($montant)) || $montant < 0)) {
                $this->refuser($context, 'details', "Ligne {$numero} : le montant doit être un nombre positif.");
            }
            if (isset($ligne['description']) && !\is_string($ligne['description'])) {
                $this->refuser($context, 'details', "Ligne {$numero} : la description doit être un texte.");
            }
            if (isset($ligne['nature']) && !\in_array($ligne['nature'], $naturesPermises, true)) {
                $this->refuser($context, 'details', "Ligne {$numero} : cette nature de dépense n'est pas permise pour ce type de note.");
            }
        }
    }

    private function refuser(ExecutionContextInterface $context, string $champ, string $message): void
    {
        $context->buildViolation($message)->atPath($champ)->addViolation();
    }
}
