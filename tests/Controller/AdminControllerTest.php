<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCase;

class AdminControllerTest extends WebTestCase
{
    public function testLogin()
    {
        $this->client = static::createClient();

        $this->client->request('GET', '/admin/');
        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects('http://localhost/login');

        $user = $this->signup(mt_rand().'test@clubalpinlyon.fr');
        $this->signin($user);

        $this->assertFalse($this->getSession()->has('admin_caf'));

        $this->client->request('GET', '/admin/');
        $this->assertResponseStatusCodeSame(200);

        $this->client->submitForm('admin_connect', [
            'username' => 'caflyon',
            'password' => 'prout',
        ]);
        $this->assertResponseStatusCodeSame(200);

        $this->assertFalse($this->getSession()->has('admin_caf'));

        $this->client->submitForm('admin_connect', [
            'username' => 'caflyon',
            'password' => 'admin',
        ]);
        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects('/');

        $this->assertTrue($this->getSession()->get('admin_caf'));
    }
}
