<?php

namespace App\Tests\Controller;

use App\Security\SecurityConstants;
use App\Tests\WebTestCase;

class AdminControllerTest extends WebTestCase
{
    public function testLogin()
    {
        $this->client->request('GET', '/admin/');
        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects('http://localhost/login');

        $user = $this->signup(mt_rand() . 'test@clubalpinlyon.fr');
        $this->signin($user);

        $this->assertEquals($this->getSession()->has(SecurityConstants::SESSION_USER_ROLE_KEY), null);

        $this->client->request('GET', '/admin/');
        $this->assertResponseStatusCodeSame(200);

        $this->client->submitForm('admin_connect', [
            'username' => 'admin',
            'password' => 'prout',
        ]);
        $this->assertResponseStatusCodeSame(200);

        $this->assertEquals($this->getSession()->has(SecurityConstants::SESSION_USER_ROLE_KEY), null);

        $this->client->submitForm('admin_connect', [
            'username' => 'admin',
            'password' => 'admin',
        ]);
        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects('/');

        $this->assertEquals($this->getSession()->get(SecurityConstants::SESSION_USER_ROLE_KEY), SecurityConstants::ROLE_ADMIN);
    }
}
