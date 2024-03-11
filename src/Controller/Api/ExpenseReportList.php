<?php

namespace App\Controller\Api;

use App\Repository\ExpenseReportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class ExpenseReportList extends AbstractController
{
    public function __construct(private ExpenseReportRepository $expenseReportRepository)
    {
        
    }

    public function __invoke(): JsonResponse
    {
        $expenseReportSerialized = $this->expenseReportRepository->findAll();;

        return new JsonResponse([
            'success' => true,
            'expenseReport' => $expenseReportSerialized,
        ]);
    }
}
