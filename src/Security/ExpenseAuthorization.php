<?php

namespace App\Security;

use Symfony\Bundle\SecurityBundle\Security;

class ExpenseAuthorization
{
    private $gistUrl;
    private $authorizedIds;
    private $security;

    public function __construct(string $gistUrl, Security $security)
    {
        $this->gistUrl = $gistUrl;
        $this->security = $security;
    }

    private function fetchAuthorizedIds(): void
    {
        $ch = curl_init();
        curl_setopt($ch, \CURLOPT_URL, $this->gistUrl);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, \CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)');
        $response = curl_exec($ch);
        curl_close($ch);

        if (false !== $response) {
            $jsonResponse = json_decode($response, true);

            $this->authorizedIds = array_map('intval', explode(',', array_values($jsonResponse['files'])[0]['content']));
        } else {
            $this->authorizedIds = [];
        }
    }

    public function isAuthorized(): bool
    {
        $this->fetchAuthorizedIds();

        $user = $this->security->getUser();

        if (null === $user) {
            return false;
        }

        return \in_array($user->getId(), $this->authorizedIds, true);
    }
}
