<?php

namespace App\Controller;

use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class LocalClubController extends AbstractController
{
    #[Route(path: '/local-club', name: 'local_club')]
    #[IsGranted('ROLE_USER')]
    #[Template('local_club/index.html.twig')]
    public function index()
    {
        return [
        ];
    }
}
