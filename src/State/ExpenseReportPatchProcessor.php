<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\ExpenseReport;
use App\Service\ExpenseReportCalculator;
use App\Service\NoteDeFrais\NotificationsNoteHorsSortie;
use App\Utils\Enums\ExpenseReportStatusEnum;
use App\Validator\ExpenseReport\ExpenseReportOriginalState;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Modification d'une note de frais. L'envoi d'une note hors sortie (brouillon ou note à
 * corriger qui passe à « envoyée ») fige ses montants dans la même écriture que le changement
 * de statut, puis prévient la trésorerie.
 */
class ExpenseReportPatchProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        private readonly ExpenseReportOriginalState $originalState,
        private readonly ExpenseReportCalculator $calculator,
        private readonly NotificationsNoteHorsSortie $notifications,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $envoi = $data instanceof ExpenseReport && $this->estUnEnvoiHorsSortie($data);

        if ($envoi) {
            $montants = $this->calculator->calculerNoteHorsSortie($data);
            $data->figerMontants($montants['totalDeclare'], $montants['totalRemboursable']);
        }

        $resultat = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        if ($envoi) {
            $this->notifications->noteEnvoyee($data);
        }

        return $resultat;
    }

    private function estUnEnvoiHorsSortie(ExpenseReport $note): bool
    {
        return $note->getType()->estHorsSortie()
            && ExpenseReportStatusEnum::SUBMITTED === $note->getStatus()
            && \in_array($this->originalState->status($note), [ExpenseReportStatusEnum::DRAFT, ExpenseReportStatusEnum::REJECTED], true);
    }
}
