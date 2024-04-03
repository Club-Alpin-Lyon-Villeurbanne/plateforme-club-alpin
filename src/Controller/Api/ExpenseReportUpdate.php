<?php

namespace App\Controller\Api;

use App\Entity\ExpenseReport;
use App\UserRights;
use App\Utils\Serialize\ExpenseReportSerializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class ExpenseReportUpdate extends AbstractController
{
    public function __construct(
        private ExpenseReportSerializer $expenseReportSerializer,
        private EntityManagerInterface $entityManager,
        private UserRights $userRights
    ) {
        
    }

    public function __invoke(ExpenseReport $expenseReport): JsonResponse
    {
        if (!$this->userRights->allowed('evt_join_doall')) {
            return new JsonResponse([
                'success' => false,
                'message' => 'You are not allowed to update this expense report',
            ], 403);
        }

        $this->entityManager->persist($expenseReport);
        $this->entityManager->flush();
        $expenseReportSerialized = $this->expenseReportSerializer->serialize($expenseReport);

        return new JsonResponse([
            'success' => true,
            'expenseReport' => $expenseReportSerialized,
        ]);
    }
}
