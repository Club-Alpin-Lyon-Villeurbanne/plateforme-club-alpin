<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;

// Solution from https://github.com/symfony/symfony/discussions/46961#discussioncomment-4573371
trait SessionHelper
{
    public function createSession(KernelBrowser $client): Session
    {
        $cookie = $client->getCookieJar()->get('MOCKSESSID');

        $container = static::getContainer();
        $session = $container->get('session.factory')->createSession();

        if ($cookie) {
            $session->setId($cookie->getValue());
            $session->start();
        } else {
            $session->start();
            $session->save();

            $sessionCookie = new Cookie(
                $session->getName(),
                $session->getId(),
                null,
                null,
                'localhost',
            );
            $client->getCookieJar()->set($sessionCookie);
        }

        return $session;
    }

    public function generateCsrfToken(KernelBrowser $client, string $tokenId): string
    {
        $session = $this->createSession($client);
        $container = static::getContainer();
        $tokenGenerator = $container->get('security.csrf.token_generator');
        $csrfToken = $tokenGenerator->generateToken();
        $session->set(SessionTokenStorage::SESSION_NAMESPACE . "/{$tokenId}", $csrfToken);
        $session->save();

        return $csrfToken;
    }
}
