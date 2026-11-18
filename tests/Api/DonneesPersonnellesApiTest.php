<?php

namespace App\Tests\Api;

use App\Entity\Article;
use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Entity\User;
use App\Tests\WebTestCase;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

/**
 * Un adhérent connecté à l'API voit, comme sur le site, le nom et le pseudo des autres adhérents,
 * jamais leurs coordonnées.
 */
class DonneesPersonnellesApiTest extends WebTestCase
{
    private const TELEPHONE = '06 11 22 33 44';

    private User $adherent;
    private User $participant;
    private Evt $sortie;
    private EventParticipation $participation;

    protected function setUp(): void
    {
        parent::setUp();

        $em = $this->getContainer()->get('doctrine')->getManager();

        $this->adherent = $this->signup();
        $this->participant = $this->signup();
        $this->participant->setTel(self::TELEPHONE);

        $this->sortie = $this->createEvent($this->signup());
        $this->sortie->setStatus(Evt::STATUS_PUBLISHED_VALIDE);
        $this->participation = $this->sortie->addParticipation($this->participant, EventParticipation::ROLE_INSCRIT, EventParticipation::STATUS_VALIDE);
        $em->flush();
    }

    public function testUneParticipationNeDonnePasLEmailDuParticipant(): void
    {
        $contenu = $this->get(sprintf('/api/participation-sorties/%d', $this->participation->getId()));

        $this->assertStringContainsString('"utilisateur"', $contenu);
        $this->assertStringNotContainsString($this->participant->getEmail(), $contenu);
    }

    public function testUneSortieNeDonnePasLEmailDesParticipants(): void
    {
        $contenu = $this->get(sprintf('/api/sorties/%d', $this->sortie->getId()));

        $this->assertStringContainsString('"utilisateur"', $contenu);
        $this->assertStringNotContainsString($this->participant->getEmail(), $contenu);
    }

    public function testDemanderDAutresGroupesNeDonnePasLeTelephoneDesParticipants(): void
    {
        $contenu = $this->get(sprintf('/api/sorties/%d', $this->sortie->getId()), [
            'groups' => ['event:read', 'eventParticipation:read', 'user:read', 'user:details'],
        ]);

        $this->assertStringContainsString('"utilisateur"', $contenu);
        $this->assertStringNotContainsString(self::TELEPHONE, $contenu);
    }

    public function testDemanderDAutresGroupesNeDonnePasLeTelephoneDeLAuteurDUnArticle(): void
    {
        $article = $this->createArticle($this->participant);
        $article->setStatus(Article::STATUS_PUBLISHED);
        $this->getContainer()->get('doctrine')->getManager()->flush();

        $contenu = $this->get(sprintf('/api/articles/%d', $article->getId()), [
            'groups' => ['article:read', 'user:read', 'user:details'],
        ]);

        $this->assertSame($this->participant->getId(), json_decode($contenu, true)['user']['id']);
        $this->assertStringNotContainsString(self::TELEPHONE, $contenu);
        $this->assertStringNotContainsString($this->participant->getEmail(), $contenu);
    }

    public function testChacunRetrouveSonEmailSurSonProfil(): void
    {
        $contenu = $this->get(sprintf('/api/utilisateurs/%d', $this->adherent->getId()));

        $this->assertStringContainsString($this->adherent->getEmail(), $contenu);
    }

    private function get(string $uri, array $query = []): string
    {
        $this->client->request('GET', $uri . ($query ? '?' . http_build_query($query) : ''), [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->getContainer()->get(JWTTokenManagerInterface::class)->create($this->adherent),
        ]);
        $this->assertResponseIsSuccessful();

        return $this->client->getResponse()->getContent();
    }
}
