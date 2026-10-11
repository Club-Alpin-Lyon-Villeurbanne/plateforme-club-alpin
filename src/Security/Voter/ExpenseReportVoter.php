<?php

namespace App\Security\Voter;

use App\Entity\ExpenseReport;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ExpenseReportVoter extends Voter
{
    public const MANAGE_EXPENSE_REPORTS = 'manage_expense_reports';
    // Sujet : une ExpenseReport. Lecture de la note et de ses justificatifs, modification de la note.
    public const ACCESS_EXPENSE_REPORT = 'access_expense_report';

    private array $authorizedManagerIds;

    public function __construct(string $authorizedManagerIds)
    {
        $this->authorizedManagerIds = explode(',', $authorizedManagerIds);
    }

    protected function supports($attribute, $subject): bool
    {
        return self::MANAGE_EXPENSE_REPORTS === $attribute
            || (self::ACCESS_EXPENSE_REPORT === $attribute && $subject instanceof ExpenseReport);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::MANAGE_EXPENSE_REPORTS => $this->canManageExpenseReports($user),
            self::ACCESS_EXPENSE_REPORT => $this->canAccessExpenseReport($user, $subject),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    private function canManageExpenseReports(User $user): bool
    {
        return \in_array((string) $user->getId(), $this->authorizedManagerIds, true);
    }

    private function canAccessExpenseReport(User $user, ExpenseReport $expenseReport): bool
    {
        $owner = $expenseReport->getUser();

        return (null !== $owner && $owner->getId() === $user->getId())
            || $this->canManageExpenseReports($user);
    }
}
