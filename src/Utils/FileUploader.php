<?php

namespace App\Utils;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
    private const UPLOAD_PATH = 'ftp/user';

    public function __construct(
        private Security $security,
        private UrlHelper $urlHelper,
        private SluggerInterface $slugger,
        private string $publicDir,
    ) {
    }

    public function upload(UploadedFile $file, string $subDirectory): File
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        return $file->move($this->getUserUploadPath($this->security->getUser(), $subDirectory), $newFilename);
    }

    /**
     * Duplicate an existing file.
     *
     * @throws NotFoundHttpException If the original file doesn't exist
     */
    public function duplicateFile(string $originalFilePath, string $subDirectory): File
    {
        if (!file_exists($originalFilePath)) {
            throw new NotFoundHttpException(sprintf('File not found: %s', $originalFilePath));
        }

        $user = $this->security->getUser();
        $extension = pathinfo($originalFilePath, \PATHINFO_EXTENSION);

        $newFilename = 'clone_' . uniqid() . '.' . $extension;

        $targetDir = $this->getUserUploadPath($user, $subDirectory);
        $targetPath = $targetDir . '/' . $newFilename;

        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        copy($originalFilePath, $targetPath);

        return new File($targetPath);
    }

    private function getUserUploadPath(User $user, string $subDirectory): string
    {
        return $this->publicDir . '/' . $this->getRelativePath($user, $subDirectory);
    }

    private function getRelativePath(User $user, string $subDirectory, ?string $filename = null): string
    {
        $subDirectory = trim($subDirectory, '/');
        $path = self::UPLOAD_PATH . '/' . $user->getId() . '/' . $subDirectory;

        return $filename ? $path . '/' . $filename : $path;
    }

    public function getUserUploadUrl(User $user, string $filename, string $subDirectory): string
    {
        return $this->urlHelper->getAbsoluteUrl('/' . $this->getRelativePath($user, $subDirectory, $filename));
    }
}
