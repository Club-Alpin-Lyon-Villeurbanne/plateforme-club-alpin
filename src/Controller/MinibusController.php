<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class MinibusController extends AbstractController
{
    
    #[Route(path: '/minibus', name: 'minibus')]
    #[Template]
    #[Security("is_granted('ROLE_USER')")]
    public function index()
    {
        return [
        ];
    }
}
