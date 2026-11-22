<?php

namespace App\Validator\ExpenseReport;

use App\Entity\ExpenseReport;
use App\Utils\Enums\ExpenseReportStatusEnum;
use Doctrine\ORM\EntityManagerInterface;

/**
 * État d'une note de frais tel qu'enregistré en base, avant les modifications de la requête en cours.
 */
class ExpenseReportOriginalState
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Statut d'origine, ou null s'il est inconnu (entité non gérée par Doctrine).
     *
     * Une entité chargée depuis la base porte un enum ; après un flush dans la même unité de
     * travail, Doctrine conserve la valeur brute (chaîne).
     */
    public function status(ExpenseReport $expenseReport): ?ExpenseReportStatusEnum
    {
        $status = $this->value($expenseReport, 'status');

        if (\is_string($status)) {
            return ExpenseReportStatusEnum::tryFrom($status);
        }

        return $status instanceof ExpenseReportStatusEnum ? $status : null;
    }

    public function value(ExpenseReport $expenseReport, string $field): mixed
    {
        return $this->entityManager->getUnitOfWork()->getOriginalEntityData($expenseReport)[$field] ?? null;
    }
}
