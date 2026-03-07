<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCase;

class HomepageControllerTest extends WebTestCase
{
    public function testHomepageWithNonExistentCommissionDoesNotCrash(): void
    {
        $this->client->request('GET', '/accueil/code-inexistant-xyz.html');

        $this->assertResponseIsSuccessful();
    }
}
