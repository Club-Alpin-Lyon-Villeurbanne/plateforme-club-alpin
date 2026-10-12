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
        yield 'montant non fini' => ['{"lignes":[{"id":"ligne-1","montant":1e400}]}'];
        yield 'identifiant en double' => ['{"lignes":[{"id":"ligne-1","montant":12},{"id":"ligne-1","montant":30}]}'];
    }

    /**
     * @dataProvider nombresDemesuresProvider
     */
    public function testUnNombreDemesureEstRefuseAvecSonMessage(string $type, string $details, string $message): void
    {
        $note = 'formation' === $type ? $this->creerFormation($this->encadrant) : $this->creerAchat($this->owner);

        $this->assertRefusAvec($this->patch($note->getUser(), $note, ['details' => $details]), 'details', $message);
    }

    public static function nombresDemesuresProvider(): iterable
    {
        yield 'montant non fini' => ['autre', '{"lignes":[{"id":"ligne-1","montant":1e400,"description":"Dégaines"}]}', 'Ligne 1 : le montant ne peut pas dépasser 100 000.'];
        yield 'montant au-delà de 100 000' => ['autre', '{"lignes":[{"id":"ligne-1","montant":100000.01,"description":"Dégaines"}]}', 'Ligne 1 : le montant ne peut pas dépasser 100 000.'];
        yield 'distance non finie' => ['formation', '{"lignes":[{"id":"ligne-1","nature":"stage","montant":280},{"id":"ligne-2","nature":"voiture","distance":1e400}]}', 'Ligne 2 : la distance ne peut pas dépasser 100 000.'];
    }

    public function testUnMontantDe100000EstAccepte(): void
    {
        $note = $this->creerAchat($this->owner);

        $this->assertResponseStatus(200, $this->patch($this->owner, $note, ['details' => '{"lignes":[{"id":"ligne-1","montant":100000,"description":"Minibus"}]}']));
    }

    public function testDeuxLignesNePartagentPasUnIdentifiant(): void
    {
        $note = $this->creerAchat($this->owner);

        $this->assertRefusAvec(
            $this->patch($this->owner, $note, ['details' => '{"lignes":[{"id":"ligne-1","montant":20},{"id":"ligne-2","montant":30},{"id":"ligne-1","montant":40}]}']),
            'details',
            'Ligne 3 : identifiant en double.',
        );
    }

    public function testUnBrouillonPeutGarderDesLignesIncompletes(): void
    {
        $note = $this->creerAchat($this->owner);

        $this->assertResponseStatus(200, $this->patch($this->owner, $note, ['details' => '{"lignes":[]}']));
        $this->assertResponseStatus(200, $this->patch($this->owner, $note, ['details' => '{"lignes":[{"id":"ligne-1","montant":null,"description":""}]}']));
    }
}
