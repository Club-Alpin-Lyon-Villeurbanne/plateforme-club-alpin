<?php

namespace App\Tests\Controller;

use App\Entity\Evt;
use App\Entity\User;
use App\Tests\WebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ProfilControllerTest extends WebTestCase
{
    public function testDisplayAlertes()
    {
        $user = $this->signup();
        $this->signin($user);

        $this->client->request('GET', '/profil/alertes');
        $this->assertResponseStatusCodeSame(200);
    }

    public function testReglageDesDemandesInscriptionCocheParDefaut(): void
    {
        $user = $this->signup();
        $this->signin($user);

        $this->client->request('GET', '/profil/alertes');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorExists('#demandes-inscription input#toutes-demandes-inscription[name="toutes-demandes-inscription"][checked]');
    }

    #[DataProvider('provideReglageDemandesInscription')]
    public function testEnregistrerLeReglageDesDemandesInscription(array $champ, bool $attendu): void
    {
        $user = $this->signup();
        $user->setAlertToutesDemandesInscription(!$attendu);
        $this->getContainer()->get('doctrine')->getManager()->flush();
        $this->signin($user);

        $this->client->request('POST', '/profil/alertes', [
            'csrf_token' => $this->generateCsrfToken($this->client, 'profil_alertes_update'),
            'sortie-prefix-input' => '[Sortie]',
            'article-prefix-input' => '[Article]',
        ] + $champ);
        $this->assertResponseRedirects('/profil/alertes');

        $this->client->followRedirect();
        $this->assertSelectorExists('#toutes-demandes-inscription' . ($attendu ? '[checked]' : ':not([checked])'));
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->clear();
        $user = $em->getRepository(User::class)->find($user->getId());
        $this->assertSame($attendu, $user->getAlertToutesDemandesInscription());
    }

    public static function provideReglageDemandesInscription(): iterable
    {
        yield 'case cochée' => [['toutes-demandes-inscription' => '1'], true];
        yield 'case décochée' => [[], false];
    }

    public function testDontDisplayAlertesWhenNotLoggedIn()
    {
        $this->client->request('GET', '/profil/alertes');
        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects('http://localhost/login');
    }

    #[DataProvider('provideSortiesPages')]
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

    #[DataProvider('provideSortiesPages')]
    public function testDisplaySortiesPagesUnauthenticated(string $page)
    {
        $this->client->request('GET', $page);
        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects('http://localhost/login');
    }

    public static function provideSortiesPages()
    {
        yield ['/profil/sorties/next'];
        yield ['/profil/sorties/self'];
        yield ['/profil/sorties/prev'];
    }
}
