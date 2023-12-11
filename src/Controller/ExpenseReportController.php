<?php

namespace App\Controller;

use App\Entity\Expense;
use App\Entity\ExpenseField;
use App\Entity\ExpenseReport;
use App\Repository\EvtRepository;
use App\Repository\ExpenseFieldTypeRepository;
use App\Repository\ExpenseTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ExpenseReportController extends AbstractController
{

    #[Route('/expense-report', name: 'app_expense_report_post')]
    public function post(
        Request $request, 
        ExpenseTypeRepository $expenseTypeRepository,
        ExpenseFieldTypeRepository $expenseFieldTypeRepository,
        EvtRepository $evtRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        // return new JsonResponse($data);

        // créer la note de frais
        $expenseReport = new ExpenseReport();
        $expenseReport->setStatus($data['status']);
        $expenseReport->setUser($this->getUser());
        $expenseReport->setRefundRequired(!!$data['refundRequired']);
        $expenseReport->setEvent($evtRepository->find($data['eventId']));
        $entityManager->persist($expenseReport);

        // pour chaque groupe de dépense
        foreach ($data as $dataExpenseGroup) {
            if (!is_array($dataExpenseGroup)) {
                continue;
            }
            // pour chaque dépense
            foreach ($dataExpenseGroup['expenseTypes'] as $dataExpenseType) {
                // si le groupe est de type "unique", ne pas traiter les types non selectionnés
                if ($dataExpenseGroup['type'] === 'unique' && !$dataExpenseType['slug'] !== $dataExpenseGroup['selectedType']) {
                    continue;
                }
                // créer la dépense
                $expense = new Expense();
                $expenseType = $expenseTypeRepository->find($dataExpenseType['expenseTypeId']);
                $expense->setExpenseType($expenseType);
                $expense->setExpenseReport($expenseReport);
                $entityManager->persist($expense);
                // pour chaque champ
                foreach ($dataExpenseType['fields'] as $dataField) {
                    // créer le champ
                    // todo add justification document
                    $expenseField = new ExpenseField();
                    $fieldType = $expenseFieldTypeRepository->find($dataField['fieldTypeId']);
                    $expenseField->setFieldType($fieldType);
                    $expenseField->setValue($dataField['value']);
                    $expenseField->setExpense($expense);
                    $entityManager->persist($expenseField);
                }
            }
        }

        $entityManager->flush();

        // rediriger vers la sortie correspondante
        return new JsonResponse($data);
    }
}
