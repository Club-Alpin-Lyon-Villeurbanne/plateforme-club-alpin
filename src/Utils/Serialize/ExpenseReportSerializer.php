<?php

namespace App\Utils\Serialize;

use App\Entity\Expense;
use App\Entity\ExpenseField;
use App\Entity\ExpenseReport;
use App\Repository\ExpenseFieldRepository;
use App\Repository\ExpenseReportRepository;
use App\Repository\ExpenseRepository;

class ExpenseReportSerializer
{
    public function __construct(
        private ExpenseReportRepository $expenseReportRepository,
        private ExpenseFieldRepository $expenseFieldRepository,
        private ExpenseRepository $expenseRepository,
    ) {
    }

    public function serialize(ExpenseReport $expenseReport): array
    {
        return [
            'id' => $expenseReport->getId(),
            'status' => $expenseReport->getStatus(),
            'createdAt' => $expenseReport->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $expenseReport->getUpdatedAt()->format('Y-m-d H:i:s'),
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
