<?php

namespace App\Service;

use App\Entity\ContentInline;
use App\Repository\ContentInlineRepository;

class CmsContentService
{
    public function __construct(protected ContentInlineRepository $contentInlineRepository)
    {
    }

    public function getMeta(string $code): string
    {
        $inlineContent = $this->contentInlineRepository->findOneBy(['code' => $code]);
        if (!$inlineContent instanceof ContentInline) {
            return '';
        }

        return $inlineContent->getContenu() ?: '';
    }
}
