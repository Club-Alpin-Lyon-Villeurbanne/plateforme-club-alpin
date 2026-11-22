<?php

namespace App\Tests\Api;

use App\Entity\Article;
use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Entity\User;
use App\Tests\WebTestCase;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

/**
 * Un adhérent connecté à l'API voit, comme sur la fiche sortie, le pseudo des participants acceptés,
 * jamais leurs coordonnées ni les inscriptions refusées ou en attente.
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
        $this->participant->setLastname('Lambertin');
        $this->participant->setNickname('pseudo-participant');

        $this->sortie = $this->createEvent($this->signup());
        $this->sortie->setStatus(Evt::STATUS_PUBLISHED_VALIDE);
        $this->participation = $this->sortie->addParticipation($this->participant, EventParticipation::ROLE_INSCRIT, EventParticipation::STATUS_VALIDE);
        $em->flush();
    }

    public function testUneSortieNeDonneQueLePseudoDesParticipants(): void
    {
        $contenu = $this->get(sprintf('/api/sorties/%d', $this->sortie->getId()));

        $this->assertStringContainsString('pseudo-participant', $contenu);
        $this->assertStringNotContainsString($this->participant->getEmail(), $contenu);
        $this->assertStringNotContainsStringIgnoringCase('Lambertin', $contenu);
    }

    public function testUneSortieNeMontrePasLesInscriptionsRefuseesOuEnAttente(): void
    {
        $refuse = $this->signup();
        $refuse->setNickname('pseudo-refuse');
        $enAttente = $this->signup();
        $enAttente->setNickname('pseudo-en-attente');
        $this->sortie->addParticipation($refuse, EventParticipation::ROLE_INSCRIT, EventParticipation::STATUS_REFUSE);
        $this->sortie->addParticipation($enAttente, EventParticipation::ROLE_INSCRIT, EventParticipation::STATUS_NON_CONFIRME);
        $this->getContainer()->get('doctrine')->getManager()->flush();

        $contenu = $this->get(sprintf('/api/sorties/%d', $this->sortie->getId()));

        $this->assertStringContainsString('pseudo-participant', $contenu);
        $this->assertStringNotContainsString('pseudo-refuse', $contenu);
        $this->assertStringNotContainsString('pseudo-en-attente', $contenu);
    }

    public function testDemanderDAutresGroupesNeDonnePasLesCoordonneesDesParticipants(): void
    {
        $contenu = $this->get(sprintf('/api/sorties/%d', $this->sortie->getId()), [
            'groups' => ['event:read', 'eventParticipation:read', 'user:read', 'user:details'],
        ]);

        $this->assertStringContainsString('"utilisateur"', $contenu);
        $this->assertStringNotContainsString(self::TELEPHONE, $contenu);
        $this->assertStringNotContainsStringIgnoringCase('Lambertin', $contenu);
    }

    public function testOnNePeutPasListerLesSortiesDUnAdherent(): void
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $autreSortie = $this->createEvent($this->signup());
        $autreSortie->setCommission($this->sortie->getCommission());
        $autreSortie->setStatus(Evt::STATUS_PUBLISHED_VALIDE);
        $em->flush();

        $contenu = $this->get('/api/sorties', [
            'commission' => $this->sortie->getCommission()->getId(),
            'participations.user.id' => $this->participant->getId(),
        ]);

        $this->assertContains($autreSortie->getId(), array_column(json_decode($contenu, true)['data'], 'id'));
    }

    public function testLaListeDesSortiesResteSurUnePage(): void
    {
        $contenu = $this->get('/api/sorties', ['pagination' => 'false']);

        $this->assertArrayHasKey('meta', json_decode($contenu, true));
    }

    public function testLesParticipationsNeSontPasConsultablesSeules(): void
    {
        $this->request('GET', sprintf('/api/participation-sorties/%d', $this->participation->getId()));
        $this->assertResponseStatusCodeSame(404);

        $this->request('GET', '/api/participation-sorties');
        $this->assertResponseStatusCodeSame(404);
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

    public function testDemanderDAutresGroupesNePermetPasDeModifierSonIdentite(): void
    {
        $this->request('PATCH', sprintf('/api/utilisateurs/%d?%s', $this->adherent->getId(), http_build_query(['groups' => ['user:read', 'user:details']])), [
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ], json_encode(['nom' => 'Autrenom', 'numeroLicence' => '999999999999']));

        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->clear();
        $adherent = $em->getRepository(User::class)->find($this->adherent->getId());
        $this->assertSame('NOM', $adherent->getLastname());
        $this->assertNotSame('999999999999', $adherent->getCafnum());
    }

    private function get(string $uri, array $query = []): string
    {
        $this->request('GET', $uri . ($query ? '?' . http_build_query($query) : ''));
        $this->assertResponseIsSuccessful();

        return $this->client->getResponse()->getContent();
    }

    private function request(string $methode, string $uri, array $entetes = [], ?string $corps = null): void
    {
        $this->client->request($methode, $uri, [], [], $entetes + [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->getContainer()->get(JWTTokenManagerInterface::class)->create($this->adherent),
        ], $corps);
    }
}
