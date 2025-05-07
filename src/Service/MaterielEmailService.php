<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class MaterielEmailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $materielPlatformUrl
    ) {
    }

    public function sendAccountCreationEmail(string $to, string $firstName, string $lastName, array $credentials): void
    {
        $email = (new TemplatedEmail())
            ->from('materiel@clubalpinlyon.fr')
            ->to($to)
            ->subject('Votre compte sur la plateforme de réservation de matériel')
            ->htmlTemplate('email/transactional/materiel-account-creation.html.twig')
            ->context([
                'firstName' => $firstName,
                'lastName' => $lastName,
                'userEmail' => $credentials['email'],
                'password' => $credentials['password'],
                'pseudo' => $credentials['pseudo'],
                'platformUrl' => $this->materielPlatformUrl,
            ]);

        $this->mailer->send($email);
    }
}
