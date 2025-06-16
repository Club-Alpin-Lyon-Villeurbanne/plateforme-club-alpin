<?php

namespace App\Twig;

use App\Entity\MediaUpload;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MediaExtension extends AbstractExtension
{
    private CacheManager $imagineCacheManager;

    public function __construct(CacheManager $imagineCacheManager)
    {
        $this->imagineCacheManager = $imagineCacheManager;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('media_thumbnail', [$this, 'getThumbnail']),
            new TwigFunction('media_thumbnails', [$this, 'getThumbnails']),
        ];
    }

    public function getLegacyThumbnail(?array $media, string $filter = 'min_thumbnail'): ?string
    {
        if (!$media || !$media['filename']) {
            return null;
        }

        $relativeImagePath = 'uploads/files/' . $media['filename'];

        try {
            return $this->imagineCacheManager->getBrowserPath($relativeImagePath, $filter);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getThumbnail(?MediaUpload $media, string $filter = 'min_thumbnail'): ?string
    {
        if (!$media || !$media->getFilename()) {
            return null;
        }

        $relativeImagePath = 'uploads/files/' . $media->getFilename();

        try {
            return $this->imagineCacheManager->getBrowserPath($relativeImagePath, $filter);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getThumbnails(?MediaUpload $media): array
    {
        if (!$media || !$media->getFilename()) {
            return [];
        }

        $relativeImagePath = 'uploads/files/' . $media->getFilename();

        try {
            return [
                'wide' => $this->imagineCacheManager->getBrowserPath($relativeImagePath, 'wide_thumbnail'),
                'min' => $this->imagineCacheManager->getBrowserPath($relativeImagePath, 'min_thumbnail'),
                // Ajoutez d'autres formats selon vos besoins
            ];
        } catch (\Exception $e) {
            return [];
        }
    }
}
