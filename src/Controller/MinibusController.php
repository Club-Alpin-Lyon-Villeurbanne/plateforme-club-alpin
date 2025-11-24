<?php

namespace App\Controller;

use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MinibusController extends AbstractController
{
    #[Route(path: '/minibus', name: 'minibus')]
    #[IsGranted('ROLE_USER')]
    #[Template('minibus/index.html.twig')]
    public function index()
    {
        return [
        ];
    }
}
