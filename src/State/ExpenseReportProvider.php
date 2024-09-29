<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\ExpenseReportRepository;
use Symfony\Bundle\SecurityBundle\Security;

class ExpenseReportProvider implements ProviderInterface
{
    public function __construct(
        private ExpenseReportRepository $expenseReportRepository,
        private Security $security
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $canValidateReport = $this->security->isGranted('validate_expense_report');

        if ($operation instanceof \ApiPlatform\Metadata\CollectionOperationInterface) {
            $qb = $this->expenseReportRepository->createQueryBuilder('er');

            if (!$canValidateReport) {
                $qb->andWhere('er.user = :user')
                   ->setParameter('user', $this->security->getUser());
            }

            return $qb->getQuery()->getResult();
        }

        $criterias = [
            'id' => $uriVariables['id'] ?? null,
        ];

        if (!$canValidateReport) {
            $criterias['user'] = $this->security->getUser();
        }

        // For single item operations
        return $this->expenseReportRepository->findOneBy($criterias);
    }
}
