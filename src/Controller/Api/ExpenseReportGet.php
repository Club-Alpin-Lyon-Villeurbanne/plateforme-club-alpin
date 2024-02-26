<?php

namespace App\Controller\Api;

use App\Entity\ExpenseReport;
use App\Utils\Serialize\ExpenseReportSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class ExpenseReportGet extends AbstractController
{
    public function __construct(private ExpenseReportSerializer $expenseReportSerializer)
    {
        
    }

    public function __invoke(ExpenseReport $expenseReport): JsonResponse
    {
        $expenseReportSerialized = $this->expenseReportSerializer->serialize($expenseReport);

        return new JsonResponse([
            'success' => true,
            'expenseReport' => $expenseReportSerialized,
        ]);
    }
}
