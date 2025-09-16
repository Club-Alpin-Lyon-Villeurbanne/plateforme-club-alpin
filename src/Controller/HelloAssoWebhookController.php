<?php

namespace App\Controller;

use App\Entity\EventParticipation;
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
    )
    {
    }

    #[Route(path: '/webhook/notification', name: 'hello_asso_webhook_notification', methods: ['POST'])]
    public function notification(Request $request): Response
    {
        $requestContent = json_decode($request->getContent(), true);

        // vérifier que l'appel provient bien des serveurs Hello asso : https://dev.helloasso.com/docs/secure-webhook
//        if ($this->helloAssoServerIp !== $request->getClientIp()) {
//            $this->logger->error('HelloAsso Webhook - Invalid IP', [
//                'ip' => $request->getClientIp(),
//                'data' => $requestContent,
//            ]);
//
//            return new Response('Invalid IP', Response::HTTP_FORBIDDEN);
//        }

        $requestHeader = $request->headers->all();
        if (\in_array('x-ha-signature', array_keys($requestHeader), true)) {
            $signature = $requestHeader['x-ha-signature'][0];
            $calculatedSignature = hash_hmac('sha256', $request->getContent(), $this->helloAssoSignatureKey);
//            if (!hash_equals($signature, $calculatedSignature)) {
//                $this->logger->error('HelloAsso Webhook - Signature mismatch', [
//                    $requestContent,
//                ]);
//
//                return new Response('Signature mismatch', Response::HTTP_FORBIDDEN);
//            }

            $requestData = $requestContent['data'] ?? [];
            if (
                \in_array('payer', array_keys($requestData), true)
                && \in_array('order', array_keys($requestData), true)
                && \in_array('items', array_keys($requestData), true)
            ) {
                $notificationType = $requestData['items'][0]['type'] ?? null;
                $status = $requestData['items'][0]['state'] ?? null;

                if ('Registration' === $notificationType && 'Processed' === $status) {
                    $payerEmail = $requestData['payer']['email'] ?? null;
                    $eventSlug = $requestData['order']['formSlug'] ?? null;

                    $event = $this->eventRepository->findOneBy(['helloAssoFormSlug' => $eventSlug]);
                    $user = $this->userRepository->findOneBy(['email' => $payerEmail]);

                    if ($event instanceof Evt && $user instanceof User) {
                        $participation = $event->getParticipation($user);
                        if ($participation instanceof EventParticipation) {
                            $participation->setHasPaid(true);
                            $this->entityManager->persist($participation);
                            $this->entityManager->flush();

                            $this->logger->info('HelloAsso Webhook - Participation updated for payment', [
                                'eventSlug' => $eventSlug,
                                'payerEmail' => $payerEmail,
                            ]);

                            return new Response('OK', Response::HTTP_OK);
                        } else {
                            $this->logger->error('HelloAsso Webhook - Participation not found for payer', [
                                'eventSlug' => $eventSlug,
                                'payerEmail' => $payerEmail,
                            ]);

                            return new Response('Participation not found', Response::HTTP_OK);
                        }
                    } else {
                        $this->logger->error('HelloAsso Webhook - Unknown user or event', [
                            'eventSlug' => $eventSlug,
                            'payerEmail' => $payerEmail,
                        ]);

                        return new Response('Unknown user or event', Response::HTTP_OK);
                    }
                } else {
                    return new Response('Notification type not handled', Response::HTTP_OK);
                }
            } else {
                $this->logger->error('HelloAsso Webhook - Invalid payload', [
                    $requestContent,
                ]);

                return new Response('Invalid payload', Response::HTTP_OK);
            }
        } else {
            $this->logger->error('HelloAsso Webhook - Missing signature', [
                $requestContent,
            ]);

            return new Response('Missing signature', Response::HTTP_FORBIDDEN);
        }

        return new Response('KO', Response::HTTP_OK);
    }
}
