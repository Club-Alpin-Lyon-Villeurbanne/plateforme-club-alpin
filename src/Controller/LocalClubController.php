<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class LocalClubController extends AbstractController
{
    
    #[Route(path: '/local-club', name: 'local_club')]
    #[Template]
    #[Security("is_granted('ROLE_USER')")]
    public function index()
    {
        return [
        ];
    }
}
