<?php

namespace App\Security\Voter;

use App\Entity\Article;
use App\Entity\User;
use App\UserRights;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ArticleUpdateVoter extends Voter
{
    private UserRights $userRights;

    public function __construct(UserRights $userRights)
    {
        $this->userRights = $userRights;
    }

    protected function supports($attribute, $subject): bool
    {
        return \in_array($attribute, ['ARTICLE_UPDATE'], true);
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
        $commission = $subject->getCommission();
        if (null === $commission && $subject->getEvt()) {
            $commission = $subject->getEvt()->getCommission();
        }

        if ($subject->getUser() === $user && $this->userRights->allowed('article_edit')) {
            return true;
        }

        return $this->userRights->allowedOnCommission('article_edit_notmine', $commission);
    }
}
