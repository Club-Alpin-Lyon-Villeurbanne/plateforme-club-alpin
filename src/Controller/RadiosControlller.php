<?php

namespace App\Controller;

use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class RadiosControlller extends AbstractController
{
    #[Route(path: '/radios-secours-montagne', name: 'radios')]
    #[IsGranted('ROLE_USER')]
    #[Template('radios/index.html.twig')]
    public function index()
    {
        return [];
    }
}
