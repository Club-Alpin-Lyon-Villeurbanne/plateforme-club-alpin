<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ExpenseReportVoter extends Voter
{
    public const VALIDATE_EXPENSE_REPORT = 'validate_expense_report';

    private $authorizedToValidateIds;

    public function __construct(string $authorizedToValidateIds)
    {
        $this->authorizedToValidateIds = explode(',', $authorizedToValidateIds);
    }

    protected function supports($attribute, $subject): bool
    {
        return \in_array($attribute, [self::VALIDATE_EXPENSE_REPORT], true);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::VALIDATE_EXPENSE_REPORT => $this->canValidateExpenseReport($user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    private function canValidateExpenseReport(User $user): bool
    {
        return \in_array((string) $user->getId(), $this->authorizedToValidateIds, true);
    }
}