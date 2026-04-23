<?php

namespace App\Controller;

use App\Entity\ProcessedHelloAssoPayment;
use App\Repository\ProcessedHelloAssoPaymentRepository;
use App\Service\HelloAssoClient;
use App\Service\LoxyaReservationService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaymentController extends AbstractController
{
    public const int MAX_AMOUNT_CENTS = 100_000;

    public function __construct(
        private readonly string $helloAssoServerIp,
        private readonly string $helloAssoSignatureKey,
        private readonly string $helloAssoOrganizationSlug,
        private readonly string $loxyaJwt,
        private readonly string $loxyaLinkSignatureKey,
        private readonly HelloAssoClient $helloAssoClient,
        private readonly LoxyaReservationService $loxyaReservationService,
        private readonly ProcessedHelloAssoPaymentRepository $processedPaymentRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    private function isEnabled(): bool
    {
        return '' !== $this->loxyaJwt
            && '' !== $this->loxyaLinkSignatureKey
            && '' !== $this->helloAssoSignatureKey;
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

        if ($reservationId <= 0 || $amount <= 0 || $amount > self::MAX_AMOUNT_CENTS) {
            return new Response(sprintf('Paramètres reservation_id et amount obligatoires (entiers positifs, max %d€).', (int) (self::MAX_AMOUNT_CENTS / 100)), Response::HTTP_BAD_REQUEST);
        }

        // Format canonique HMAC convenu avec Loxya : "{reservation_id}|{amount}" (entiers en base 10, séparés par "|").
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

            return $this->withSecurityHeaders($this->render('payment/error.html.twig'));
        }
    }

    #[Route(path: '/webhook/paiement', name: 'payment_webhook', methods: ['POST'])]
    public function webhook(Request $request): Response
    {
        if (!$this->isEnabled()) {
            throw $this->createNotFoundException();
        }

        // Ordre des vérifs : IP → signature → décodage JSON. Aucun payload non authentifié n'est loggé.
        if ($this->helloAssoServerIp !== $request->getClientIp()) {
            $this->logger->error('Payment webhook - Invalid IP', [
                'ip' => $request->getClientIp(),
            ]);

            return new Response('Invalid IP', Response::HTTP_BAD_REQUEST);
        }

        $signatureHeader = $request->headers->get('x-ha-signature');
        if (null === $signatureHeader) {
            $this->logger->error('Payment webhook - Missing signature');

            return new Response('Missing signature', Response::HTTP_BAD_REQUEST);
        }

        $rawContent = $request->getContent();
        $calculatedSignature = hash_hmac('sha256', $rawContent, $this->helloAssoSignatureKey);
        if (!hash_equals($calculatedSignature, $signatureHeader)) {
            $this->logger->error('Payment webhook - Signature mismatch');

            return new Response('Signature mismatch', Response::HTTP_FORBIDDEN);
        }

        $payload = json_decode($rawContent, true);
        if (!\is_array($payload)) {
            return new Response('Invalid JSON', Response::HTTP_BAD_REQUEST);
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

        $reservationId = filter_var($reservationId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (false === $reservationId || !\is_scalar($helloAssoPaymentId) || '' === (string) $helloAssoPaymentId) {
            $this->logger->error('Payment webhook - Invalid or missing reservation_id / payment id', [
                'eventType' => $eventType,
                'state' => $state,
            ]);

            return new Response('OK', Response::HTTP_OK);
        }

        $helloAssoPaymentId = (string) $helloAssoPaymentId;

        // Idempotence : si le paiement a déjà été traité, on ne refait ni l'appel Loxya ni la persistence.
        if (null !== $this->processedPaymentRepository->findOneByHelloAssoPaymentId($helloAssoPaymentId)) {
            $this->logger->info('Payment webhook - Already processed, skipping', [
                'reservationId' => $reservationId,
                'helloAssoPaymentId' => $helloAssoPaymentId,
            ]);

            return new Response('OK', Response::HTTP_OK);
        }

        try {
            $this->loxyaReservationService->markReservationAsPaid($reservationId, $helloAssoPaymentId);
        } catch (\Exception $e) {
            $this->logger->error('Payment webhook - Loxya update failed, HelloAsso will retry', [
                'reservationId' => $reservationId,
                'helloAssoPaymentId' => $helloAssoPaymentId,
                'error' => $e->getMessage(),
            ]);

            // On renvoie 503 pour que HelloAsso retente. Aucune ligne ProcessedHelloAssoPayment persistée
            // → la prochaine tentative rappellera Loxya.
            return new Response('Loxya update failed', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        try {
            $this->processedPaymentRepository->save(new ProcessedHelloAssoPayment($helloAssoPaymentId, $reservationId));
        } catch (UniqueConstraintViolationException) {
            // Race condition : deux workers sont passés sur le même payment_id simultanément.
            // Le PUT Loxya étant idempotent pour ce use-case, on traite comme un succès.
            $this->logger->info('Payment webhook - Concurrent processing detected', [
                'helloAssoPaymentId' => $helloAssoPaymentId,
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

        return $this->withSecurityHeaders($this->render('payment/return.html.twig', [
            'status' => $status,
        ]));
    }

    #[Route(path: '/paiement/annuler', name: 'payment_cancel', methods: ['GET'])]
    public function paymentCancel(): Response
    {
        if (!$this->isEnabled()) {
            throw $this->createNotFoundException();
        }

        return $this->withSecurityHeaders($this->render('payment/return.html.twig', [
            'status' => 'cancel',
        ]));
    }

    #[Route(path: '/paiement/erreur', name: 'payment_error', methods: ['GET'])]
    public function paymentError(): Response
    {
        if (!$this->isEnabled()) {
            throw $this->createNotFoundException();
        }

        return $this->withSecurityHeaders($this->render('payment/error.html.twig'));
    }

    private function withSecurityHeaders(Response $response): Response
    {
        $response->headers->set('X-Frame-Options', 'DENY');

        return $response;
    }
}
