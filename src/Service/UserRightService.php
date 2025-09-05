<?php

namespace App\Service;

use App\Entity\Commission;
use App\Entity\User;
use App\Entity\UserAttr;
use App\Entity\Usertype;
use App\Mailer\Mailer;
use App\Repository\UserAttrRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class UserRightService
{
    public function __construct(
        protected Mailer $mailer,
        protected EntityManagerInterface $manager,
        protected UserAttrRepository $attrRepository,
        protected LoggerInterface $logger,
    ) {
    }

    public function removeRightAndNotify(int $idUserAttr, ?User $whoUser): void
    {
        $userRight = $this->manager->getRepository(UserAttr::class)->find($idUserAttr);
        $this->manager->remove($userRight);
        $this->manager->flush();

        try {
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
        } catch (\Exception $exception) {
            $this->logger->error('Impossible d\'envoyer les notifications de retrait de responsabilité');
            $this->logger->error($exception->getMessage());
        }
    }

    public function notifyUserAfterRightAdded(int $idUser, int $idUserType, string $params, ?User $whoUser): void
    {
        $user = $this->manager->getRepository(User::class)->find($idUser);
        $userType = $this->manager->getRepository(Usertype::class)->find($idUserType);
        $userRight = $this->manager->getRepository(UserAttr::class)->findOneBy(['user' => $user, 'userType' => $userType, 'params' => $params]);

        try {
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
        } catch (\Exception $exception) {
            $this->logger->error('Impossible d\'envoyer les notifications d\'ajout de responsabilité');
            $this->logger->error($exception->getMessage());
        }
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

    public function sendNotificationToManagement(string $mailTemplate, ?UserAttr $userRight, ?string $by_who_name): void
    {
        $commissionLabel = $this->findCommission($userRight->getCommission())->getTitle();
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
                $presidents = $this->attrRepository->listAllManagement();
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
