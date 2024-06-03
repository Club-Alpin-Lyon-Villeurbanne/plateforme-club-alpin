<?php

namespace App\Security;

use Symfony\Bundle\SecurityBundle\Security;

class ExpenseAuthorization
{
    private $gistUrl;
    private $authorizedIds = null;
    private $security;

    public function __construct(string $gistUrl, Security $security)
    {
        $this->gistUrl = $gistUrl;
        $this->security = $security;
    }

    private function fetchAuthorizedIds(): void
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->gistUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)");
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response !== false) {
            $jsonResponse = json_decode($response, true);

            $this->authorizedIds = array_map('trim', explode(",", array_values($jsonResponse['files'])[0]['content']));
        } else {
            $this->authorizedIds = [];
        }
    }

    public function isAuthorized(): bool
    {
        $this->fetchAuthorizedIds();

        $user = $this->security->getUser();

        if ($user === null) {
            return false;
        }

        return in_array($user->getId(), $this->authorizedIds);
    }
}
