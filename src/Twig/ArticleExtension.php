<?php

namespace App\Twig;

use App\Entity\Article;
use App\Legacy\LegacyContainer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ArticleExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('article_image', [$this, 'getArticleImage']),
        ];
    }

    public function getArticleImage(Article $article): string
    {
        $img = '';
        if (!empty($article->getMediaUpload())) {
            $img = LegacyContainer::get('legacy_twig')->getExtension('App\Twig\MediaExtension')->getLegacyThumbnail(['filename' => $article->getMediaUpload()->getFilename()], 'wide_thumbnail');
        }

        return $img;
    }
}
