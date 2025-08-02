<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\Evt;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;

class PushNotificationService
{
    private Messaging $messaging;

    public function __construct(private CacheManager $cache, private string $appInstanceId, private string $appDeeplinkScheme, private string $sitename)
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
            ->withNotification(Notification::create($this->sitename . ' - ' . $title, $body, $imageUrl))
            ->withData($data)
            ->toTopic($this->appInstanceId . '_' . $topic);

        return $this->messaging->send($message);
    }

    public function notifyArticle(Article $article)
    {
        $thumbnail = $article->getMediaUpload();
        $thumbnailUrl = $thumbnail ? $this->cache->generateUrl('uploads/files/' . $thumbnail->getFilename(), 'wide_thumbnail') : null;
        $this->sendNotificationToTopic('article_all', 'Nouvel article', $article->getTitre(), $thumbnailUrl, [
            'url' => $this->appDeeplinkScheme . '://article/' . $article->getId(),
            'instanceId' => $this->appInstanceId,
        ]);
    }

    public function notifyEvent(Evt $event)
    {
        $this->sendNotificationToTopic('event_' . $event->getCommission()->getCode(), 'Nouvelle sortie', $event->getCommission()->getTitle() . ': ' . $event->getTitre(), null, [
            'url' => $this->appDeeplinkScheme . '://event/' . $event->getId(),
            'instanceId' => $this->appInstanceId,
        ]);
    }
}
