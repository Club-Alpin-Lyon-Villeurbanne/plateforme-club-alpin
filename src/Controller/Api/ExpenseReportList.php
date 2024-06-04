<?php

namespace App\Controller\Api;

use App\Repository\ExpenseReportRepository;
use App\UserRights;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class ExpenseReportList extends AbstractController
{
    public function __construct(
        private ExpenseReportRepository $expenseReportRepository,
        private UserRights $userRights
    ) {
    }

    public function __invoke(): JsonResponse
    {
        if (!$this->userRights->allowed('evt_join_doall')) {
            return new JsonResponse([
                'success' => false,
                'message' => 'You are not allowed to view this resource',
            ], 403);
        }

        $expenseReportList = $this->expenseReportRepository->findAll();

        return new JsonResponse([
            'success' => true,
            'expenseReports' => $expenseReportList,
        ]);
    }
}
