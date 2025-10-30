<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\ExpenseAttachment;
use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Utils\Enums\ExpenseReportStatusEnum;
use App\Utils\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ExpenseReportCloneProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private string $kernelProjectDir,
        private FileUploader $fileUploader
    ) {
    }

    /**
     * @param ExpenseReport $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ExpenseReport
    {
        $originalReport = $this->entityManager->getRepository(ExpenseReport::class)->find($uriVariables['id']);

        if (!$originalReport) {
            throw new \RuntimeException('Expense report not found');
        }

        /** @var User $currentUser */
        $currentUser = $this->security->getUser();
        if (!$currentUser) {
            throw new AccessDeniedHttpException('User not authenticated');
        }

        $existingReport = $this->entityManager->getRepository(ExpenseReport::class)
            ->getExpenseReportByEventAndUser($originalReport->getEvent()->getId(), $currentUser->getId());

        if ($existingReport && ExpenseReportStatusEnum::DRAFT === $existingReport->getStatus()) {
            $this->entityManager->remove($existingReport);
            $this->entityManager->flush();
        }

        $clonedReport = new ExpenseReport();
        $clonedReport->setStatus(ExpenseReportStatusEnum::DRAFT);
        $clonedReport->setRefundRequired($originalReport->isRefundRequired());
        $clonedReport->setUser($currentUser);
        $clonedReport->setEvent($originalReport->getEvent());
        $clonedReport->setDetails($originalReport->getDetails());

        foreach ($originalReport->getAttachments() as $originalAttachment) {
            try {
                $expenseId = $originalAttachment->getExpenseId();

                $newFile = $this->fileUploader->duplicateFile($originalAttachment->getFilePath(), 'expense-attachments');

                $clonedAttachment = new ExpenseAttachment();
                $clonedAttachment->setExpenseId($expenseId);
                $clonedAttachment->setFileName($newFile->getFilename());
                $clonedAttachment->setFilePath($newFile->getPathname());
                $clonedAttachment->setUser($currentUser);

                $clonedReport->addAttachment($clonedAttachment);
            } catch (\Exception $e) {
                throw new \RuntimeException('Failed to clone attachment: ' . $e->getMessage());
            }
        }

        $this->entityManager->persist($clonedReport);
        $this->entityManager->flush();

        return $clonedReport;
    }
}
