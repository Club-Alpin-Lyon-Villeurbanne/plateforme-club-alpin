<?php

namespace App\Tests\Api;

use App\Tests\WebTestCase;

/**
 * Limite de tentatives sur POST /api/auth : 5 échecs, puis refus même avec le bon mot de passe.
 */
class LoginThrottlingTest extends WebTestCase
{
    public function testLoginIsThrottledAfterFiveFailedAttempts(): void
    {
        $this->client->disableReboot();
        $email = 'throttle-' . bin2hex(random_bytes(6)) . '@clubalpinlyon.fr';
        $user = $this->signup($email);
        $hasher = $this->getContainer()->get('security.user_password_hasher');
        $user->setMdp($hasher->hashPassword($user, 'Bon-mot-de-passe-1'));
        $this->getContainer()->get('doctrine')->getManager()->flush();

        for ($i = 0; $i < 5; ++$i) {
            $this->assertSame(401, $this->login($email, 'mauvais'));
        }

        $this->assertSame(401, $this->login($email, 'Bon-mot-de-passe-1'), 'Le bon mot de passe doit être refusé pendant le blocage.');
        $this->assertStringContainsString('Trop de tentatives', json_decode((string) $this->client->getResponse()->getContent(), true)['message'] ?? '');
    }

    private function login(string $email, string $password): int
    {
        $this->client->request('POST', '/api/auth', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => $email, 'password' => $password]));

        return $this->client->getResponse()->getStatusCode();
    }
}
