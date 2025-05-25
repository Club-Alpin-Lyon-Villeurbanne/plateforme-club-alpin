<?php
// src/Controller/TestMicromodalController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestMicromodalController extends AbstractController
{
    #[Route('/test-micromodal', name: 'test_micromodal')]
    public function index(): Response
    {
        return $this->render('test/micromodal.html.twig');
    }
}