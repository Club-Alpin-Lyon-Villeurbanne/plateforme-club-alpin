<?php

namespace App\Tests\State;

use App\Entity\ExpenseAttachment;
use App\Entity\ExpenseReport;
use App\Entity\User;
use App\State\ExpenseReportCloneProcessor;
use App\Tests\WebTestCase;
use App\Utils\Enums\ExpenseReportStatusEnum;
use App\Utils\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ExpenseReportCloneProcessorTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private Security $security;
    private string $kernelProjectDir;
    private FileUploader $fileUploader;
    private ExpenseReportCloneProcessor $processor;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->getContainer()->get(EntityManagerInterface::class);
        $this->security = $this->getContainer()->get(Security::class);
        $this->kernelProjectDir = $this->getContainer()->getParameter('kernel.project_dir');
        $this->fileUploader = $this->getContainer()->get(FileUploader::class);

        $this->processor = new ExpenseReportCloneProcessor(
            $this->entityManager,
            $this->security,
            $this->kernelProjectDir,
            $this->fileUploader
        );

        $this->user = $this->signup();
        $this->signin($this->user);
    }

    public function testCloneExpenseReport(): void
    {
        $event = $this->createEvent($this->user);

        $originalReport = new ExpenseReport();
        $originalReport->setStatus(ExpenseReportStatusEnum::SUBMITTED);
        $originalReport->setRefundRequired(true);
        $originalReport->setUser($this->user);
        $originalReport->setEvent($event);
        $originalReport->setDetails('{"someKey": "someValue"}');

        $this->entityManager->persist($originalReport);
        $this->entityManager->flush();

        $this->createMockAttachment($originalReport);

        $operation = new \ApiPlatform\Metadata\Post(name: 'clone');
        $uriVariables = ['id' => $originalReport->getId()];

        $clonedReport = $this->processor->process(null, $operation, $uriVariables);

        $this->assertInstanceOf(ExpenseReport::class, $clonedReport);
        $this->assertNotSame($originalReport->getId(), $clonedReport->getId());
        $this->assertEquals(ExpenseReportStatusEnum::DRAFT, $clonedReport->getStatus());
        $this->assertEquals($originalReport->isRefundRequired(), $clonedReport->isRefundRequired());
        $this->assertSame($this->user, $clonedReport->getUser());
        $this->assertSame($event, $clonedReport->getEvent());
        $this->assertEquals($originalReport->getDetails(), $clonedReport->getDetails());

        $this->assertCount(1, $clonedReport->getAttachments());

        $originalAttachment = $originalReport->getAttachments()->first();
        $clonedAttachment = $clonedReport->getAttachments()->first();

        $this->assertNotSame($originalAttachment->getId(), $clonedAttachment->getId());
        $this->assertEquals($originalAttachment->getExpenseId(), $clonedAttachment->getExpenseId());
        $this->assertNotEquals($originalAttachment->getFilePath(), $clonedAttachment->getFilePath());
        $this->assertStringContainsString('clone_', $clonedAttachment->getFileName());
    }

    public function testCloneExpenseReportWithExistingReport(): void
    {
        $event = $this->createEvent($this->user);

        $existingReport = new ExpenseReport();
        $existingReport->setStatus(ExpenseReportStatusEnum::DRAFT);
        $existingReport->setRefundRequired(false);
        $existingReport->setUser($this->user);
        $existingReport->setEvent($event);
        $existingReport->setDetails('{"existingKey": "existingValue"}');

        $this->entityManager->persist($existingReport);
        $this->entityManager->flush();

        $existingReportId = $existingReport->getId();

        $originalReport = new ExpenseReport();
        $originalReport->setStatus(ExpenseReportStatusEnum::SUBMITTED);
        $originalReport->setRefundRequired(true);
        $originalReport->setUser($this->user);
        $originalReport->setEvent($event);
        $originalReport->setDetails('{"newKey": "newValue"}');

        $this->entityManager->persist($originalReport);
        $this->entityManager->flush();

        $this->createMockAttachment($originalReport);

        $operation = new \ApiPlatform\Metadata\Post(name: 'clone');
        $uriVariables = ['id' => $originalReport->getId()];

        $clonedReport = $this->processor->process(null, $operation, $uriVariables);

        $this->assertInstanceOf(ExpenseReport::class, $clonedReport);
        $this->assertNotSame($originalReport->getId(), $clonedReport->getId());
        $this->assertEquals(ExpenseReportStatusEnum::DRAFT, $clonedReport->getStatus());
        $this->assertEquals($originalReport->isRefundRequired(), $clonedReport->isRefundRequired());
        $this->assertSame($this->user, $clonedReport->getUser());
        $this->assertSame($event, $clonedReport->getEvent());
        $this->assertEquals($originalReport->getDetails(), $clonedReport->getDetails());

        $this->assertCount(1, $clonedReport->getAttachments());

        $deletedReport = $this->entityManager->find(ExpenseReport::class, $existingReportId);
        $this->assertNull($deletedReport, 'The existing report should have been deleted');

        $reportsCount = $this->entityManager->getRepository(ExpenseReport::class)
            ->count(['user' => $this->user, 'event' => $event]);
        $this->assertEquals(2, $reportsCount, 'Should have 2 reports: original and cloned');
    }

    private function createMockAttachment(ExpenseReport $report): ExpenseAttachment
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'test content');

        $uploadDir = $this->kernelProjectDir . '/public/ftp/user/' . $this->user->getId() . '/expense-attachments';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $testFileName = 'test_file.txt';
        $testFilePath = $uploadDir . '/' . $testFileName;
        copy($tempFile, $testFilePath);

        $attachment = new ExpenseAttachment();
        $attachment->setExpenseId('TEST123');
        $attachment->setFileName($testFileName);
        $attachment->setFilePath($testFilePath);
        $attachment->setFileUrl('http://example.com/test_file.txt');
        $attachment->setUser($this->user);
        $attachment->setExpenseReport($report);

        $report->addAttachment($attachment);

        $this->entityManager->persist($attachment);
        $this->entityManager->flush();

        return $attachment;
    }

    protected function tearDown(): void
    {
        $uploadDir = $this->kernelProjectDir . '/public/ftp/user/' . $this->user->getId();
        if (file_exists($uploadDir)) {
            $this->removeDirectory($uploadDir);
        }

        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        if (!file_exists($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }
}
