<?php

namespace App\Tests;

use App\Entity\Commission;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\UsertypeRepository;
use App\UserRights;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;


abstract class WebTestCase extends BaseWebTestCase
{
    use SessionHelper;

    protected static ?KernelBrowser $client;

    public function setUp(): void
    {
        parent::setUp();
        static::$client = static::createClient();
    }

    protected function signup(string $email = null)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        if (null === $email) {
            $email = 'test-'.bin2hex(random_bytes(12)).'@clubalpinlyon.fr';
        }

        $user = new User();
        $user->setEmail($email);
        $user->setFirstname('prenom');
        $user->setLastname('nom');
        $user->setNickname('nickname');
        $user->setCafnumParent('');
        $user->setTel('');
        $user->setTel2('');
        $user->setAdresse('');
        $user->setCp('');
        $user->setVille('');
        $user->setPays('');
        $user->setCiv('');
        $user->setMoreinfo('');
        $user->setCookietoken('');
        $user->setNomadeParent(0);

        $em->persist($user);
        $em->flush();

        return $user;
    }

    protected function signin($username = 'test@clubalpinlyon.fr', $providerKey = 'main')
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        if ($username instanceof User) {
            $em->refresh($username);
            $user = $username;
        } else {
            $user = $this->getContainer()->get(UserRepository::class)->findOneByEmail($username);
        }

        if (!$user) {
            throw new \LogicException(sprintf('The user "%s" does not exist.', $username));
        }

        $tokenStorage = $this->getContainer()->get(TokenStorageInterface::class);
        $token = new UsernamePasswordToken($user, $providerKey, $user->getRoles());
        $tokenStorage->setToken($token);
        
        // $token = new PostAuthenticationToken($user, $providerKey, $user->getRoles());

        $session = $this->getSession();
        $session->set('_security_'.$providerKey, serialize($token));
        $session->save();

        // the client must register the session cookie
        // taken from TestSessionListener
        $params = session_get_cookie_params();
        static::$client->getCookieJar()->set(new Cookie($session->getName(), $session->getId(), 0 === $params['lifetime'] ? 0 : time() + $params['lifetime'], $params['path'], $params['domain'], $params['secure'], $params['httponly']));

        return $user;
    }

    protected function signout($providerKey = 'main')
    {
        $session = $this->getSession();
        $session->remove('_security_'.$providerKey);
        $session->save();

        $this->getContainer()->get('security.token_storage')->setToken(null);
        static::$client->getCookieJar()->clear();
    }

    protected function createCommission(string $name = 'Alpinisme'): Commission
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $commission = new Commission($name, sprintf('commission-%s', bin2hex(random_bytes(12))), 1);
        $em->persist($commission);
        $em->flush();

        return $commission;
    }

    protected function addAttribute(User $user, string $attribute, ?string $param = null)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $userTypeRepo = $this->getContainer()->get(UsertypeRepository::class);
        $user->addAttribute($userTypeRepo->getByCode($attribute), $param);
        $em->flush();
        self::getContainer()->get(UserRights::class)->reset();
    }

    protected function getSession() : Session {
         return $this->createSession(static::$client);
    }
}
