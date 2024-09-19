<?php

namespace App\Utils;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
    public function __construct(
        private Security $security,
        private UrlHelper $urlHelper,
        private SluggerInterface $slugger,
    ) {}

    public function upload(UploadedFile $file, string $subDirectory) : File {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
 
        return $file->move($this->getUploadPath($subDirectory), $newFilename);
    }

    public function getUploadPath(string $subDirectory): string
    {
        $user = $this->security->getUser();
        if (!$user) { 
            throw new \RuntimeException('Unable to upload a file as anonymous user');
        }

        $subDirectory = trim($subDirectory, '/');

        return __DIR__ . '/../../public/ftp/user/' . $user->getId() . '/' . $subDirectory;
    }

    public function getUploadUrl(string $filename, string $subDirectory): string
    {
        $user = $this->security->getUser();
        if (!$user) { 
            throw new \RuntimeException('Unable to upload a file as anonymous user');
        }

        $subDirectory = trim($subDirectory, '/');

        return '/ftp/user/' . $user->getId() . '/' . $subDirectory . '/' . $filename;
    }
}
