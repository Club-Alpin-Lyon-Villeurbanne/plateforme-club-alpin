<?php

namespace App\Controller;

use App\Entity\Expense;
use App\Entity\ExpenseField;
use App\Entity\ExpenseReport;
use App\Repository\EvtRepository;
use App\Repository\ExpenseFieldTypeRepository;
use App\Repository\ExpenseTypeExpenseFieldTypeRepository;
use App\Repository\ExpenseTypeRepository;
use App\Utils\FileUploadHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ExpenseReportController extends AbstractController
{

    #[Route('/expense-report', name: 'app_expense_report_post')]
    public function post(
        Request $request, 
        ExpenseTypeRepository $expenseTypeRepository,
        ExpenseFieldTypeRepository $expenseFieldTypeRepository,
        EvtRepository $evtRepository,
        ExpenseTypeExpenseFieldTypeRepository $expenseTypeExpenseFieldTypeRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {

        // TODO: vérifier ACL

        $data = json_decode($request->getContent(), true);

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
            // pour chaque dépense dans le groupe
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

                // pour chaque champ dans la dépense
                foreach ($dataExpenseType['fields'] as $dataField) {
                    // créer le champ
                    $expenseField = new ExpenseField();
                    $fieldType = $expenseFieldTypeRepository->find($dataField['fieldTypeId']);
                    $expenseField->setFieldType($fieldType);
                    $expenseField->setValue($dataField['value']);
                    $expenseField->setExpense($expense);
                    
                    // check if this field needs a justification document in this expense type
                    $relation = $expenseTypeExpenseFieldTypeRepository->findOneBy([
                        'expenseType' => $expenseType,
                        'expenseFieldType' => $fieldType
                    ]);

                    if ($relation->getNeedsJustification()) {
                        if (empty($dataField['justificationFileUrl'])) {
                            throw new BadRequestHttpException('Missing justification document');
                        }
                        $expenseField->setJustificationDocument($dataField['justificationFileUrl']);
                    }

                    $entityManager->persist($expenseField);
                }
            }
        }

        $entityManager->flush();

        // rediriger vers la sortie correspondante
        return new JsonResponse($data);
    }

    #[Route('/expense-report/justification-document', name: 'app_expense_report_upload_justification_document', methods: ['POST'])]
    public function uploadJustificationDocument(Request $request)
    {
        // TODO: vérifier les ACL
        
        $file = $request->files->get('justification_document');
        
        if (!$file) {
            throw new BadRequestHttpException('No file uploaded');
        }

        $extension = $file->getClientOriginalExtension();
        $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        // rebuild filename with hashed timestamp and extension
        $filename = $filename . '_' . substr(md5(time()), 0, 6) . '.' . $extension;
        $file->move(FileUploadHelper::getUserUploadPath($this->getUser()), $filename);

        return new JsonResponse([
            'success' => true,
            'fileUrl' => FileUploadHelper::getUserUploadUrl($this->getUser()) . '/' . $filename,
        ]);

    }
}
