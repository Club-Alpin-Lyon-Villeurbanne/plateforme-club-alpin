<?php

namespace App\Tests\Utils;

use App\Tests\WebTestCase;
use App\Utils\FileUploader;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FileUploaderTest extends WebTestCase
{
    private FileUploader $fileUploader;
    private string $publicDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fileUploader = static::getContainer()->get(FileUploader::class);
        $this->publicDir = static::getContainer()->getParameter('kernel.project_dir') . '/public';
    }

    public function testUpload(): void
    {
        $user = $this->signup();
        $this->signin($user);

        $tempFile = tempnam(sys_get_temp_dir(), 'test_upload_');
        file_put_contents($tempFile, 'test content');

        $uploadedFile = new UploadedFile(
            $tempFile,
            'test_file.txt',
            'text/plain',
            null,
            true
        );

        $uploadedFileObj = $this->fileUploader->upload($uploadedFile, 'test-directory');

        $this->assertInstanceOf(File::class, $uploadedFileObj);
        $this->assertFileExists($uploadedFileObj->getPathname());

        $expectedPathPattern = "#^{$this->publicDir}/ftp/user/{$user->getId()}/test-directory/#";
        $this->assertMatchesRegularExpression($expectedPathPattern, $uploadedFileObj->getPathname());

        if (file_exists($uploadedFileObj->getPathname())) {
            unlink($uploadedFileObj->getPathname());
        }
    }

    public function testDuplicateFile(): void
    {
        $user = $this->signup();
        $this->signin($user);

        $sourceDir = $this->publicDir . '/ftp/user/' . $user->getId() . '/source-dir';
        if (!file_exists($sourceDir)) {
            mkdir($sourceDir, 0755, true);
        }

        $sourceFile = $sourceDir . '/original-file.txt';
        file_put_contents($sourceFile, 'original content');

        $duplicatedFile = $this->fileUploader->duplicateFile($sourceFile, 'target-dir');

        $this->assertInstanceOf(File::class, $duplicatedFile);
        $this->assertFileExists($duplicatedFile->getPathname());

        $expectedPathPattern = "#^{$this->publicDir}/ftp/user/{$user->getId()}/target-dir/clone_#";
        $this->assertMatchesRegularExpression($expectedPathPattern, $duplicatedFile->getPathname());

        $this->assertEquals('original content', file_get_contents($duplicatedFile->getPathname()));

        if (file_exists($sourceFile)) {
            unlink($sourceFile);
        }
        if (file_exists($duplicatedFile->getPathname())) {
            unlink($duplicatedFile->getPathname());
        }
    }

    public function testDuplicateNonExistentFile(): void
    {
        $user = $this->signup();
        $this->signin($user);

        $nonExistentFile = $this->publicDir . '/non-existent-file.txt';

        $this->expectException(NotFoundHttpException::class);
        $this->fileUploader->duplicateFile($nonExistentFile, 'target-dir');
    }

    public function testGetUserUploadUrl(): void
    {
        $user = $this->signup();

        $url = $this->fileUploader->getUserUploadUrl($user, 'test-file.txt', 'test-directory');

        $expectedPattern = "#/ftp/user/{$user->getId()}/test-directory/test-file.txt#";
        $this->assertMatchesRegularExpression($expectedPattern, $url);
    }
}
