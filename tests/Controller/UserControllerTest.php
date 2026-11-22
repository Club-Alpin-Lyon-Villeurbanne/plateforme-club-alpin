<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCase;

class UserControllerTest extends WebTestCase
{
    public function testCreationDAdherentRenvoieVersLaConnexionSiAnonyme(): void
    {
        $this->client->request('GET', '/adherents-creer.html');

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', $this->client->getResponse()->headers->get('Location'));
    }
}
