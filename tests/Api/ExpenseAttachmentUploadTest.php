<?php

namespace App\Tests\Api;

use App\Entity\ExpenseAttachment;
use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * Justificatifs (POST /api/notes-de-frais/{id}/pieces-jointes) : modifiables seulement
 * tant que la note est en brouillon ou rejetée, comme les montants qu'ils justifient.
 */
class ExpenseAttachmentUploadTest extends ExpenseReportApiTestCase
{
    private const EXPENSE_ID = 'transport.ticketPrice';

    public function testOwnerCanAttachToDraft(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::DRAFT);

        $this->assertResponseStatus(201, $this->uploadAttachment($this->owner, $report, self::EXPENSE_ID));
        $this->assertFileExists($this->attachmentPath($report));
    }

    public function testOwnerCanReplaceAttachmentOfRejectedReport(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::REJECTED);
        $previousPath = $this->createAttachment($report);

        $this->assertResponseStatus(201, $this->uploadAttachment($this->owner, $report, self::EXPENSE_ID));
        $this->assertNotSame($previousPath, $this->attachmentPath($report));
    }

    /**
     * @dataProvider frozenStatusProvider
     */
    public function testAttachmentsAreFrozenOnceSubmitted(ExpenseReportStatusEnum $status): void
    {
        $report = $this->createReport($this->owner, $status);
        $existingPath = $this->createAttachment($report);
        $filesBefore = $this->uploadedFiles($this->owner);

        $response = $this->uploadAttachment($this->owner, $report, self::EXPENSE_ID);

        $this->assertResponseStatus(422, $response);
        $this->assertSame($existingPath, $this->attachmentPath($report), 'Le justificatif enregistré ne doit pas changer.');
        $this->assertFileExists($existingPath, 'Le fichier d\'origine ne doit pas être supprimé.');
        $this->assertSame($filesBefore, $this->uploadedFiles($this->owner), 'Aucun fichier ne doit être écrit.');
    }

    public static function frozenStatusProvider(): iterable
    {
        yield 'submitted' => [ExpenseReportStatusEnum::SUBMITTED];
        yield 'approved' => [ExpenseReportStatusEnum::APPROVED];
        yield 'accounted' => [ExpenseReportStatusEnum::ACCOUNTED];
    }

    /**
     * Crée un justificatif existant (fichier sur disque + entité) et renvoie son chemin.
     */
    private function createAttachment(ExpenseReport $report): string
    {
        $em = $this->em();
        $owner = $em->find(User::class, $this->owner->getId());

        $directory = $this->uploadDirectory($owner);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        $path = $directory . '/existant-' . uniqid() . '.png';
        file_put_contents($path, 'justificatif existant');

        $attachment = new ExpenseAttachment();
        $attachment->setUser($owner);
        $attachment->setExpenseReport($em->find(ExpenseReport::class, $report->getId()));
        $attachment->setExpenseId(self::EXPENSE_ID);
        $attachment->setFileName(basename($path));
        $attachment->setFilePath($path);
        $em->persist($attachment);
        $em->flush();
        $em->clear();

        return $path;
    }

    private function attachmentPath(ExpenseReport $report): string
    {
        $em = $this->em();
        $em->clear();
        $attachment = $em->getRepository(ExpenseAttachment::class)->findOneBy(['expenseReport' => $report->getId(), 'expenseId' => self::EXPENSE_ID]);
        $this->assertNotNull($attachment);

        return $attachment->getFilePath();
    }

    /**
     * @return list<string>
     */
    private function uploadedFiles(User $user): array
    {
        $files = glob($this->uploadDirectory($user) . '/*') ?: [];
        sort($files);

        return $files;
    }

    private function uploadDirectory(User $user): string
    {
        return $this->getContainer()->getParameter('kernel.project_dir') . '/public/ftp/user/' . $user->getId() . '/expense-attachments';
    }
}
