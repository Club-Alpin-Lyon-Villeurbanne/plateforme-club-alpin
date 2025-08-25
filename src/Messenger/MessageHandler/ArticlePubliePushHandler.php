<?php

namespace App\Messenger\MessageHandler;

use App\Messenger\Message\ArticlePublie;
use App\Repository\ArticleRepository;
use App\Service\PushNotificationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ArticlePubliePushHandler
{
    public function __construct(
        private readonly ArticleRepository $articleRepository,
        private readonly PushNotificationService $pushNotificationService,
    ) {
    }

    public function __invoke(ArticlePublie $message): void
    {
        $article = $this->articleRepository->find($message->id);

        if (!$article) {
            return;
        }

        $this->pushNotificationService->notifyArticle($article);
    }
}
