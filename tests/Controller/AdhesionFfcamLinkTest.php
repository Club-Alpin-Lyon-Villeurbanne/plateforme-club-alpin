<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCase;

/**
 * Lien « Adhérer au club » du header (visiteurs non connectés).
 */
class AdhesionFfcamLinkTest extends WebTestCase
{
    private const LINK_TEXT = 'Adhérer au club';

    public function testVisitorSeesLinkToClubAdhesionPageWhenHchkIsSet(): void
    {
        static::getContainer()->get('twig')->addGlobal('ffcam_adhesion_hchk', 'test-hchk');

        $crawler = $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $link = $crawler->selectLink(self::LINK_TEXT);
        $this->assertCount(1, $link);

        $href = $link->attr('href');
        $baseUrl = static::getContainer()->get('twig')->getGlobals()['ffcam_adhesion_base_url'];
        $idClub = (string) static::getContainer()->getParameter('club_cafnum_prefix');

        $this->assertStringStartsWith($baseUrl . '?', $href);
        parse_str((string) parse_url($href, \PHP_URL_QUERY), $query);
        $this->assertSame(['IDCLUB' => $idClub, 'Hchk' => 'test-hchk'], $query);
    }

    public function testVisitorSeesLinkToClubSearchWhenHchkIsEmpty(): void
    {
        static::getContainer()->get('twig')->addGlobal('ffcam_adhesion_hchk', '');

        $crawler = $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $link = $crawler->selectLink(self::LINK_TEXT);
        $this->assertCount(1, $link);

        $searchUrl = static::getContainer()->get('twig')->getGlobals()['ffcam_search_url'];
        $this->assertSame($searchUrl, $link->attr('href'));
    }

    public function testLoggedInUserDoesNotSeeLink(): void
    {
        $user = $this->signup();
        $this->signin($user);

        $crawler = $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->selectLink(self::LINK_TEXT));
    }
}
