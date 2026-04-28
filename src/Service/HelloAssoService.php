<?php

namespace App\Service;

use App\Entity\Evt;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HelloAssoService
{
    protected const string HELLO_ASSO_CAMPAIGN_ENDPOINT = '/v5/organizations/{organizationSlug}/forms/Event/action/quick-create';
    protected const string HELLO_ASSO_CAMPAIGN_PUBLISH_ENDPOINT = '/v5/organizations/{organizationSlug}/forms/Event/{formSlug}/state';

    /**
     * @param string $organizationSlug Slug de l'organisation HelloAsso
     * @param string $baseUrl          URL de base de l'API HelloAsso
     * @param int    $activityTypeId   Identifiant du type d'activité HelloAsso (ex. "Sortie")
     */
    public function __construct(
        protected string $organizationSlug,
        protected string $baseUrl,
        protected int $activityTypeId,
        protected readonly UrlGeneratorInterface $urlGenerator,
        protected HelloAssoClient $helloAssoClient,
    ) {
    }

    public function createFormForEvent(Evt $event): array
    {
        $eventDate = $event->getStartDate();
        $description = 'Frais d\'inscription pour la sortie ' . $event->getTitre() . ' du ' . $eventDate->format('d/m/Y');
        $description .= "\n\n" . $this->urlGenerator->generate('sortie', [
            'code' => $event->getCode(),
            'id' => $event->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        $commissionTitle = trim((string) $event->getCommission()?->getTitle());
        $organizerLastname = trim((string) $event->getUser()?->getLastname());
        $departurePlace = trim((string) $event->getPlace());

        if ('' === $commissionTitle || '' === $organizerLastname || '' === $departurePlace) {
            throw new \InvalidArgumentException('Impossible de créer le titre HelloAsso: commission, organisateur et lieu de départ sont obligatoires.');
        }

        $params = [
            'title' => sprintf('[%s] %s - %s - %s', $eventDate->format('Y-m-d'), $commissionTitle, $organizerLastname, $departurePlace),
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

    /**
     * Vérifie que les paramètres de configuration HelloAsso sont tous renseignés.
     */
    public function isConfigSet(): bool
    {
        return $this->helloAssoClient->areCredentialsSet()
            && !empty($this->organizationSlug)
            && !empty($this->activityTypeId)
            && !empty($this->baseUrl)
        ;
    }
}
