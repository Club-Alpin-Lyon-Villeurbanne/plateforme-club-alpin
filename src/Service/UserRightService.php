<?php

namespace App\Service;

use App\Entity\Commission;
use App\Entity\User;
use App\Entity\UserAttr;
use App\Entity\Usertype;
use App\Mailer\Mailer;
use Doctrine\ORM\EntityManagerInterface;

class UserRightService
{
    public function __construct(protected Mailer $mailer, protected EntityManagerInterface $manager)
    {
    }

    public function removeRightAndNotify(int $idUserAttr, ?User $whoUser): void
    {
        $userRight = $this->manager->getRepository(UserAttr::class)->find($idUserAttr);
        $this->manager->remove($userRight);
        $this->manager->flush();

        $this->sendNotificationToUser(
            'suppression-utilisateur',
            $userRight->getUser(),
            $userRight->getUserType()->getTitle(),
            $userRight->getCommission(),
            $whoUser->getFullName()
        );
    }

    public function notifyUserAfterRightAdded(int $idUser, int $idUserType, string $params, ?User $whoUser): void
    {
        $user = $this->manager->getRepository(User::class)->find($idUser);
        $userType = $this->manager->getRepository(Usertype::class)->find($idUserType);
        $userRight = $this->manager->getRepository(UserAttr::class)->findOneBy(['user' => $user, 'userType' => $userType, 'params' => $params]);

        $this->sendNotificationToUser(
            'ajout-utilisateur',
            $userRight->getUser(),
            $userRight->getUserType()->getTitle(),
            $userRight->getCommission(),
            $whoUser->getFullName()
        );
    }

    public function sendNotificationToUser(string $mailTemplate, User $user, string $rightLabel, ?string $commissionCode, ?string $by_who_name): void
    {
        $commissionLabel = $this->findCommission($commissionCode)->getTitle();
        $this->mailer->send($user, 'transactional/droits/' . $mailTemplate, [
            'right_name' => $rightLabel,
            'commission' => $commissionLabel,
            'by_who' => $by_who_name,
        ]);
    }

    protected function findCommission(string $code): ?Commission
    {
        return $this->manager->getRepository(Commission::class)->findOneBy(['code' => $code]);
    }
}
