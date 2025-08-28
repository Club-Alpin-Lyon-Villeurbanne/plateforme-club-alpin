<?php

namespace App\Controller;

use App\Entity\UserAttr;
use App\Entity\Usertype;
use App\Repository\UserAttrRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route(path: '/statuts-adherents', name: 'user_status_list', methods: ['GET'])]
    #[Template('user/status-list.html.twig')]
    public function userStatusList(EntityManagerInterface $manager, UserAttrRepository $userAttrRepository): array
    {
        $ignoredRoles = [UserAttr::VISITEUR, UserAttr::ADHERENT, UserAttr::DEVELOPPEUR];
        $listedRoles = [];
        $listedUsers = [];
        $users = [];
        $counts = [];

        $roles = $manager->getRepository(Usertype::class)->findBy([], ['hierarchie' => 'ASC']);
        foreach ($roles as $role) {
            if (!\in_array($role->getCode(), $ignoredRoles, true)) {
                $listedRoles[$role->getCode()] = $role->getTitle();
                $users[$role->getCode()] = $userAttrRepository->listAllUsersByRole($role);
            }
        }

        foreach ($users as $roleCode => $roleUsers) {
            /** @var UserAttr $userAttr */
            foreach ($roleUsers as $userAttr) {
                $listedUsers[$roleCode][$userAttr->getUser()->getId()] = $userAttr->getUser()->getNickname();
            }
        }
        foreach ($listedRoles as $code => $role) {
            $counts[$code] = \count($listedUsers[$code]);
        }

        return [
            'roles' => $listedRoles,
            'users' => $listedUsers,
            'counts' => $counts,
        ];
    }
}
