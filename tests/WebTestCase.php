<?php

namespace App\Tests;

use App\Entity\CafUser;
use App\Repository\CafUserRepository;
use Bundle\BlackfireBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

abstract class WebTestCase extends BaseWebTestCase
{
    protected ?KernelBrowser $client = null;

    protected function signup($email = 'test@clubalpinlyon.fr')
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        $user = new CafUser();
        $user->setEmailUser($email);
        $user->setFirstnameUser('prenom');
        $user->setLastnameUser('nom');
        $user->setNicknameUser('nickname');
        $user->setCafnumParentUser('');
        $user->setTelUser('');
        $user->setTel2User('');
        $user->setAdresseUser('');
        $user->setCpUser('');
        $user->setVilleUser('');
        $user->setPaysUser('');
        $user->setCivUser('');
        $user->setMoreinfoUser('');
        $user->setCookietokenUser('');
        $user->setNomadeParentUser(0);

        $em->persist($user);
        $em->flush();

        return $user;
    }

    protected function signin($username = 'test@clubalpinlyon.fr', $providerKey = 'main')
    {
        if (!$this->client) {
            throw new \LogicException('$this->client should be initialized before calling this method');
        }

        $em = $this->getContainer()->get('doctrine')->getManager();

        if ($username instanceof CafUser) {
            $em->refresh($username);
            $user = $username;
        } else {
            $user = $this->getContainer()->get(CafUserRepository::class)->findOneByEmailUser($username);
        }

        if (!$user) {
            throw new \LogicException(sprintf('The user "%s" does not exist.', $username));
        }

        $token = new PostAuthenticationToken($user, $providerKey, $user->getRoles());
        $token->setAuthenticated(true); // mandatory since sf2.2, without that the user is not connected

        $session = $this->getSession();
        $session->set('_security_'.$providerKey, serialize($token));
        $session->save();

        // the client must register the session cookie
        // taken from TestSessionListener
        $params = session_get_cookie_params();
        $this->client->getCookieJar()->set(new Cookie($session->getName(), $session->getId(), 0 === $params['lifetime'] ? 0 : time() + $params['lifetime'], $params['path'], $params['domain'], $params['secure'], $params['httponly']));

        return $user;
    }

    protected function signout($providerKey = 'main')
    {
        if (!$this->client) {
            throw new \LogicException('$this->client should be initialized before calling this method');
        }

        $session = $this->getSession();
        $session->remove('_security_'.$providerKey);
        $session->save();

        $this->getContainer()->get('security.token_storage')->setToken(null);
        $this->client->getCookieJar()->clear();
    }

    protected function getSession()
    {
        if (!$this->client) {
            throw new \LogicException('$this->client should be initialized before calling this method');
        }

        $request = Request::create('/');
        $event = new RequestEvent($this->client->getKernel(), $request, HttpKernelInterface::MAIN_REQUEST);
        static::getContainer()->get('session_listener')->onKernelRequest($event);

        return $request->getSession();
    }
}
