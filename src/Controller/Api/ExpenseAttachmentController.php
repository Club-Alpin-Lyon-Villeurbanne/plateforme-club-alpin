<?php

namespace App\Controller\Api;

use App\Entity\Attachment;
use App\Entity\ExpenseAttachment;
use App\Repository\ExpenseAttachmentRepository;
use App\Repository\ExpenseReportRepository;
use App\Utils\FileUploader;
use App\Validator\ExpenseReport\FieldPermissionValidator;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Proxies\__CG__\App\Entity\Expense;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
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

        $expenseReport = $this->expenseReportRepository->findOneBy(['id' => $expenseReportId, 'user' => $user]);
        if (!$expenseReport) {
            throw $this->createNotFoundException('ExpenseReport not found');
        }

        // La note reste verrouillée jusqu'à l'enregistrement du justificatif : un envoi simultané
        // attend, et le statut est relu une fois le verrou pris.
        $this->entityManager->beginTransaction();
        try {
            $this->entityManager->refresh($expenseReport, LockMode::PESSIMISTIC_WRITE);

            // Avant toute écriture sur disque : une fois la note soumise, ses justificatifs sont figés.
            if (!$expenseReport->getStatus()?->isEditableByOwner()) {
                throw new UnprocessableEntityHttpException(FieldPermissionValidator::NOTE_DEJA_ENVOYEE);
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
            $existingAttachments = $this->attachmentRepository->findByExpenseReportAndExpenseId($expenseReport, $body['expenseId']);
            $existingAttachment = array_shift($existingAttachments);
            // fichiers supprimés seulement après le flush, pour ne pas perdre un justificatif si l'enregistrement échoue
            $filesToDelete = [];

            // un double envoi simultané a pu créer des doublons, qui faisaient échouer tout nouvel envoi
            foreach ($existingAttachments as $duplicate) {
                $filesToDelete[] = $duplicate->getFilePath();
                $this->entityManager->remove($duplicate);
            }

            if ($existingAttachment) {
                // Update existing attachment
                $filesToDelete[] = $existingAttachment->getFilePath();
                $existingAttachment->setFileName($file->getFilename());
                $existingAttachment->setFilePath($file->getPathname());
                $attachment = $existingAttachment;
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
            $this->entityManager->commit();
        } catch (\Throwable $erreur) {
            $this->entityManager->rollback();

            throw $erreur;
        }

        foreach ($filesToDelete as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }

        return $this->json($attachment, Response::HTTP_CREATED, [], ['groups' => 'attachment:read']);
    }
}
