<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ExpenseReportController extends AbstractController
{
    #[Route('/expense-report', name: 'app_expense_report_post')]
    public function post(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // créer la note de frais
        // rediriger vers la sortie correspondante
        return new JsonResponse($data);
    }
}
