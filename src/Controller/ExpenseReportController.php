<?php

namespace App\Controller;

use App\Entity\Expense;
use App\Entity\ExpenseField;
use App\Entity\ExpenseReport;
use App\Repository\EvtRepository;
use App\Repository\ExpenseFieldTypeRepository;
use App\Repository\ExpenseReportRepository;
use App\Repository\ExpenseTypeExpenseFieldTypeRepository;
use App\Repository\ExpenseTypeRepository;
use App\Utils\FileUploadHelper;
use App\Utils\Serialize\ExpenseReportSerializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ExpenseReportController extends AbstractController
{

    #[Route('/expense-report', name: 'app_expense_report_post', methods: ['POST'])]
    public function post(
        Request $request,
        ExpenseReportRepository $expenseReportRepository,
        ExpenseTypeRepository $expenseTypeRepository,
        ExpenseFieldTypeRepository $expenseFieldTypeRepository,
        EvtRepository $evtRepository,
        ExpenseTypeExpenseFieldTypeRepository $expenseTypeExpenseFieldTypeRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {

        // TODO: vérifier ACL

        $data = json_decode($request->getContent(), true);

        $errors = [];

        // créer la note de frais si nouvelle, ou récupérer la note de frais existante
        $expenseReport = $expenseReportRepository->getExpenseReportByEventAndUser($data['eventId'], $this->getUser()->getId());
        if (!$expenseReport) {
            $expenseReport = new ExpenseReport();
        }
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
                if ($dataExpenseGroup['type'] === 'unique' && $dataExpenseType['slug'] !== $dataExpenseGroup['selectedType']) {
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
                    $expenseField->setExpense($expense);
                    
                    // check if this field needs a justification document in this expense type
                    $relation = $expenseTypeExpenseFieldTypeRepository->findOneBy([
                        'expenseType' => $expenseType,
                        'expenseFieldType' => $fieldType
                    ]);

                    // gérer les champs obligatoires
                    if ($relation->isMandatory() && empty($dataField['value'])) {
                       $errors[] = [
                            'message' => 'Ce champ est obligatoire !',
                            'field' => $fieldType->getSlug(),
                            'expenseTypeId' => $expenseType->getId(),
                            'expenseGroup' => $dataExpenseGroup['slug'],
                        ];
                    } elseif (!empty($dataField['value'])) {
                        $expenseField->setValue($dataField['value']);
                    }

                    // gérer la présence des justificatifs
                    if (!empty($dataField['value']) && $relation->getNeedsJustification()) {
                        if (empty($dataField['justificationFileUrl'])) {
                            $errors[] = [
                                'message' => 'Un justificatif est obligatoire pour ce champ !',
                                'field' => $fieldType->getSlug(),
                                'expenseTypeId' => $expenseType->getId(),
                                'expenseGroup' => $dataExpenseGroup['slug'],
                            ];
                        } else {
                            $expenseField->setJustificationDocument($dataField['justificationFileUrl']);
                        }
                    }
                    if (!$errors) {
                        $entityManager->persist($expenseField);
                    }
                }
            }
        }

        if ($errors) {
            return new JsonResponse([
                'success' => false,
                'errors' => $errors,
            ], 400);
        }

        $entityManager->flush();
        $data['success'] = true;
        return new JsonResponse($data);
    }

    #[Route('/expense-report', name: 'app_expense_report_list', methods: ['GET'])]
    public function list(
        ExpenseReportRepository $expenseReportRepository
    ): JsonResponse
    {

        $expenseReportList = $expenseReportRepository->findAll();

        return new JsonResponse([
            'success' => true,
            'expenseReports' => $expenseReportList,
        ]);
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
        $file->move(FileUploadHelper::getUserUploadPath($this->getUser(), 'expense-reports-justification'), $filename);

        return new JsonResponse([
            'success' => true,
            'fileUrl' => FileUploadHelper::getUserUploadUrl($this->getUser(), 'expense-reports-justification') . '/' . $filename,
        ]);

    }
}
