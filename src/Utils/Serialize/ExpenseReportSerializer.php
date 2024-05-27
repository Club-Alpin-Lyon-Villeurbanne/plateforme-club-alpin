<?php

namespace App\Utils\Serialize;

use App\Entity\ExpenseField;
use App\Entity\ExpenseReport;
use App\Repository\EvtRepository;
use App\Repository\ExpenseFieldRepository;
use App\Repository\ExpenseGroupRepository;
use App\Repository\ExpenseReportRepository;
use App\Repository\ExpenseRepository;
use App\Repository\UserRepository;

class ExpenseReportSerializer
{
    public function __construct(
        private ExpenseReportRepository $expenseReportRepository,
        private ExpenseGroupRepository $expenseGroupRepository,
        private ExpenseFieldRepository $expenseFieldRepository,
        private ExpenseRepository $expenseRepository,
        private UserRepository $userRepository,
        private EvtRepository $evtRepository,
    ) {
    }

    public function serialize(ExpenseReport $expenseReport): array
    {
        // récupérer les groupes
        $expenseGroups = $this->expenseGroupRepository->findAll();
        $expenseGroupsArray = [];
        // pour chaque dépense associées aux groupes, générer les champs
        foreach ($expenseGroups as $expenseGroup) {
            foreach ($expenseGroup->getExpenseTypes() as $expenseType) {
                $expenses = $this->expenseRepository->findBy([
                    'expenseReport' => $expenseReport,
                    'expenseType' => $expenseType
                ]);

                foreach ($expenses as $expense) {
                    $fields = $this->expenseFieldRepository->findBy(['expense' => $expense]);
                    $expenseGroupsArray[$expenseGroup->getSlug()][] = [
                        'id' => $expense->getId(),
                        'expenseType' => $expense->getExpenseType(),
                        'fields' => $fields,
                    ];
                }

                // si le groupe est de type "unique", récupérer le type de dépense sélectionné
                if ($expenseGroup->getType() === 'unique' && $expenses) {
                    $expenseGroupsArray[$expenseGroup->getSlug()]['selectedType'] = $expenseType->getSlug();
                }
            }
        }

        return [
            'id' => $expenseReport->getId(),
            'status' => $expenseReport->getStatus(),
            'statusComment' => $expenseReport->getStatusComment(),
            'refundRequired' => $expenseReport->isRefundRequired(),
            'user' => $expenseReport->getUser(),
            'event' => $expenseReport->getEvent(),
            'createdAt' => $expenseReport->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $expenseReport->getUpdatedAt()->format('Y-m-d H:i:s'),
            'expenseGroups' => $expenseGroupsArray,
        ];
    }

    /**
     * Used to create an ExpenseReport from an array of data (e.g. from a JSON payload)
     * (do not use this method to handle an existing ExpenseReport) 
     * @param array<string, mixed> $data
     */
    public function unserialize(array $data): ExpenseReport
    {
        $expenseReport = new ExpenseReport();

        // gérer chaque groupe de dépense
        foreach ($data as $dataExpenseGroup) {
            // pour chaque type de dépense dans le groupe
            foreach ($dataExpenseGroup['expenseTypes'] as $dataExpenseType) {
                // créer chaque champ
                $fields = [];
                foreach ($dataExpenseType['fields'] as $dataField) {
                    $expenseField = new ExpenseField();
                    // todo add justification document
                    $expenseField->setFieldType($dataField['fieldTypeId']);
                    $expenseField->setValue($dataField['value']);
    
                    $fields[] = $expenseField;
                }
            }
        }
        
        $expenseReport->setStatus($data['status']);
        return $expenseReport;
    }
}
