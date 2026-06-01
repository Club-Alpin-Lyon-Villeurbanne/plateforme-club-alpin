<?php

namespace App\Controller;

use App\Entity\EventParticipation;
use App\Entity\EventUnrecognizedPayer;
use App\Entity\Evt;
use App\Entity\User;
use App\Repository\EvtRepository;
use App\Repository\UserRepository;
use App\Service\LoxyaReservationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HelloAssoWebhookController extends AbstractController
{
    public function __construct(
        protected string $helloAssoServerIp,
        protected string $helloAssoSignatureKey,
        protected readonly LoggerInterface $logger,
        protected readonly EntityManagerInterface $entityManager,
        protected readonly EvtRepository $eventRepository,
        protected readonly UserRepository $userRepository,
        protected readonly LoxyaReservationService $loxyaReservationService,
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

        // vérification signature (optionnelle) : HelloAsso ne signe pas les notifications des comptes
        // non-partenaires. On ne vérifie que si une clé est configurée ET qu'une signature est fournie
        // (défense en profondeur) ; sinon l'authenticité repose sur l'allowlist d'IP ci-dessus.
        $signature = $request->headers->get('x-ha-signature');
        if ('' !== $this->helloAssoSignatureKey && null !== $signature) {
            $calculatedSignature = hash_hmac('sha256', $request->getContent(), $this->helloAssoSignatureKey);
            if (!hash_equals($signature, $calculatedSignature)) {
                $this->logger->error('HelloAsso Webhook - Signature mismatch', [
                    'payload' => $requestContent,
                ]);

                return new Response('Signature mismatch', Response::HTTP_OK);
            }
        }

        // Paiement de location de matériel : identifié par metadata.reservation_id (injecté dans le
        // checkout-intent par PaymentController::checkout). HelloAsso n'autorisant qu'une URL de notif
        // par organisation, ces paiements arrivent sur la même URL que les inscriptions événements.
        if (isset($requestContent['metadata']['reservation_id'])) {
            return $this->handleMaterialReservationPayment($requestContent);
        }

        $requestData = $requestContent['data'] ?? [];
        if (!(
            \in_array('payer', array_keys($requestData), true)
            && \in_array('items', array_keys($requestData), true)
            && \in_array('eventType', array_keys($requestContent), true)
            && 'Payment' === $requestContent['eventType'] && \in_array('order', array_keys($requestData), true)
        )) {
            $this->logger->info('HelloAsso Webhook - Notification type not handled', [
                'payload' => $requestContent,
            ]);

            return new Response('Notification type not handled', Response::HTTP_OK);
        }

        $notificationType = $requestData['items'][0]['type'] ?? null;
        $status = $requestData['items'][0]['state'] ?? null;

        if ('Registration' !== $notificationType || 'Processed' !== $status) {
            $this->logger->info('HelloAsso Webhook - Payment notification type or status not handled', [
                'notificationType' => $notificationType,
                'state' => $status,
            ]);

            return new Response('Payment notification type not handled', Response::HTTP_OK);
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

        if (!$event instanceof Evt) {
            $this->logger->error('HelloAsso Webhook - Unknown form', [
                'eventSlug' => $eventSlug,
            ]);

            return new Response('Unknown form', Response::HTTP_OK);
        }

        if (!$user instanceof User) {
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

    /**
     * Marque la réservation Loxya comme payée à partir d'une notification de paiement matériel.
     * Renvoie 503 si Loxya échoue, pour que HelloAsso retente (le PUT Loxya est idempotent).
     */
    private function handleMaterialReservationPayment(array $payload): Response
    {
        $eventType = $payload['eventType'] ?? null;
        $state = $payload['data']['state'] ?? null;

        if ('Payment' !== $eventType || 'Authorized' !== $state) {
            $this->logger->info('HelloAsso Webhook - Loxya : notification ignorée', [
                'eventType' => $eventType,
                'state' => $state,
            ]);

            return new Response('OK', Response::HTTP_OK);
        }

        $reservationId = filter_var($payload['metadata']['reservation_id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $helloAssoPaymentId = $payload['data']['id'] ?? null;

        if (false === $reservationId
            || (!\is_string($helloAssoPaymentId) && !\is_int($helloAssoPaymentId))
            || '' === (string) $helloAssoPaymentId
        ) {
            $this->logger->error('HelloAsso Webhook - Loxya : reservation_id ou payment id invalide');

            return new Response('OK', Response::HTTP_OK);
        }

        try {
            $this->loxyaReservationService->markReservationAsPaid($reservationId, (string) $helloAssoPaymentId);
        } catch (\Exception $e) {
            $this->logger->error('HelloAsso Webhook - Loxya update failed, HelloAsso will retry', [
                'reservationId' => $reservationId,
                'error' => $e->getMessage(),
            ]);

            return new Response('Loxya update failed', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return new Response('OK', Response::HTTP_OK);
    }
}
