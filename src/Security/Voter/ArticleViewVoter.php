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
        } elseif (!$subject->isPublic()) {
            return false;
        }

        $commission = $subject->getCommission();
        if (null === $commission && $subject->getEvt()) {
            $commission = $subject->getEvt()->getCommission();
        }
        if ($commission && $this->userRights->allowedOnCommission('article_read', $commission)) {
            return true;
        }

        if ($this->userRights->allowed('article_read')) {
            return true;
        }

        return false;
    }
}
