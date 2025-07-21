<?php

namespace App\Messenger\MessageHandler;

use App\Entity\AlertType;
use App\Entity\Article;
use App\Messenger\Message\ArticlePublie;
use App\Messenger\Message\UserNotification;
use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
use App\Service\PushNotificationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class ArticlePublieHandler
{
    public function __construct(
        private readonly ArticleRepository $articleRepository,
        private readonly UserRepository $userRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly PushNotificationService $pushNotificationService,
    ) {
    }

    public function __invoke(ArticlePublie $message): void
    {
        $article = $this->articleRepository->find($message->id);

        if (!$article) {
            return;
        }

        if ($article->getCommission()) {
            $commissionCode = $article->getCommission()->getCode();
        } elseif ($article->getEvt()) {
            $commissionCode = $article->getEvt()->getCommission()->getCode();
        } else {
            $commissionCode = ArticlePublie::ACTU_CLUB_RUBRIQUE;
        }

        foreach ($this->userRepository->findUsersIdWithAlert($commissionCode, AlertType::Article) as $userId) {
            $this->notifyUser($article, $userId);
        }

        $this->pushNotificationService->notifyArticle($article);
    }

    private function notifyUser(Article $article, int $userId)
    {
        $this->messageBus->dispatch(new UserNotification(AlertType::Article, $article->getId(), $userId));
    }
}
