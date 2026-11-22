<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCase;

class SearchControllerTest extends WebTestCase
{
    public function testRechercheSansJetonCsrf(): void
    {
        $this->client->request('POST', '/recherche.html', ['str' => 'Mont Blanc']);

        $this->assertResponseIsSuccessful();
    }
}
