<?php

namespace App\Tests\Api;

use App\Entity\User;
use App\Tests\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * Limite de tentatives de connexion : sur le formulaire du site, pas sur l'API.
 *
 * L'API reçoit toutes les connexions de compta-club depuis les IP de Vercel : y limiter par IP
 * permettrait à n'importe qui de bloquer les connexions compta.
 */
class LoginThrottlingTest extends WebTestCase
{
    private const PASSWORD = 'Bon-mot-de-passe-1';

    public function testSiteLoginIsThrottledAfterFiveFailedAttempts(): void
    {
        $this->client->disableReboot();
        $user = $this->userWithPassword();

        for ($i = 0; $i < 5; ++$i) {
            $this->siteLogin($user, 'mauvais');
        }
        $this->siteLogin($user, self::PASSWORD);

        $this->assertResponseRedirects('http://localhost/login');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('body', 'Trop de tentatives');
    }

    public function testApiLoginIsNotThrottled(): void
    {
        $this->client->disableReboot();
        $user = $this->userWithPassword();

        for ($i = 0; $i < 5; ++$i) {
            $this->assertSame(401, $this->apiLogin($user, 'mauvais'));
        }

        $this->assertSame(200, $this->apiLogin($user, self::PASSWORD));
    }

    private function userWithPassword(): User
    {
        $user = $this->signup('throttle-' . bin2hex(random_bytes(6)) . '@clubalpinlyon.fr');
        $hasher = self::getContainer()->get(PasswordHasherFactoryInterface::class)->getPasswordHasher('login_form');
        $user->setPassword($hasher->hash(self::PASSWORD));
        $this->getContainer()->get('doctrine')->getManager()->flush();

        return $user;
    }

    private function siteLogin(User $user, string $password): void
    {
        $this->client->request('GET', '/login');
        $this->client->submitForm('connect-button', [
            '_username' => $user->getEmail(),
            '_password' => $password,
        ]);
    }

    private function apiLogin(User $user, string $password): int
    {
        $this->client->request('POST', '/api/auth', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => $user->getEmail(), 'password' => $password]));

        return $this->client->getResponse()->getStatusCode();
    }
}
