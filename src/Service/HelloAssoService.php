<?php

namespace App\Service;

use App\Entity\Evt;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HelloAssoService
{
    protected const string HELLO_ASSO_CAMPAIGN_ENDPOINT = '/v5/organizations/{organizationSlug}/forms/Event/action/quick-create';
    protected const string HELLO_ASSO_CAMPAIGN_PUBLISH_ENDPOINT = '/v5/organizations/{organizationSlug}/forms/Event/{formSlug}/state';
    protected const string HELLO_ASSO_PAYMENT_INFO_ENDPOINT = '/v5/organizations/{organizationSlug}/forms/Event/{formSlug}/payments';

    public function __construct(
        protected string $organizationSlug,
        protected string $baseUrl,
        protected int $activityTypeId,
        protected readonly HttpClientInterface $httpClient,
        protected readonly LoggerInterface $logger,
        protected readonly UrlGeneratorInterface $urlGenerator,
        protected HelloAssoClient $helloAssoClient,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function createFormForEvent(Evt $event): array
    {
        $eventDate = $event->getStartDate();
        $description = 'Frais d\'inscription pour la sortie ' . $event->getTitre() . ' du ' . $eventDate->format('d/m/Y');
        $description .= "\n\n" . $this->urlGenerator->generate('sortie', [
            'code' => $event->getCode(),
            'id' => $event->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        $params = [
            'title' => '[' . $eventDate->format('Y-m-d') . '] ' . $event->getTitre(),
            'description' => $description,
            'amountVisible' => true,
            'generateTickets' => false,
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

        return $this->helloAssoClient->createForm($apiEndpoint, $params);
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

            $this->helloAssoClient->publishForm($apiEndpoint);
        }
    }

    public function isConfigSet(): bool
    {
        return $this->helloAssoClient->areCredentialsSet()
            && !empty($this->organizationSlug)
            && !empty($this->activityTypeId)
            && !empty($this->baseUrl)
        ;
    }
}
