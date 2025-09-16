<?php

namespace App\Service;

use App\Entity\Evt;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HelloAssoService
{
    // region constantes
    protected const string HELLO_ASSO_CAMPAIGN_ENDPOINT = '/v5/organizations/{organizationSlug}/forms/Event/action/quick-create';
    protected const string HELLO_ASSO_CAMPAIGN_PUBLISH_ENDPOINT = '/v5/organizations/{organizationSlug}/forms/Event/{formSlug}/state';
    protected const string HELLO_ASSO_PAYMENT_INFO_ENDPOINT = '/v5/organizations/{organizationSlug}/forms/Event/{formSlug}/payments';
    // endregion

    // region attributs & construct
    public function __construct(
        protected string $organizationSlug,
        protected string $baseUrl,
        protected int $activityTypeId,
        protected readonly HttpClientInterface $httpClient,
        protected readonly LoggerInterface $logger,
        protected HelloAssoClient $helloAssoClient,
    ) {
    }
    // endregion

    // region méthodes : appels API
    public function getPaymentsForEvent(Evt $event): array
    {
        $payers = [];

        $organizationSlug = $this->organizationSlug;
        $formSlug = $event->getHelloAssoFormSlug();
        if (!empty($organizationSlug) && !empty($formSlug)) {
            $apiEndpoint = self::HELLO_ASSO_PAYMENT_INFO_ENDPOINT;
            $apiEndpoint = str_replace('{organizationSlug}', $organizationSlug, $apiEndpoint);
            $apiEndpoint = str_replace('{formSlug}', $formSlug, $apiEndpoint);

            $organizationAccessToken = $this->helloAssoClient->login();

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
                            'pageSize' => 100,      // @todo gérer la pagination si > 100
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
                    'price' => (int) ($event->getPaymentAmount() * 100),        // prix en centimes
                ],
            ],
        ];

        $apiEndpoint = self::HELLO_ASSO_CAMPAIGN_ENDPOINT;
        $apiEndpoint = str_replace('{organizationSlug}', $this->organizationSlug, $apiEndpoint);

        $organizationAccessToken = $this->helloAssoClient->login();

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

            $organizationAccessToken = $this->helloAssoClient->login();

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
}
