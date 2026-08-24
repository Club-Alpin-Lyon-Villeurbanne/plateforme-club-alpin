<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCase;

class CommuneControllerTest extends WebTestCase
{
    private function autocomplete(string $query): array
    {
        $this->client->request(
            'POST',
            '/commune/autocompletion',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X-Requested-With' => 'XMLHttpRequest'],
            json_encode(['query' => $query])
        );

        $this->assertResponseIsSuccessful();

        return json_decode($this->client->getResponse()->getContent(), true);
    }

    public function testReturnsCanonicalLabelsIncludingHameaux(): void
    {
        $this->signin($this->signup());

        $labels = array_column($this->autocomplete('74400'), 'label');

        // la commune principale et les hameaux (ligne5) sont distingués par leur suffixe
        $this->assertContains('74400 Chamonix-Mont-Blanc', $labels);
        $this->assertContains('74400 Chamonix-Mont-Blanc (ARGENTIERE)', $labels);
        $this->assertContains('74400 Chamonix-Mont-Blanc (LES PRAZ DE CHAMONIX)', $labels);
    }

    public function testResponseExposesOnlyLabel(): void
    {
        $this->signin($this->signup());

        $suggestions = $this->autocomplete('74400');

        $this->assertNotEmpty($suggestions);
        // les coordonnées ne doivent plus être exposées : le serveur en est l'autorité
        foreach ($suggestions as $suggestion) {
            $this->assertSame(['label'], array_keys($suggestion));
        }
    }

    public function testEmptyQueryReturnsEmptyArray(): void
    {
        $this->signin($this->signup());

        // une requête vide ne doit pas déclencher un LIKE '%' renvoyant tout le référentiel
        $this->assertSame([], $this->autocomplete(''));
        $this->assertSame([], $this->autocomplete('   '));
    }

    public function testGetMethodIsNotAllowed(): void
    {
        $this->signin($this->signup());

        $this->client->request('GET', '/commune/autocompletion');

        $this->assertResponseStatusCodeSame(405);
    }
}
