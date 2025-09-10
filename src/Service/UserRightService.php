<?php

namespace App\Service;

use App\Entity\Commission;
use App\Entity\User;
use App\Entity\UserAttr;
use App\Entity\Usertype;
use App\Mailer\Mailer;
use App\Repository\UserAttrRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserRightService
{
    public function __construct(
        protected Mailer $mailer,
        protected EntityManagerInterface $manager,
        protected UserAttrRepository $attrRepository,
    ) {
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
        $this->sendNotificationToManagement(
            'suppression-responsables',
            $userRight,
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
        $this->sendNotificationToManagement(
            'ajout-responsables',
            $userRight,
            $whoUser->getFullName()
        );
    }

    public function sendNotificationToUser(string $mailTemplate, User $user, string $rightLabel, ?string $commissionCode, ?string $by_who_name): void
    {
        $commissionLabel = '';
        if (!empty($commissionCode)) {
            $commissionLabel = $this->findCommission($commissionCode)?->getTitle();
        }
        $this->mailer->send($user, 'transactional/droits/' . $mailTemplate, [
            'right_name' => $rightLabel,
            'commission' => $commissionLabel,
            'by_who' => $by_who_name,
        ]);
    }

    public function sendNotificationToManagement(string $mailTemplate, ?UserAttr $userRight, ?string $by_who_name): void
    {
        $commissionLabel = '';
        if (!empty($userRight->getCommission())) {
            $commissionLabel = $this->findCommission($userRight->getCommission())?->getTitle();
        }
        $receivers = $this->getReceivers($userRight);

        /** @var UserAttr $receiver */
        foreach ($receivers as $receiver) {
            $this->mailer->send($receiver->getUser(), 'transactional/droits/' . $mailTemplate, [
                'right_name' => $userRight->getUserType()->getTitle(),
                'user_name' => $userRight->getUser()->getFullName(),
                'commission' => $commissionLabel,
                'by_who' => $by_who_name,
            ]);
        }
    }

    protected function findCommission(string $code): ?Commission
    {
        return $this->manager->getRepository(Commission::class)->findOneBy(['code' => $code]);
    }

    protected function getReceivers(?UserAttr $userRight): array
    {
        $receivers = [];
        switch ($userRight->getUserType()->getCode()) {
            case UserAttr::ENCADRANT:
            case UserAttr::STAGIAIRE:
            case UserAttr::COENCADRANT:
            case UserAttr::RESPONSABLE_COMMISSION:
                $commissionResp = $this->attrRepository
                    ->listAllEncadrants(
                        $this->findCommission($userRight->getCommission()),
                        [UserAttr::RESPONSABLE_COMMISSION]
                    )
                ;
                foreach ($commissionResp as $resp) {
                    $receivers[] = $resp;
                }
                $presidents = $this->attrRepository->listAllManagement([UserAttr::PRESIDENT]);
                foreach ($presidents as $president) {
                    $receivers[] = $president;
                }
                break;

            default:
                // pas d'email pour l'instant
                break;
        }

        return $receivers;
    }
}
