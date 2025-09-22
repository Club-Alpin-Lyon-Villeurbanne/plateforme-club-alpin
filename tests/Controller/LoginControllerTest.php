<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class LoginControllerTest extends WebTestCase
{
    public function testLogin()
    {
        $hasherFactory = self::getContainer()->get(PasswordHasherFactoryInterface::class);

        $user = $this->signup(mt_rand() . 'test@clubalpinlyon.fr');
        $user->setPassword($hasherFactory->getPasswordHasher('login_form')->hash('youpla'));
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->flush();

        $this->client->request('GET', '/login');
        $this->assertResponseStatusCodeSame(200);

        $this->client->submitForm('connect-button', [
            '_username' => $user->getEmail(),
            '_password' => 'youpla',
        ]);
        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects('http://localhost/');
    }

    public function testLoginInvalidPassword()
    {
        $hasherFactory = self::getContainer()->get(PasswordHasherFactoryInterface::class);

        $user = $this->signup(mt_rand() . 'test@clubalpinlyon.fr');
        $user->setPassword($hasherFactory->getPasswordHasher('login_form')->hash('youpla'));
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->flush();

        $this->client->request('GET', '/login');
        $this->assertResponseStatusCodeSame(200);

        $this->client->submitForm('connect-button', [
            '_username' => $user->getEmail(),
            '_password' => 'invalid',
        ]);
        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects('http://localhost/login');
    }

    public function testLoginWhenConnected()
    {
        $user = $this->signup(mt_rand() . 'test@clubalpinlyon.fr');
        $this->signin($user);

        $this->client->request('GET', '/login');
        $this->assertResponseStatusCodeSame(302);
    }

    public function testPasswordLostWhenConnected()
    {
        $user = $this->signup(mt_rand() . 'test@clubalpinlyon.fr');
        $this->signin($user);

        $this->client->request('GET', '/password-lost');
        $this->assertResponseStatusCodeSame(302);
    }

    public function testPasswordLost()
    {
        $user = $this->signup(mt_rand() . 'test@clubalpinlyon.fr');

        $this->client->request('GET', '/password-lost');
        $this->assertResponseStatusCodeSame(200);

        $crawler = $this->client->submitForm('reset_password[submit]', [
            'reset_password[email]' => $user->getEmail(),
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Ré-initialisation du mot de passe en cours', $crawler->filter('h1')->first()->text());

        $emails = $this->getMailerMessages();
        $this->assertCount(1, $emails);

        $this->assertEmailHeaderSame($emails[0], 'To', $user->getEmail());
        $this->assertEmailTextBodyContains($emails[0], 'Votre mot de passe');
        $this->assertEmailHtmlBodyContains($emails[0], 'Votre mot de passe');
    }

    public function testSetPassword()
    {
        $user = $this->signup(mt_rand() . 'test@clubalpinlyon.fr');

        $this->client->request('GET', '/password');
        $this->assertResponseStatusCodeSame(302);

        $this->signin($user);

        $this->client->request('GET', '/password');
        $this->assertResponseStatusCodeSame(200);

        $this->client->submitForm('set_password[submit]', [
            'set_password[password][first]' => '!NewPassw0rd',
            'set_password[password][second]' => '!NewPassw0rd',
        ]);

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects('/');

        $emails = $this->getMailerMessages();
        $this->assertCount(1, $emails);

        $this->assertEmailHeaderSame($emails[0], 'To', sprintf('%s <%s>', $user->getNickname(), $user->getEmail()));
        $this->assertEmailTextBodyContains($emails[0], 'Modification de votre mot de passe');
        $this->assertEmailHtmlBodyContains($emails[0], 'Modification de votre mot de passe');
    }

    public function testChangePassword()
    {
        $hasherFactory = self::getContainer()->get(PasswordHasherFactoryInterface::class);

        $user = $this->signup(mt_rand() . 'test@clubalpinlyon.fr');
        $user->setMdp($hasherFactory->getPasswordHasher('login_form')->hash('!currentPassw0rd'));
        $this->getContainer()->get('doctrine')->getManager()->flush();

        $this->client->request('GET', '/change-password');
        $this->assertResponseStatusCodeSame(302);

        $this->signin($user);

        $this->client->request('GET', '/change-password');
        $this->assertResponseStatusCodeSame(200);

        $this->client->submitForm('change_password[submit]', [
            'change_password[current_password]' => '!currentPassw0rd',
            'change_password[password][first]' => '!NewPassw0rd',
            'change_password[password][second]' => '!NewPassw0rd',
        ]);

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects('/logout');

        $emails = $this->getMailerMessages();
        $this->assertCount(1, $emails);

        $this->assertEmailHeaderSame($emails[0], 'To', sprintf('%s <%s>', $user->getNickname(), $user->getEmail()));
        $this->assertEmailTextBodyContains($emails[0], 'Modification de votre mot de passe');
        $this->assertEmailHtmlBodyContains($emails[0], 'Modification de votre mot de passe');
    }
}
