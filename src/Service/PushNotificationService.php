<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\Evt;
use App\Entity\User;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Bundle\SecurityBundle\Security;

class PushNotificationService
{
    private Messaging $messaging;

    public function __construct(private Security $security, private CacheManager $cache)
    {
        $factory = new Factory();
        $this->messaging = $factory->createMessaging();
    }

    private function sendNotificationToTopic(
        string $topic,
        string $title,
        string $body,
        ?string $imageUrl = null,
        ?array $data = []
    ) {
        $message = CloudMessage::new()
            ->withNotification(Notification::create($_ENV['SITENAME'] . ' - ' . $title, $body, $imageUrl))
            ->withData($data)
            ->toTopic($_ENV['APP_INSTANCE'] . '_' . $topic);

        return $this->messaging->send($message);
    }

    private function getUserId(): ?string
    {
        /** @var User? $user */
        $user = $this->security->getUser() instanceof User ? $this->security->getUser() : null;
        if ($user) {
            return (string) $user->getId();
        }

        return null;
    }

    public function notifyArticle(Article $article)
    {
        $userId = $this->getUserId();
        if ($userId) {
            $thumbnail = $article->getMediaUpload();
            $thumbnailUrl = $thumbnail ? $this->cache->generateUrl('uploads/files/' . $thumbnail->getFilename(), 'wide_thumbnail') : null;
            $this->sendNotificationToTopic('article_all', 'Nouvel article', $article->getTitre(), $thumbnailUrl, [
                'url' => $_ENV['APP_DEEPLINK_SCHEME'] . '://article/' . $article->getId(),
                'instanceId' => $_ENV['APP_INSTANCE'],
                'userId' => $userId,
            ]);
        }
    }

    public function notifyEvent(Evt $event)
    {
        $this->sendNotificationToTopic('event_' . $event->getCommission()->getCode(), 'Nouvelle sortie', $event->getCommission()->getTitle() . ': ' . $event->getTitre(), null, [
            'url' => $_ENV['APP_DEEPLINK_SCHEME'] . '://event/' . $event->getId(),
        ]);
    }
}
