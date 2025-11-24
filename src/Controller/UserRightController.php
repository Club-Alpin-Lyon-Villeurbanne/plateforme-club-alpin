<?php

namespace App\Controller;

use App\Entity\Commission;
use App\Entity\User;
use App\Entity\UserAttr;
use App\Entity\Usertype;
use App\Service\UserRightService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class UserRightController extends AbstractController
{
    #[Route(path: '/retirer-responsabilite/{type}/{commission}', name: 'user_right_auto_remove', requirements: ['type' => '[a-z0-9-_]+'], methods: ['GET', 'POST'])]
    public function autoRemoveRight(
        EntityManagerInterface $manager,
        UserRightService $userRightService,
        LoggerInterface $logger,
        #[MapEntity(mapping: ['type' => 'code'])]
        Usertype $type,
        #[MapEntity(mapping: ['commission' => 'code'])]
        ?Commission $commission = null,
    ): RedirectResponse {
        if (!$this->getUser()) {
            throw new AccessDeniedHttpException('Seuls les adhérents connectés peuvent effectuer cette action');
        }

        /** @var User $user */
        $user = $this->getUser();
        $userRight = $user->getAttribute($type->getCode(), $commission?->getCode() ?? null);
        if ($userRight instanceof UserAttr) {
            $manager->remove($userRight);
            $manager->flush();
            try {
                $userRightService->notify($userRight, 'suppression', $user);
            } catch (\Exception $exception) {
                $logger->error('Impossible de notifier le retrait d\'une responsabilité');
                $logger->error($exception->getMessage());
            }
            $this->addFlash('success', 'La responsabilité ' . $type->getTitle() . ' vous a bien été retirée' . ($commission ? ' pour la commission ' . $commission->getTitle() : '') . '.');
        } else {
            $this->addFlash('error', 'Vous n\'avez pas la responsabilité ' . $type->getTitle() . ' ' . ($commission ? 'pour la commission ' . $commission->getTitle() : '') . ', vous ne pouvez donc pas la retirer.');
        }

        return $this->redirect('/profil/infos.html');
    }
}
