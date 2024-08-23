<?php

namespace App\Controller\Api;

use App\Dto\ExpenseReportStatusDto;
use App\Entity\ExpenseReport;
use App\UserRights;
use App\Utils\Enums\ExpenseReportEnum;
use App\Utils\Serialize\ExpenseReportSerializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ExpenseReportUpdateStatus extends AbstractController
{
    public function __construct(
        private ExpenseReportSerializer $expenseReportSerializer,
        private EntityManagerInterface $entityManager,
        private UserRights $userRights
    ) {
    }

    public function __invoke(ExpenseReport $expenseReport, ExpenseReportStatusDto $expenseReportStatusDto): JsonResponse
    {
        if (!$this->userRights->allowed('evt_join_doall')) {
            return new JsonResponse([
                'success' => false,
                'message' => 'You are not allowed to update this expense report',
            ], Response::HTTP_FORBIDDEN);
        }

        if (\in_array($expenseReportStatusDto->status, [ExpenseReportEnum::STATUS_DRAFT, ExpenseReportEnum::STATUS_SUBMITTED], true)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'You cannot set the status to draft or submitted',
            ], Response::HTTP_BAD_REQUEST);
        }

        $expenseReport->setStatus($expenseReportStatusDto->status);
        $expenseReport->setStatusComment($expenseReportStatusDto->statusComment);
        $this->entityManager->persist($expenseReport);
        $this->entityManager->flush();
        $expenseReportSerialized = $this->expenseReportSerializer->serialize($expenseReport);

        return new JsonResponse([
            'success' => true,
            'expenseReport' => $expenseReportSerialized,
        ]);
    }
}
