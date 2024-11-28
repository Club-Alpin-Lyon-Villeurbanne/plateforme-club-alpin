<?php

namespace App\Utils;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\UrlHelper;
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
