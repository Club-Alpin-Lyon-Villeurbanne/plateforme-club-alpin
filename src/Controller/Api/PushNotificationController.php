<?php

namespace App\Controller\Api;

use App\Repository\ArticleRepository;
use App\Repository\EvtRepository;
use App\Service\PushNotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;

class PushNotificationController extends AbstractController
{
    public function __construct(
        private PushNotificationService $push,
        private ArticleRepository $articleRepository,
        private EvtRepository $eventRepository,
    ) {
    }

    #[Route(name: 'notifications_article', path: '/api/notifications/article', methods: ['GET'])]
    public function notifyArticle(#[MapQueryParameter] int $id)
    {
        $article = $this->articleRepository->find($id);
        if (!$article) {
            return new Response('Article not found', 404);
        }
        $this->push->notifyArticle($article);

        return new Response('OK', 200);
    }

    #[Route(name: 'notifications_event', path: '/api/notifications/event', methods: ['GET'])]
    public function notifyEvent(#[MapQueryParameter] int $id)
    {
        $event = $this->eventRepository->find($id);
        if (!$event) {
            return new Response('Event not found', 404);
        }
        $this->push->notifyEvent($event);

        return new Response('OK', 200);
    }
}
