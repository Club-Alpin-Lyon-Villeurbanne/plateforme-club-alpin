<?php

namespace App\Controller;

use App\Entity\EventParticipation;
use App\Entity\EventUnrecognizedPayer;
use App\Entity\Evt;
use App\Entity\User;
use App\Repository\EvtRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HelloAssoWebhookController extends AbstractController
{
    public function __construct(
        protected string $helloAssoServerIp,
        protected string $helloAssoSignatureKey,
        protected readonly LoggerInterface $logger,
        protected readonly EntityManagerInterface $entityManager,
        protected readonly EvtRepository $eventRepository,
        protected readonly UserRepository $userRepository,
    ) {
    }

    #[Route(path: '/webhook/notification', name: 'hello_asso_webhook_notification', methods: ['POST'])]
    public function notification(Request $request): Response
    {
        // vérifier que l'appel provient bien des serveurs Hello asso : https://dev.helloasso.com/docs/secure-webhook
        $requestContent = json_decode($request->getContent(), true);

        // vérification IP d'origine
        if ($this->helloAssoServerIp !== $request->getClientIp()) {
            $this->logger->error('HelloAsso Webhook - Invalid IP', [
                'ip' => $request->getClientIp(),
                'payload' => $requestContent,
            ]);

            return new Response('Invalid IP', Response::HTTP_BAD_REQUEST);
        }

        // vérification signature
        $requestHeader = $request->headers->all();
        if (!\in_array('x-ha-signature', array_keys($requestHeader), true)) {
            $this->logger->error('HelloAsso Webhook - Missing signature', [
                $requestContent,
            ]);

            return new Response('Missing signature', Response::HTTP_BAD_REQUEST);
        }

        $signature = $requestHeader['x-ha-signature'][0];
        $calculatedSignature = hash_hmac('sha256', $request->getContent(), $this->helloAssoSignatureKey);
        if (!hash_equals($signature, $calculatedSignature)) {
            $this->logger->error('HelloAsso Webhook - Signature mismatch', [
                'payload' => $requestContent,
            ]);

            return new Response('Signature mismatch', Response::HTTP_OK);
        }

        $requestData = $requestContent['data'] ?? [];
        if (!(
            \in_array('payer', array_keys($requestData), true)
            && \in_array('items', array_keys($requestData), true)
            && \in_array('eventType', array_keys($requestContent), true)
            && 'Payment' === $requestContent['eventType'] && \in_array('order', array_keys($requestData), true)
        )) {
            $this->logger->error('HelloAsso Webhook - Invalid payload', [
                'payload' => $requestContent,
            ]);

            return new Response('Invalid payload', Response::HTTP_OK);
        }

        $notificationType = $requestData['items'][0]['type'] ?? null;
        $status = $requestData['items'][0]['state'] ?? null;

        if ('Registration' !== $notificationType || 'Processed' !== $status) {
            $this->logger->error('HelloAsso Webhook - Notification type or status not handled', [
                'notificationType' => $notificationType,
                'state' => $status,
            ]);

            return new Response('Notification type not handled', Response::HTTP_OK);
        }

        // email du payeur pour trouver l'adhérent
        $payerEmail = $requestData['payer']['email'] ?? null;
        $user = $this->userRepository->findOneBy(['email' => $payerEmail]);

        // slug de la campagne pour trouver la sortie
        $eventSlug = null;
        if (\in_array('formSlug', array_keys($requestData['order']), true)) {
            $eventSlug = $requestData['order']['formSlug'];
        }
        $event = $this->eventRepository->findOneBy(['helloAssoFormSlug' => $eventSlug]);

        if (!$user instanceof User) {
            if (!$event instanceof Evt) {
                $this->logger->error('HelloAsso Webhook - Unknown form', [
                    'eventSlug' => $eventSlug,
                ]);

                return new Response('Unknown form', Response::HTTP_OK);
            }

            // enregistrer dans les payeurs non reconnus
            $payer = new EventUnrecognizedPayer();
            $payer
                ->setEvent($event)
                ->setEmail($payerEmail ?: '')
                ->setLastname($requestData['payer']['lastName'])
                ->setFirstname($requestData['payer']['firstName'])
                ->setHasPaid(true)
            ;
            $event->addUnrecognizedPayer($payer);
            $this->entityManager->persist($event);
            $this->entityManager->flush();

            $this->logger->error('HelloAsso Webhook - Unknown payer', [
                'payerEmail' => $payerEmail,
            ]);

            return new Response('Unknown payer', Response::HTTP_OK);
        }

        $participation = $event->getParticipation($user);
        if (!$participation instanceof EventParticipation) {
            $this->logger->error('HelloAsso Webhook - Participation not found for payer and event', [
                'payerEmail' => $payerEmail,
                'eventSlug' => $eventSlug,
            ]);

            return new Response('Participation not found', Response::HTTP_OK);
        }

        $participation->setHasPaid(true);
        $this->entityManager->persist($participation);
        $this->entityManager->flush();

        $this->logger->info('HelloAsso Webhook - Participation updated for payment', [
            'eventSlug' => $eventSlug,
            'payerEmail' => $payerEmail,
        ]);

        // on retourne une 200 même en cas d'erreur pour ne pas que HelloAsso renvoie plusieurs fois la notification
        return new Response('OK', Response::HTTP_OK);
    }
}
