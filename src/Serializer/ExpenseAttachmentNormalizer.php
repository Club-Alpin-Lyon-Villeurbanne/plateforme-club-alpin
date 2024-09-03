<?php

namespace App\Serializer;

use App\Entity\ExpenseAttachment;
use App\Utils\FileUploader;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ExpenseAttachmentNormalizer implements NormalizerInterface
{
    private const ALREADY_CALLED = 'BOOK_ATTRIBUTE_NORMALIZER_ALREADY_CALLED';

    public function __construct(
        private Security $security,
        private FileUploader $fileUploader,
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly NormalizerInterface $normalizer,
    ) {
    }

    public function normalize($object, $format = null, array $context = [])
    {
        $context[self::ALREADY_CALLED] = true;

        // update the filePath with the url
        $object->setFileUrl($this->fileUploader->getUploadUrl($object->getFilename(), 'expense-attachments'));

        return $this->normalizer->normalize($object, $format, $context);
    }

    public function supportsNormalization($data, $format = null, array $context = [])
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof ExpenseAttachment;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            'object' => null,
            '*' => false,
            ExpenseAttachment::class => true,
        ];
    }
}
