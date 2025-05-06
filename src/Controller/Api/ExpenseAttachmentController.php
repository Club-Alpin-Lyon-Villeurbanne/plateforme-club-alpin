<?php

namespace App\Controller\Api;

use App\Entity\Attachment;
use App\Entity\ExpenseAttachment;
use App\Repository\ExpenseAttachmentRepository;
use App\Repository\ExpenseReportRepository;
use App\Utils\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Proxies\__CG__\App\Entity\Expense;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsController]
class ExpenseAttachmentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ExpenseAttachmentRepository $attachmentRepository,
        private ExpenseReportRepository $expenseReportRepository,
        private Security $security,
        private FileUploader $fileUploader
    ) {
    }

    public function __invoke(Request $request, ValidatorInterface $validator, string $expenseReportId): Response
    {
        $user = $this->security->getUser();

        $body = $request->getPayload()->all();

        $expenseReport = $this->expenseReportRepository->getExpenseReportByEventAndUser($expenseReportId, $user);
        if (!$expenseReport) {
            throw $this->createNotFoundException('ExpenseReport not found');
        }

        $file = $request->files->get('file');
        if (!$file) {
            throw new BadRequestHttpException('File is required');
        }

        $fileConstraint = new File([
            'maxSize' => '8M',
            'extensions' => [
                'jpg',
                'jpeg',
                'png',
                'pdf' => 'application/pdf',
            ],
        ]);

        $errors = $validator->validate($file, $fileConstraint);

        if ($errors->count() > 0) {
            throw new BadRequestHttpException((string) $errors);
        }

        if (empty($body['expenseId'])) {
            throw new BadRequestHttpException('expenseId parameter is missing');
        }

        $file = $this->fileUploader->upload($file, 'expense-attachments');

        // Check if an attachment already exists for this expense
        $existingAttachment = $this->attachmentRepository->findByExpenseReportAndExpenseId($expenseReport, $body['expenseId']);

        if ($existingAttachment) {
            // Update existing attachment
            $oldFilePath = $existingAttachment->getFilePath();
            $existingAttachment->setFileName($file->getFilename());
            $existingAttachment->setFilePath($file->getPathname());
            $attachment = $existingAttachment;

            // Remove old file if it exists
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }
        } else {
            // Create new attachment
            $attachment = new ExpenseAttachment();
            $attachment->setUser($user);
            $attachment->setExpenseReport($expenseReport);
            $attachment->setExpenseId($body['expenseId']);
            $attachment->setFileName($file->getFilename());
            $attachment->setFilePath($file->getPathname());
        }

        $this->entityManager->persist($attachment);
        $this->entityManager->flush();

        return $this->json($attachment, Response::HTTP_CREATED, [], ['groups' => 'attachment:read']);
    }
}
