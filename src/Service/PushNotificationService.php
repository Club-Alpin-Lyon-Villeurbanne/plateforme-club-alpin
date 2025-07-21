<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\Evt;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class PushNotificationService
{
    private Messaging $messaging;

    function __construct() {
        $factory = new Factory();
        $this->messaging = $factory->createMessaging();
    }

    public function sendNotificationToTopic(
        string $topic,
        string $title,
        string $body,
        ?string $imageUrl = null,
        ?array $data = []
    ) {
        
        $message = CloudMessage::new()
            ->withNotification(Notification::create($_ENV['SITENAME'] . " - " . $title, $body, $imageUrl))
            ->withData($data)
            ->toTopic($_ENV['APP_INSTANCE'] . '_' . $topic);
        return $this->messaging->send($message);
    }

    public function notifyArticle(Article $article) {
        // TODO: URL could maybe not be hard coded
        $thumbnailUrl = $_ENV['BACKEND_URL'] . '/media/cache/wide_thumbnail/uploads/files/' . $article->getMediaUpload()?->getFilename();
        $this->sendNotificationToTopic('article_all', 'Nouvel article', $article->getTitre(), $thumbnailUrl, [
            'url' => $_ENV['APP_DEEPLINK_SCHEME'] . '://article/' . $article->getId(),
        ]);
    }

    public function notifyEvent(Evt $event) {
        $this->sendNotificationToTopic('event_' . $event->getCommission()->getCode(), 'Nouvelle sortie', $event->getCommission()->getTitle() . ': ' . $event->getTitre(), null, [
            'url' => $_ENV['APP_DEEPLINK_SCHEME'] . '://event/' . $event->getId(),
        ]);
    }
}
