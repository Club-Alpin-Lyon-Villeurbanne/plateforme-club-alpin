<?php

namespace App\Security\Voter;

use App\Entity\Article;
use App\UserRights;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ArticleViewVoter extends Voter
{
    private UserRights $userRights;

    public function __construct(UserRights $userRights)
    {
        $this->userRights = $userRights;
    }

    protected function supports($attribute, $subject): bool
    {
        return 'ARTICLE_VIEW' === $attribute;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof Article) {
            throw new \InvalidArgumentException(sprintf('The voter "%s" requires an article subject', __CLASS__));
        }

        $user = $token->getUser();
        if ($user && $subject->getUser() === $user) {
            return true;
        }

        $commission = $subject->getCommission();
        if (null === $commission && $subject->getEvt()) {
            $commission = $subject->getEvt()->getCommission();
        }

        if (
            !$subject->isPublic()
            && (
                $commission && !$this->userRights->allowedOnCommission('article_validate', $commission)
                && !$this->userRights->allowed('article_validate_all')
            )
        ) {
            return false;
        }

        if ($commission && $this->userRights->allowedOnCommission('article_read', $commission) || $this->userRights->allowedOnCommission('article_validate', $commission)) {
            return true;
        }

        if ($this->userRights->allowed('article_read') || $this->userRights->allowed('article_validate_all')) {
            return true;
        }

        return false;
    }
}
