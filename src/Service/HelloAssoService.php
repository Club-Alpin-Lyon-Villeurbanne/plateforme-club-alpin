<?php

namespace App\Service;

use AdrienGras\PKCE\PKCEUtils;
use App\Entity\Config;
use App\Entity\Evt;
use App\Repository\ConfigRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Provider\GenericProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HelloAssoService
{
    // region constantes
    public const string HELLO_ASSO_TOKEN_ENDPOINT = '/oauth2/token';
    public const int HELLO_ASSO_REFRESH_TOKEN_DURATION_IN_DAYS = 30;
    protected const string HELLO_ASSO_AUTHORIZE_ENDPOINT = '/authorize';
    protected const string HELLO_ASSO_RESOURCE_ENDPOINT = '/oauth2/userinfo';
    protected const string HELLO_ASSO_CAMPAIGN_ENDPOINT = '/v5/organizations/{organizationSlug}/forms/Event/action/quick-create';
    protected const string HELLO_ASSO_CAMPAIGN_PUBLISH_ENDPOINT = '/v5/organizations/{organizationSlug}/forms/Event/{formSlug}/state';
    protected const string HELLO_ASSO_PAYMENT_INFO_ENDPOINT = '/v5/organizations/{organizationSlug}/forms/Event/{formSlug}/payments';
    // endregion

    // region attributes
    private GenericProvider $provider;

    public function __construct(
        protected string $clientId,
        protected string $clientSecret,
        protected string $organizationSlug,
        protected string $baseUrl,
        protected string $authorizeUrl,
        protected int $activityTypeId,
        protected ConfigRepository $configRepository,
        protected readonly HttpClientInterface $httpClient,
        protected readonly RouterInterface $router,
        protected readonly EntityManagerInterface $entityManager,
        protected readonly LoggerInterface $logger
    ) {
    }
    // endregion

    // region méthodes : appels API
    public function login(): string
    {
        $accessToken = '';

        try {
            $response = $this->httpClient->request(
                'POST',
                $this->baseUrl . self::HELLO_ASSO_TOKEN_ENDPOINT,
                [
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'accept' => 'application/json',
                    ],
                    'body' => [
                        'client_id' => $this->clientId,
                        'client_secret' => $this->clientSecret,
                        'grant_type' => 'client_credentials',
                    ],
                ],
            );
            $data = $response->toArray();
            $accessToken = $data['access_token'];
            $organizationRefreshToken = $data['refresh_token'];

            // stocker le refresh token en bdd
            $this->saveRefreshToken($organizationRefreshToken, 'organization');
            $this->saveTokenGetDate();
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $accessToken;
    }

    public function getAccessTokenFromRefreshToken(): string
    {
        $accessToken = '';
        $today = new \DateTime();
        $refreshToken = $this->getRefreshToken('organization');
        $tokenGetDate = $this->getTokenGetDate();

        // si pas de refresh token ou expiré
        if (!$refreshToken || !$tokenGetDate || $tokenGetDate->diff($today)->d >= (self::HELLO_ASSO_REFRESH_TOKEN_DURATION_IN_DAYS - 2)) {
            return $this->login();
        }

        try {
            $response = $this->httpClient->request(
                'POST',
                $this->baseUrl . self::HELLO_ASSO_TOKEN_ENDPOINT,
                [
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'accept' => 'application/json',
                    ],
                    'body' => [
                        'refresh_token' => $refreshToken,
                        'grant_type' => 'refresh_token',
                    ],
                ],
            );
            $data = $response->toArray();
            $accessToken = $data['access_token'];
            $refreshToken = $data['refresh_token'];

            // stocker le refresh token en bdd ainsi que sa date d'obtention
            $this->saveRefreshToken($refreshToken, 'organization');
            $this->saveTokenGetDate();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $accessToken;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getPaymentsForEvent(Evt $event): array
    {
        $payers = [];

        $organizationSlug = $this->organizationSlug;
        $formSlug = $event->getHelloAssoFormSlug();
        if (!empty($organizationSlug) && !empty($formSlug)) {
            $apiEndpoint = self::HELLO_ASSO_PAYMENT_INFO_ENDPOINT;
            $apiEndpoint = str_replace('{organizationSlug}', $organizationSlug, $apiEndpoint);
            $apiEndpoint = str_replace('{formSlug}', $formSlug, $apiEndpoint);

            $organizationAccessToken = $this->getAccessTokenFromRefreshToken();

            try {
                $response = $this->httpClient->request(
                    'GET',
                    $this->baseUrl . $apiEndpoint,
                    [
                        'headers' => [
                            'accept' => 'application/json',
                            'Authorization' => 'Bearer ' . $organizationAccessToken,
                        ],
                        'query' => [
                            'pageSize' => 100,
                        ],
                    ],
                );
                $data = $response->toArray();

                foreach ($data['data'] as $payment) {
                    if ('Authorized' === $payment['state']) {
                        $payers[] = $payment['payer']['email'];
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        return $payers;
    }

    public function createFormForEvent(Evt $event): array
    {
        $return = [];
        $params = [
            'title' => $event->getTitre(),
            'description' => 'Frais d\'inscription pour la sortie ' . $event->getTitre(),
            'amountVisible' => true,
            'maxEntries' => (int) $event->getNgensMax(),
            'generateTickets' => true,
            'allowIndividualPayer' => true,
            'allowOrganismPayer' => true,
            'activityTypeId' => $this->activityTypeId,      // activityType = "Sortie"
            'tierList' => [
                [
                    'label' => 'Frais d\'inscription',
                    'price' => (int) ($event->getHelloAssoFormAmount() * 100),        // prix en centimes
                ],
            ],
        ];

        $apiEndpoint = self::HELLO_ASSO_CAMPAIGN_ENDPOINT;
        $apiEndpoint = str_replace('{organizationSlug}', $this->organizationSlug, $apiEndpoint);

        $organizationAccessToken = $this->getAccessTokenFromRefreshToken();

        try {
            $response = $this->httpClient->request(
                'POST',
                $this->baseUrl . $apiEndpoint,
                [
                    'headers' => [
                        'Content-Type' => 'application/*+json',
                        'accept' => 'application/json',
                        'Authorization' => 'Bearer ' . $organizationAccessToken,
                    ],
                    'json' => $params,
                ],
            );
            $return = $response->toArray();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $return;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function publishFormForEvent(Evt $event): void
    {
        $organizationSlug = $this->organizationSlug;
        $formSlug = $event->getHelloAssoFormSlug();
        if (!empty($organizationSlug) && !empty($formSlug)) {
            $apiEndpoint = self::HELLO_ASSO_CAMPAIGN_PUBLISH_ENDPOINT;
            $apiEndpoint = str_replace('{organizationSlug}', $organizationSlug, $apiEndpoint);
            $apiEndpoint = str_replace('{formSlug}', $formSlug, $apiEndpoint);

            $organizationAccessToken = $this->getAccessTokenFromRefreshToken();

            try {
                $this->httpClient->request(
                    'PUT',
                    $this->baseUrl . $apiEndpoint,
                    [
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $organizationAccessToken,
                        ],
                        'json' => [
                            'state' => 'Private',
                        ],
                    ],
                );
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }
    // endregion

    // region méthodes : config en bdd
    public function getRefreshToken(string $type = 'partner'): ?string
    {
        return $this->entityManager->getRepository(Config::class)->findOneBy(['code' => $type . '_refresh_token'])?->getValue();
    }

    public function getTokenGetDate(): ?\DateTime
    {
        $dateStr = $this->entityManager->getRepository(Config::class)->findOneBy(['code' => 'organization_token_get_date'])?->getValue();

        return $dateStr ? \DateTime::createFromFormat('Y-m-d H:i:s', $dateStr) : null;
    }

    public function saveRefreshToken(string $refreshToken, string $type = 'partner'): void
    {
        $this->configRepository->saveConfigValue($type . '_refresh_token', $refreshToken);
    }

    public function saveTokenGetDate(): void
    {
        $this->configRepository->saveConfigValue('organization_token_get_date', (new \DateTime())->format('Y-m-d H:i:s'));
    }
    // endregion
}
