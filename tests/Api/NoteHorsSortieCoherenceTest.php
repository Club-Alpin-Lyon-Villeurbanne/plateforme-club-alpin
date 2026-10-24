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

    /**
     * Le formulaire renvoie toute la note, sans la commission quand elle n'a pas changé : l'IRI
     * d'une commission masquée ne se résout plus.
     */
    public function testLeFormulaireEnregistreEtEnvoieUneNoteDontLaCommissionAEteMasquee(): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, self::LIGNES_ACHAT, $this->commissionDeTest);
        $this->joindreLesJustificatifs($note, 'ligne-1', 'ligne-2');
        $this->em()->find(Commission::class, $this->commissionDeTest->getId())->setVis(false);
        $this->em()->flush();
        $corps = [
            'objet' => 'Dégaines pour la SAE',
            'dateDepense' => (new \DateTimeImmutable('-3 days'))->format('Y-m-d'),
            'motPourResponsable' => null,
            'details' => self::LIGNES_ACHAT,
            'refundRequired' => true,
            'version' => $this->reload($note)->getVersion(),
        ];

        $enregistree = $this->patch($this->owner, $note, $corps);
        $this->assertResponseStatus(200, $enregistree);
        $envoyee = $this->patch($this->owner, $note, ['status' => 'submitted', 'version' => $enregistree['body']['version']] + $corps);
        $this->assertResponseStatus(200, $envoyee);
        $this->assertSame($this->commissionDeTest->getId(), $envoyee['body']['commission']['id']);
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
        yield 'montant non fini' => ['autre', '{"lignes":[{"id":"ligne-1","montant":1e400,"description":"Dégaines"}]}', 'Dépense 1 : le montant ne peut pas dépasser 100 000.'];
        yield 'montant au-delà de 100 000' => ['autre', '{"lignes":[{"id":"ligne-1","montant":100000.01,"description":"Dégaines"}]}', 'Dépense 1 : le montant ne peut pas dépasser 100 000.'];
        yield 'distance non finie' => ['formation', '{"lignes":[{"id":"ligne-1","nature":"stage","montant":280},{"id":"ligne-2","nature":"voiture","distance":1e400}]}', 'Dépense 2 : la distance ne peut pas dépasser 100 000.'];
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
            'Dépense 3 : identifiant en double.',
        );
    }

    public function testUnBrouillonPeutGarderDesLignesIncompletes(): void
    {
        $note = $this->creerAchat($this->owner);

        $this->assertResponseStatus(200, $this->patch($this->owner, $note, ['details' => '{"lignes":[]}']));
        $this->assertResponseStatus(200, $this->patch($this->owner, $note, ['details' => '{"lignes":[{"id":"ligne-1","montant":null,"description":""}]}']));
    }
}
