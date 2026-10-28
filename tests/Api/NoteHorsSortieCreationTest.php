<?php

namespace App\Tests\Api;

use App\Entity\ExpenseReport;

/**
 * Création d'une note hors sortie (POST /api/notes-de-frais avec un type) : règles de cohérence
 * et contrôles du moment « création ».
 */
class NoteHorsSortieCreationTest extends ExpenseReportApiTestCase
{
    public function testUnAchatEstCreeEnBrouillonSansSortieNiChoixDuRemboursement(): void
    {
        $commission = $this->commissionVisible();
        $date = (new \DateTimeImmutable('-3 days'))->format('Y-m-d');

        $response = $this->create($this->owner, $this->achat(['dateDepense' => $date]));

        $this->assertResponseStatus(201, $response);
        $this->assertSame('draft', $response['body']['status']);
        $this->assertSame('autre', $response['body']['type']);
        $this->assertNull($response['body']['sortie']);
        $this->assertNull($response['body']['refundRequired']);
        $this->assertSame($commission->getId(), $response['body']['commission']['id']);
        $this->assertSame('Dégaines pour la SAE', $response['body']['objet']);
        $this->assertSame($date, $response['body']['dateDepense']);
        $this->assertSame($this->owner->getId(), $response['body']['utilisateur']['id']);

        $note = $this->em()->find(ExpenseReport::class, $response['body']['id']);
        $this->assertNull($note->getEvent());
        $this->assertNull($note->isRefundRequired());
    }

    /**
     * @dataProvider champObligatoireProvider
     */
    public function testLaCreationEstRefuseeSiUnChampObligatoireManque(string $champEnvoye, string $champRefuse): void
    {
        $this->assertRefused($this->create($this->owner, $this->achat([$champEnvoye => null])), $champRefuse);
    }

    public static function champObligatoireProvider(): iterable
    {
        yield 'sans commission' => ['commissionId', 'commission'];
        yield 'sans objet' => ['objet', 'objet'];
        yield 'sans date' => ['dateDepense', 'dateDepense'];
    }

    public function testUnObjetFaitDEspacesEstRefuse(): void
    {
        $this->assertRefused($this->create($this->owner, $this->achat(['objet' => '   '])), 'objet');
    }

    public function testUnObjetTropLongEstRefuseEnLeNommant(): void
    {
        $this->assertRefusAvec($this->create($this->owner, $this->achat(['objet' => str_repeat('a', 256)])), 'objet', 'L\'objet doit faire au plus 255 caractères.');
        $this->assertResponseStatus(201, $this->create($this->owner, $this->achat(['objet' => str_repeat('a', 255)])));
    }

    public function testUneCommissionInconnueEstRefusee(): void
    {
        $this->assertRefused($this->create($this->owner, $this->achat(['commissionId' => 999999999])), 'commission');
    }

    public function testUneCommissionMasqueeEstRefusee(): void
    {
        $this->assertRefused($this->create($this->owner, $this->achat(['commissionId' => $this->commissionMasquee()->getId()])), 'commission');
    }

    public function testUnLienVersUneFormationEstRefusePourUnAchat(): void
    {
        $this->assertRefused($this->create($this->owner, $this->achat(['lienFormation' => 'https://example.org/stage'])), 'lienFormation');
    }

    public function testUneDateFutureEstRefusee(): void
    {
        $response = $this->create($this->owner, $this->achat(['dateDepense' => (new \DateTimeImmutable('+1 day'))->format('Y-m-d')]));

        $this->assertRefused($response, 'dateDepense');
        $this->assertSame(['La date ne peut pas être dans le futur.'], $this->messages($response));
    }

    public function testUnAchatDuJourEstAccepte(): void
    {
        $this->assertResponseStatus(201, $this->create($this->owner, $this->achat(['dateDepense' => (new \DateTimeImmutable('today'))->format('Y-m-d')])));
    }

    public function testUnAchatPeutEtreDeclareJusquAuDernierJourDuDelai(): void
    {
        $date = (new \DateTimeImmutable('today'))->modify(sprintf('-%d days', $this->delaiAchat()));

        $this->assertResponseStatus(201, $this->create($this->owner, $this->achat(['dateDepense' => $date->format('Y-m-d')])));
    }

    public function testUnAchatPlusAncienQueLeDelaiEstRefuse(): void
    {
        $delai = $this->delaiAchat();
        $date = (new \DateTimeImmutable('today'))->modify(sprintf('-%d days', $delai + 1));

        $response = $this->create($this->owner, $this->achat(['dateDepense' => $date->format('Y-m-d')]));

        $this->assertRefused($response, 'dateDepense');
        $this->assertSame([sprintf('Le délai de %d jours après la dépense est dépassé.', $delai)], $this->messages($response));
    }

    public function testUneDateMalFormeeEstRefusee(): void
    {
        $this->assertRefused($this->create($this->owner, $this->achat(['dateDepense' => '24/09/2026'])), 'dateDepense');
    }

    /**
     * @dataProvider typeRefuseProvider
     */
    public function testUnTypeAutreQueFormationOuAchatEstRefuse(string $type): void
    {
        $this->assertRefused($this->create($this->owner, $this->achat(['type' => $type])), 'type');
    }

    public static function typeRefuseProvider(): iterable
    {
        yield 'sortie sans sortie' => ['sortie'];
        yield 'inconnu' => ['voyage'];
    }

    public function testUneNoteHorsSortieNeCitePasDeSortie(): void
    {
        $event = $this->createEvent($this->owner);

        $this->assertRefused($this->create($this->owner, $this->achat(['eventId' => $event->getId()])), 'eventId');
    }

    public function testUneNoteDeSortieSeCreeToujoursParSaSortie(): void
    {
        $event = $this->createEvent($this->owner);

        $response = $this->create($this->owner, ['eventId' => $event->getId()]);

        $this->assertResponseStatus(201, $response);
        $this->assertSame('sortie', $response['body']['type']);
        $this->assertSame($event->getId(), $response['body']['sortie']['id']);
        $this->assertFalse($response['body']['refundRequired']);
        $this->assertNull($response['body']['commission']);
        $this->assertNull($response['body']['objet']);
    }

    public function testUnDemandeurPeutAvoirPlusieursNotesHorsSortie(): void
    {
        $premiere = $this->create($this->owner, $this->achat());
        $seconde = $this->create($this->owner, $this->achat(['objet' => 'Nom de domaine du site']));

        $this->assertResponseStatus(201, $premiere);
        $this->assertResponseStatus(201, $seconde);
        $this->assertNotSame($premiere['body']['id'], $seconde['body']['id']);
    }

    public function testUneNoteHorsSortieNeSeClonePas(): void
    {
        $note = $this->em()->find(ExpenseReport::class, $this->create($this->owner, $this->achat())['body']['id']);

        $response = $this->cloneReport($this->owner, $note);

        $this->assertResponseStatus(403, $response);
        $this->assertStringContainsString('hors sortie', $response['body']['detail'] ?? '');
        $this->assertCount(1, $this->em()->getRepository(ExpenseReport::class)->findBy(['user' => $this->owner->getId()]));
    }

    private function delaiAchat(): int
    {
        return $this->getContainer()->getParameter('expense_report')['horsSortie']['delaiEnvoiJours']['autre'];
    }

    /**
     * @return list<string>
     */
    private function messages(array $response): array
    {
        return array_column($response['body']['violations'] ?? [], 'message');
    }
}
