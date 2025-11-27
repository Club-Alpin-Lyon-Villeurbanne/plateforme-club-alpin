<?php

namespace App\Service;

use Firebase\JWT\JWT;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MetabaseService
{
    private string $siteUrl;

    public function __construct(#[Autowire('%metabase_secret_key%')] private string $secretKey)
    {
        $this->siteUrl = 'https://wltukblxoyxlobfldmpp-metabase.services.clever-cloud.com';
    }

    public function generateDashboardUrl(int $dashboardId, array $params = []): string
    {
        $payload = [
            'resource' => ['dashboard' => $dashboardId],
            'params' => (object) $params,
            'exp' => time() + (10 * 60) // 10 minutes
        ];

        $token = JWT::encode($payload, $this->secretKey, 'HS256');

        return $this->siteUrl . '/embed/dashboard/' . $token . '#bordered=true&titled=true';
    }
}
