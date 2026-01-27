<?php

namespace App\Security\Voter;

use App\Entity\Article;
use App\Entity\Commission;
use App\Entity\User;
use App\UserRights;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ArticleManageVoter extends Voter
{
    private UserRights $userRights;

    public function __construct(UserRights $userRights)
    {
        $this->userRights = $userRights;
    }

    protected function supports($attribute, $subject): bool
    {
        return \in_array($attribute, ['ARTICLE_MANAGE'], true);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (!$subject instanceof Article) {
            throw new \InvalidArgumentException(sprintf('The voter "%s" requires an article subject', __CLASS__));
        }

        $validate = false;
        $validateAll = $this->userRights->allowed('article_validate_all');
        $commission = $subject->getCommission();
        if (null === $commission && $subject->getEvt()) {
            $commission = $subject->getEvt()->getCommission();
        }
        if ($commission instanceof Commission) {
            $validate = $this->userRights->allowedOnCommission('article_validate', $commission);
        }

        return $validate || $validateAll;
    }
}
