<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ExpenseReportVoter extends Voter
{
    public const MANAGE_EXPENSE_REPORTS = 'manage_expense_reports';

    private array $authorizedManagerIds;

    public function __construct(string $authorizedManagerIds)
    {
        $this->authorizedManagerIds = explode(',', $authorizedManagerIds);
    }

    protected function supports($attribute, $subject): bool
    {
        return \in_array($attribute, [self::MANAGE_EXPENSE_REPORTS], true);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::MANAGE_EXPENSE_REPORTS => $this->canManageExpenseReports($user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    private function canManageExpenseReports(User $user): bool
    {
        return \in_array((string) $user->getId(), $this->authorizedManagerIds, true);
    }
}
