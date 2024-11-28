<?php

namespace App\Tests\Controller;

use App\Entity\Evt;
use App\Tests\WebTestCase;

class ProfilControllerTest extends WebTestCase
{
    public function testDisplayAlertes()
    {
        $user = $this->signup();
        $this->signin($user);

        $this->client->request('GET', '/profil/alertes');
        $this->assertResponseStatusCodeSame(200);
    }

    public function testDontDisplayAlertesWhenNotLoggedIn()
    {
        $this->client->request('GET', '/profil/alertes');
        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects('http://localhost/login');
    }

    /** @dataProvider provideSortiesPages */
    public function testDisplaySortiesPages(string $page)
    {
        $user = $this->signup();
        $this->signin($user);

        $event = $this->createEvent($user);
        $event2 = $this->createEvent($user);

        $event->setStatus(Evt::STATUS_PUBLISHED_VALIDE);
        $event2->setStatus(Evt::STATUS_PUBLISHED_VALIDE);

        $this->getContainer()->get('doctrine')->getManager()->flush();
        $this->client->request('GET', $page);
        $this->assertResponseStatusCodeSame(200);
    }

    /** @dataProvider provideSortiesPages */
    public function testDisplaySortiesPagesUnauthenticated(string $page)
    {
        $this->client->request('GET', $page);
        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects('http://localhost/login');
    }

    public function provideSortiesPages()
    {
        yield ['/profil/sorties/next'];
        yield ['/profil/sorties/self'];
        yield ['/profil/sorties/prev'];
    }
}
