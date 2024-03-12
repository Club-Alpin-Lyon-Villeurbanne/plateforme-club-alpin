<?php

namespace App\Controller\Api;

use App\Entity\ExpenseReport;
use App\UserRights;
use App\Utils\Serialize\ExpenseReportSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class ExpenseReportGet extends AbstractController
{
    public function __construct(
        private ExpenseReportSerializer $expenseReportSerializer,
        private UserRights $userRights
    ) {
        
    }

    public function __invoke(ExpenseReport $expenseReport): JsonResponse
    {
        if (!$this->userRights->allowed('evt_join_doall')) {
            return new JsonResponse([
                'success' => false,
                'message' => 'You are not allowed to view this expense report',
            ], 403);
        }
        
        $expenseReportSerialized = $this->expenseReportSerializer->serialize($expenseReport);

        return new JsonResponse([
            'success' => true,
            'expenseReport' => $expenseReportSerialized,
        ]);
    }
}
