<?php

namespace App\State;

use ApiPlatform\Doctrine\Orm\Paginator;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\ExpenseReportRepository;
use App\Utils\Enums\ExpenseReportStatusEnum;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
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

        $filters = $context['filters'] ?? [];
        $includeDrafts = isset($filters['include_drafts']) && 'true' === $filters['include_drafts'];

        if ($operation instanceof \ApiPlatform\Metadata\CollectionOperationInterface) {
            $qb = $this->expenseReportRepository->createQueryBuilder('er');
            if (isset($filters['event'])) {
                $qb->andWhere('er.event = :event')
                   ->setParameter('event', $filters['event']);
            }

            if (!$canValidateReport) {
                $qb->andWhere('er.user = :user')
                   ->setParameter('user', $this->security->getUser());
            }

            // Exclure les notes de frais en brouillon seulement si include_drafts n'est pas défini à true
            if (!$includeDrafts) {
                $qb->andWhere('er.status != :draftStatus')
                   ->setParameter('draftStatus', ExpenseReportStatusEnum::DRAFT->value);
            }

            // Gérer la pagination
            $page = (int) ($context['filters']['page'] ?? 1);
            $itemsPerPage = (int) ($context['filters']['itemsPerPage'] ?? 30);
            $firstResult = ($page - 1) * $itemsPerPage;
            
            $qb->setFirstResult($firstResult)
               ->setMaxResults($itemsPerPage);
            
            $doctrinePaginator = new DoctrinePaginator($qb);
            $doctrinePaginator->setUseOutputWalkers(false);
            
            return new Paginator($doctrinePaginator);
        }

        $criterias = [
            'id' => $uriVariables['id'] ?? null,
        ];

        if (!$canValidateReport) {
            $criterias['user'] = $this->security->getUser();
        }

        if (isset($filters['event'])) {
            $criterias['event'] = $filters['event'];
        }

        // For single item operations
        return $this->expenseReportRepository->findOneBy($criterias);
    }
}
