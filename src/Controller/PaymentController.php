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
    public const int MAX_AMOUNT_CENTS = 100_000;

    public function __construct(
        private readonly string $helloAssoServerIp,
        private readonly string $helloAssoSignatureKey,
        private readonly string $helloAssoOrganizationSlug,
        private readonly string $loxyaLinkSignatureKey,
        private readonly HelloAssoClient $helloAssoClient,
        private readonly LoxyaReservationService $loxyaReservationService,
        private readonly LoggerInterface $logger,
    ) {
    }

    private function isEnabled(): bool
    {
        // Seule la clé du lien (partagée avec Loxya) est requise ; la signature webhook est optionnelle (cf. webhook()).
        return '' !== $this->loxyaLinkSignatureKey;
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
            return $this->invalidLinkResponse(Response::HTTP_BAD_REQUEST);
        }

        // Format canonique HMAC convenu avec Loxya : "{reservation_id}|{amount}" (entiers en base 10, séparés par "|").
        $expectedSignature = hash_hmac('sha256', $reservationId . '|' . $amount, $this->loxyaLinkSignatureKey);
        if (!\is_string($signature) || !hash_equals($expectedSignature, $signature)) {
            $this->logger->error('Payment checkout - Invalid signature', [
                'reservationId' => $reservationId,
                'amount' => $amount,
            ]);

            return $this->invalidLinkResponse(Response::HTTP_FORBIDDEN);
        }

        // Risque résiduel assumé : la signature (reservation_id|amount) n'ayant ni timestamp ni nonce,
        // le lien est rejouable et un adhérent peut repasser par HelloAsso et payer deux fois. Pas de
        // garde-fou ici pour préserver le bridge stateless : le club n'est pas lésé (il encaisse les deux,
        // HelloAsso est gratuit), Loxya reste cohérent (PUT idempotent), seul l'adhérent est remboursé à
        // la main. Cas rare vu les montants/volumes ; à réévaluer si les doublons deviennent fréquents.
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

            $redirectUrl = $result['redirectUrl'] ?? null;
            if (!\is_string($redirectUrl) || !self::isHelloAssoUrl($redirectUrl)) {
                throw new \RuntimeException('Invalid HelloAsso redirect URL');
            }

            return $this->redirect($redirectUrl);
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

        // Garde principal : allowlist d'IP HelloAsso. 403 sans body pour ne pas révéler le contrôle échoué.
        if ($this->helloAssoServerIp !== $request->getClientIp()) {
            $this->logger->error('Payment webhook - Invalid IP', [
                'ip' => $request->getClientIp(),
            ]);

            return new Response('', Response::HTTP_FORBIDDEN);
        }

        $rawContent = $request->getContent();

        // Signature vérifiée seulement si clé + header présents (HelloAsso ne signe pas les comptes non-partenaires).
        $signatureHeader = $request->headers->get('x-ha-signature');
        if ('' !== $this->helloAssoSignatureKey && null !== $signatureHeader) {
            $calculatedSignature = hash_hmac('sha256', $rawContent, $this->helloAssoSignatureKey);
            if (!hash_equals($calculatedSignature, $signatureHeader)) {
                $this->logger->error('Payment webhook - Signature mismatch');

                return new Response('', Response::HTTP_FORBIDDEN);
            }
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

        // Metadata du checkout-intent renvoyées au niveau racine par HelloAsso
        // (cf. https://dev.helloasso.com/docs/validation-de-vos-paiements).
        $reservationId = $payload['metadata']['reservation_id'] ?? null;
        $helloAssoPaymentId = $payload['data']['id'] ?? null;

        $reservationId = filter_var($reservationId, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if (false === $reservationId
            || (!\is_string($helloAssoPaymentId) && !\is_int($helloAssoPaymentId))
            || '' === (string) $helloAssoPaymentId
        ) {
            $this->logger->error('Payment webhook - Invalid or missing reservation_id / payment id', [
                'eventType' => $eventType,
                'state' => $state,
            ]);

            return new Response('OK', Response::HTTP_OK);
        }

        $helloAssoPaymentId = (string) $helloAssoPaymentId;

        try {
            // Le PUT Loxya est idempotent : un rejeu HelloAsso avec le même payment_id
            // produit le même état (couleur + note), pas besoin de dédup applicative.
            $this->loxyaReservationService->markReservationAsPaid($reservationId, $helloAssoPaymentId);
        } catch (\Exception $e) {
            $this->logger->error('Payment webhook - Loxya update failed, HelloAsso will retry', [
                'reservationId' => $reservationId,
                'helloAssoPaymentId' => $helloAssoPaymentId,
                'error' => $e->getMessage(),
            ]);

            // On renvoie 503 pour que HelloAsso retente plus tard.
            return new Response('Loxya update failed', Response::HTTP_SERVICE_UNAVAILABLE);
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

    // Page dédiée (≠ error.html.twig « réessaie ») : un lien malformé ne se répare pas en retentant.
    private function invalidLinkResponse(int $statusCode): Response
    {
        $response = $this->render('payment/invalid_link.html.twig');
        $response->setStatusCode($statusCode);

        return $this->withSecurityHeaders($response);
    }

    // Defense in depth : on n'accepte un redirect HelloAsso que si l'URL est en HTTPS,
    // ne contient pas de userinfo (https://attacker@helloasso.com/...) et que l'host est
    // helloasso.com ou helloasso-sandbox.com (ou un sous-domaine de l'un des deux). Le
    // sandbox utilise l'apex helloasso-sandbox.com (ex. www.helloasso-sandbox.com), distinct
    // de helloasso.com — sans lui, aucun test de bout en bout en sandbox n'est possible.
    // Le suffixe est littéral, donc aucun risque d'homographe punycode (ASCII).
    private static function isHelloAssoUrl(string $url): bool
    {
        $parsed = parse_url($url);
        if (false === $parsed) {
            return false;
        }

        if (isset($parsed['user']) || isset($parsed['pass'])) {
            return false;
        }

        $scheme = $parsed['scheme'] ?? null;
        $host = $parsed['host'] ?? null;
        if ('https' !== $scheme || !\is_string($host)) {
            return false;
        }

        foreach (['helloasso.com', 'helloasso-sandbox.com'] as $domain) {
            if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                return true;
            }
        }

        return false;
    }
}
