<?php

namespace App\Service;

use App\Mailer\Mailer;

class MaterielEmailService
{
    public function __construct(
        private readonly Mailer $mailer,
        private readonly string $materielPlatformUrl
    ) {
    }

    public function sendAccountCreationEmail(string $to, string $firstName, string $lastName, array $credentials): void
    {
        $this->mailer->send(
            $to,
            'transactional/materiel-account-creation',
            [
                'firstName' => ucfirst($firstName),
                'lastName' => strtoupper($lastName),
                'userEmail' => $credentials['email'],
                'password' => $credentials['password'],
                'pseudo' => $credentials['pseudo'],
                'platformUrl' => $this->materielPlatformUrl,
            ]
        );
    }
}
