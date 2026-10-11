<?php

namespace App\Tests\Api;

use App\Entity\Commission;
use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * Règles de cohérence d'une note hors sortie, vérifiées à chaque modification : champs selon
 * le type et forme des lignes.
 */
class NoteHorsSortieCoherenceTest extends ExpenseReportApiTestCase
{
    public function testUnAchatGardeSaCommissionEtSonObjet(): void
    {
        $note = $this->creerAchat($this->owner);

        $this->assertRefused($this->patch($this->owner, $note, ['commission' => null]), 'commission');
        $this->assertRefused($this->patch($this->owner, $note, ['objet' => '']), 'objet');
    }

    public function testUneCommissionMasqueeNePeutPasEtreChoisie(): void
    {
        $note = $this->creerAchat($this->owner);

        // L'API ne résout pas l'IRI d'une commission masquée : refus avant la validation.
        $this->assertResponseStatus(400, $this->patch($this->owner, $note, ['commission' => '/api/commissions/' . $this->commissionMasquee()->getId()]));
        $this->assertSame($this->commissionVisible()->getId(), $this->reload($note)->getCommission()->getId());
    }

    public function testMasquerUneCommissionNeBloquePasSesNotesEnCours(): void
    {
        $commission = $this->createCommission('Commission bientôt masquée');
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, null, $commission);
        $this->em()->find(Commission::class, $commission->getId())->setVis(false);
        $this->em()->flush();

        $this->assertResponseStatus(200, $this->patch($this->owner, $note, ['objet' => 'Cordes pour la SAE']));
    }

    public function testUnAchatNeRecoitPasDeLienVersUneFormation(): void
    {
        $note = $this->creerAchat($this->owner);

        $this->assertRefused($this->patch($this->owner, $note, ['lienFormation' => 'https://example.org/stage']), 'lienFormation');
    }

    public function testUneNoteDeSortieNeRecoitPasLesChampsHorsSortie(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::DRAFT);

        $this->assertRefused($this->patch($this->owner, $report, ['objet' => 'Dégaines pour la SAE']), 'objet');
    }

    /**
     * @dataProvider lignesMalFormeesProvider
     */
    public function testLesLignesMalFormeesSontRefusees(string $details): void
    {
        $note = $this->creerAchat($this->owner);

        $this->assertRefused($this->patch($this->owner, $note, ['details' => $details]), 'details');
    }

    public static function lignesMalFormeesProvider(): iterable
    {
        yield 'pas une liste' => ['{"lignes":"Dégaines"}'];
        yield 'forme d\'une note de sortie' => ['{"transport":{"type":"PUBLIC_TRANSPORT"},"accommodations":[],"others":[]}'];
        yield 'ligne sans identifiant' => ['{"lignes":[{"montant":12,"description":"Dégaines"}]}'];
        yield 'identifiant vide' => ['{"lignes":[{"id":"","montant":12}]}'];
        yield 'montant négatif' => ['{"lignes":[{"id":"ligne-1","montant":-12}]}'];
        yield 'montant en texte' => ['{"lignes":[{"id":"ligne-1","montant":"12"}]}'];
        yield 'montant à plus de deux décimales' => ['{"lignes":[{"id":"ligne-1","montant":12.345}]}'];
        yield 'description qui n\'est pas un texte' => ['{"lignes":[{"id":"ligne-1","montant":12,"description":12}]}'];
        yield 'nature sur un achat' => ['{"lignes":[{"id":"ligne-1","montant":12,"nature":"stage"}]}'];
    }

    public function testUnBrouillonPeutGarderDesLignesIncompletes(): void
    {
        $note = $this->creerAchat($this->owner);

        $this->assertResponseStatus(200, $this->patch($this->owner, $note, ['details' => '{"lignes":[]}']));
        $this->assertResponseStatus(200, $this->patch($this->owner, $note, ['details' => '{"lignes":[{"id":"ligne-1","montant":null,"description":""}]}']));
    }
}
