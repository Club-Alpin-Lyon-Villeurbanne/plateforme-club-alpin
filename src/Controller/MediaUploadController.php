<?php

namespace App\Controller;

use App\Entity\MediaUpload;
use App\Repository\MediaUploadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Vich\UploaderBundle\Handler\UploadHandler;

#[AsController]
class MediaUploadController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MediaUploadRepository $mediaUploadRepository,
        private Security $security,
        private UploadHandler $uploadHandler,
        private CacheManager $imagineCacheManager
    ) {
    }

    #[Route('/upload-image', name: 'media_upload_image', methods: ['POST'])]
    public function __invoke(Request $request, ValidatorInterface $validator): Response
    {
        return $this->upload($request, $validator, new File([
            'maxSize' => '5M',
            'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        ]));
    }

    #[Route('/upload-file', name: 'media_upload_file', methods: ['POST'])]
    public function uploadFile(Request $request, ValidatorInterface $validator): Response
    {
        return $this->upload($request, $validator, new File([
            'maxSize' => '5M',
            'extensions' => ['pdf', 'gpx', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'],
        ]));
    }

    public function upload(Request $request, ValidatorInterface $validator, File $fileConstraints): Response|JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['uploaded' => 0, 'error' => ['message' => 'User must be logged in']], Response::HTTP_UNAUTHORIZED);
        }

        $file = $request->files->get('file');
        if (!$file) {
            return new JsonResponse(['uploaded' => 0, 'error' => ['message' => 'File is required']], Response::HTTP_BAD_REQUEST);
        }

        $errors = $validator->validate($file, $fileConstraints);

        if ($errors->count() > 0) {
            throw new BadRequestHttpException((string) $errors);
        }

        $mediaUpload = new MediaUpload();
        $mediaUpload->setUploadedBy($user);
        $mediaUpload->setFile($file);

        $this->entityManager->persist($mediaUpload);
        $this->entityManager->flush();

        $this->uploadHandler->upload($mediaUpload, 'file');

        $this->entityManager->flush();

        $imagePath = $request->getSchemeAndHttpHost() . '/ftp/uploads/files/' . $mediaUpload->getFilename();

        return $this->json([
            'id' => $mediaUpload->getId(),
            'filename' => $mediaUpload->getFilename(),
            'url' => $imagePath,
            'uploaded' => 1,
            'createdAt' => $mediaUpload->getCreatedAt()->format('c'),
        ], Response::HTTP_CREATED);
    }
}
