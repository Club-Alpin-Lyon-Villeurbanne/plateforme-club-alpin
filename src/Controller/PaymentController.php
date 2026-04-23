<?php

namespace App\Controller;

use App\Service\HelloAssoClient;
use App\Service\LoxyaReservationService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaymentController extends AbstractController
{
    public function __construct(
        private readonly string $helloAssoServerIp,
        private readonly string $helloAssoSignatureKey,
        private readonly string $helloAssoOrganizationSlug,
        private readonly string $loxyaJwt,
        private readonly string $loxyaLinkSignatureKey,
        private readonly HelloAssoClient $helloAssoClient,
        private readonly LoxyaReservationService $loxyaReservationService,
        private readonly LoggerInterface $logger,
    ) {
    }

    private function isEnabled(): bool
    {
        return '' !== $this->loxyaJwt && '' !== $this->loxyaLinkSignatureKey;
    }

    #[Route(path: '/paiement', name: 'payment_checkout', methods: ['GET'])]
    public function checkout(Request $request): Response
    {
        if (!$this->isEnabled()) {
            throw $this->createNotFoundException();
        }

        $reservationId = $request->query->getInt('reservation_id');
        $amount = $request->query->getInt('amount');
        $signature = $request->query->get('signature', '');

        if ($reservationId <= 0 || $amount <= 0 || $amount > 100000) {
            return new Response('Paramètres reservation_id et amount obligatoires (entiers positifs, max 1000€).', Response::HTTP_BAD_REQUEST);
        }

        $expectedSignature = hash_hmac('sha256', $reservationId . '|' . $amount, $this->loxyaLinkSignatureKey);
        if (!\is_string($signature) || !hash_equals($expectedSignature, $signature)) {
            $this->logger->error('Payment checkout - Invalid signature', [
                'reservationId' => $reservationId,
                'amount' => $amount,
            ]);

            return new Response('Signature invalide.', Response::HTTP_FORBIDDEN);
        }

        try {
            $result = $this->helloAssoClient->createCheckoutIntent($this->helloAssoOrganizationSlug, [
                'totalAmount' => $amount,
                'initialAmount' => $amount,
                'itemName' => sprintf('Location de matériel - Réservation n°%d', $reservationId),
                'backUrl' => $this->generateUrl('payment_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'errorUrl' => $this->generateUrl('payment_error', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'returnUrl' => $this->generateUrl('payment_return', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'containsDonation' => false,
                'metadata' => [
                    'reservation_id' => $reservationId,
                ],
            ]);

            if (!isset($result['redirectUrl'])) {
                throw new \RuntimeException('HelloAsso response missing redirectUrl');
            }

            return $this->redirect($result['redirectUrl']);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create HelloAsso checkout intent', [
                'reservationId' => $reservationId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return $this->render('payment/error.html.twig');
        }
    }

    #[Route(path: '/webhook/paiement', name: 'payment_webhook', methods: ['POST'])]
    public function webhook(Request $request): Response
    {
        if (!$this->isEnabled()) {
            return new Response('Not configured', Response::HTTP_NOT_FOUND);
        }

        $rawContent = $request->getContent();
        $payload = json_decode($rawContent, true);

        if (!\is_array($payload)) {
            return new Response('Invalid JSON', Response::HTTP_BAD_REQUEST);
        }

        // Vérification IP d'origine
        if ($this->helloAssoServerIp !== $request->getClientIp()) {
            $this->logger->error('Payment webhook - Invalid IP', [
                'ip' => $request->getClientIp(),
                'payload' => $payload,
            ]);

            return new Response('Invalid IP', Response::HTTP_BAD_REQUEST);
        }

        // Vérification signature HMAC-SHA256
        $signatureHeader = $request->headers->get('x-ha-signature');
        if (null === $signatureHeader) {
            $this->logger->error('Payment webhook - Missing signature', [
                'payload' => $payload,
            ]);

            return new Response('Missing signature', Response::HTTP_BAD_REQUEST);
        }

        if ('' === $this->helloAssoSignatureKey) {
            $this->logger->error('Payment webhook - Signature key not configured');

            return new Response('Webhook not configured', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $calculatedSignature = hash_hmac('sha256', $rawContent, $this->helloAssoSignatureKey);
        if (!hash_equals($signatureHeader, $calculatedSignature)) {
            $this->logger->error('Payment webhook - Signature mismatch', [
                'payload' => $payload,
            ]);

            return new Response('Signature mismatch', Response::HTTP_FORBIDDEN);
        }

        // Filtrage : seuls les paiements autorisés nous intéressent
        $eventType = $payload['eventType'] ?? null;
        $state = $payload['data']['state'] ?? null;

        if ('Payment' !== $eventType || 'Authorized' !== $state) {
            $this->logger->info('Payment webhook - Ignored notification', [
                'eventType' => $eventType,
                'state' => $state,
            ]);

            return new Response('OK', Response::HTTP_OK);
        }

        // HelloAsso peut placer les metadata à différents endroits selon le type de notification
        $reservationId = $payload['metadata']['reservation_id']
            ?? $payload['data']['meta']['reservation_id']
            ?? null;
        $helloAssoPaymentId = $payload['data']['id'] ?? null;

        if (null === $reservationId || null === $helloAssoPaymentId) {
            $this->logger->error('Payment webhook - Missing reservation_id or payment id', [
                'payload' => $payload,
            ]);

            return new Response('OK', Response::HTTP_OK);
        }

        try {
            $this->loxyaReservationService->markReservationAsPaid((int) $reservationId, (string) $helloAssoPaymentId);
        } catch (\Exception $e) {
            $this->logger->error('Payment webhook - Loxya update failed', [
                'reservationId' => $reservationId,
                'helloAssoPaymentId' => $helloAssoPaymentId,
                'error' => $e->getMessage(),
            ]);
        }

        return new Response('OK', Response::HTTP_OK);
    }

    #[Route(path: '/paiement/retour', name: 'payment_return', methods: ['GET'])]
    public function paymentReturn(Request $request): Response
    {
        if (!$this->isEnabled()) {
            throw $this->createNotFoundException();
        }

        $code = $request->query->get('code', '');
        $status = 'succeeded' === $code ? 'success' : 'error';

        return $this->render('payment/return.html.twig', [
            'status' => $status,
        ]);
    }

    #[Route(path: '/paiement/annuler', name: 'payment_cancel', methods: ['GET'])]
    public function paymentCancel(): Response
    {
        if (!$this->isEnabled()) {
            throw $this->createNotFoundException();
        }

        return $this->render('payment/return.html.twig', [
            'status' => 'cancel',
        ]);
    }

    #[Route(path: '/paiement/erreur', name: 'payment_error', methods: ['GET'])]
    public function paymentError(): Response
    {
        if (!$this->isEnabled()) {
            throw $this->createNotFoundException();
        }

        return $this->render('payment/error.html.twig');
    }
}
