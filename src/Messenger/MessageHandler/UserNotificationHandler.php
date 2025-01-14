<?php

namespace App\Messenger\MessageHandler;

use App\Entity\AlertType;
use App\Entity\UserNotification as UserNotificationEntity;
use App\Mailer\Mailer;
use App\Messenger\Message\UserNotification;
use App\Repository\ArticleRepository;
use App\Repository\EvtRepository;
use App\Repository\UserNotificationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UserNotificationHandler
{
    public function __construct(
        private readonly ArticleRepository $articleRepository,
        private readonly EvtRepository $evtRepository,
        private readonly UserRepository $userRepository,
        private readonly UserNotificationRepository $userNotificationRepository,
        private readonly EntityManagerInterface $em,
        private readonly Mailer $mailer,
        private readonly string $defaultAlertArticlePrefix,
        private readonly string $defaultAlertSortiePrefix,
    ) {
    }

    public function __invoke(UserNotification $message): void
    {
        $entity = match ($message->alertType) {
            AlertType::Article => $this->articleRepository->find($message->id),
            AlertType::Sortie => $this->evtRepository->find($message->id),
            default => null,
        };

        if (null === $entity) {
            return;
        }

        $user = $this->userRepository->find($message->userId);

        if (null === $user) {
            return;
        }

        if ($this->userNotificationRepository->hasNotificationBeSent($user, $message->alertType, $message->id)) {
            return;
        }

        $notification = new UserNotificationEntity($user, $message->alertType, $message->id);
        $this->em->persist($notification);
        $this->em->flush();

        $template = match ($message->alertType) {
            AlertType::Article => 'transactional/notification-new-article',
            AlertType::Sortie => 'transactional/notification-new-sortie',
        };

        $prefix = match ($message->alertType) {
            AlertType::Article => $user->getAlertArticlePrefix() ?? $this->defaultAlertArticlePrefix,
            AlertType::Sortie => $user->getAlertSortiePrefix() ?? $this->defaultAlertSortiePrefix,
        };

        $this->mailer->send($user, $template, ['entity' => $entity, 'prefix' => $prefix]);
    }
}
