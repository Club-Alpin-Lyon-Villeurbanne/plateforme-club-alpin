<?php

namespace App\Security;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecaptchaValidator
{
    private const SCORE = 0.3;
    private string $kernelEnvironment;
    private string $recaptchaSecret;
    private HttpClientInterface $httpClient;

    public function __construct(
        string $kernelEnvironment,
        string $recaptchaSecret,
        HttpClientInterface $httpClient
    ) {
        $this->kernelEnvironment = $kernelEnvironment;
        $this->recaptchaSecret = $recaptchaSecret;
        $this->httpClient = $httpClient;
    }

    public function isValid(string $recaptchaToken, string $clientIp, ?string $action = null): bool
    {
        if ('test' === $this->kernelEnvironment) {
            return true;
        }

        // Let's verify the captcha
        $response = $this->httpClient->request('POST',
            'https://www.google.com/recaptcha/api/siteverify',
            [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'secret' => $this->recaptchaSecret,
                    'response' => $recaptchaToken,
                    'remoteip' => $clientIp,
                ],
            ]
        );
        $resp = $response->toArray();

        return !empty($resp['success']) && (null === $action || $action === $resp['action']) && $resp['score'] >= self::SCORE;
    }
}
