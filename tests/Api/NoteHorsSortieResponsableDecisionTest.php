<?php

namespace App\Tests\Api;

use App\Entity\ExpenseReportStatusHistory;
use App\Entity\User;
use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * Les responsables de la commission d'une note hors sortie la valident ou la renvoient ; personne
 * d'autre que les valideurs et la trésorerie n'y touche.
 */
class NoteHorsSortieResponsableDecisionTest extends ExpenseReportApiTestCase
{
    private const MESSAGE = 'Merci de joindre la facture du magasin.';

    public function testLeResponsableValideUneNoteDeSaCommission(): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $response = $this->patch($this->responsable, $note, ['status' => 'approved']);

        $this->assertResponseStatus(200, $response);
        $this->assertSame(ExpenseReportStatusEnum::APPROVED, $this->reload($note)->getStatus());
        $historique = $this->em()->getRepository(ExpenseReportStatusHistory::class)->findBy(['expenseReport' => $note->getId()], ['id' => 'DESC']);
        $this->assertSame(ExpenseReportStatusEnum::APPROVED, $historique[0]->getNewStatus());
        $this->assertSame($this->responsable->getId(), $historique[0]->getChangedBy()->getId());
    }

    public function testLeCoResponsableNePeutPlusAgirSurUneNoteValidee(): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);
        $this->assertResponseStatus(200, $this->patch($this->responsable, $note, ['status' => 'approved']));

        $this->assertRefused($this->patch($this->coResponsable, $note, ['status' => 'rejected', 'commentaireStatut' => self::MESSAGE]), 'status');
        $this->assertRefused($this->patch($this->coResponsable, $note, ['status' => 'submitted']), 'status');
        $this->assertSame(ExpenseReportStatusEnum::APPROVED, $this->reload($note)->getStatus());
    }

    /**
     * @dataProvider sansDroitProvider
     */
    public function testSansEtreValideurOnNeLitNiNeTraiteLaNote(string $acteur): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $this->assertResponseStatus(403, $this->show($this->{$acteur}, $note));
        $this->assertResponseStatus(403, $this->patch($this->{$acteur}, $note, ['status' => 'approved']));
        $this->assertResponseStatus(403, $this->renvoyer($this->{$acteur}, $note));
        $this->assertSame(ExpenseReportStatusEnum::SUBMITTED, $this->reload($note)->getStatus());
    }

    public static function sansDroitProvider(): iterable
    {
        yield 'responsable d\'une autre commission' => ['responsableAutreCommission'];
        yield 'encadrant de la commission' => ['encadrant'];
        yield 'adhérent sans rôle' => ['stranger'];
    }

    public function testUnResponsableQuiDeposeSaNoteNAQueLesDroitsDuProprietaire(): void
    {
        $note = $this->noteEnvoyee($this->responsable, commission: $this->commissionDeTest);

        $this->assertRefused($this->patch($this->responsable, $note, ['status' => 'approved']), 'status');
        $this->assertRefusSurLeStatut($this->renvoyer($this->responsable, $note));
        $this->assertResponseStatus(200, $this->patch($this->coResponsable, $note, ['status' => 'approved']));
    }

    public function testUnGestionnaireValideLaNoteDUnResponsable(): void
    {
        $note = $this->noteEnvoyee($this->responsable, commission: $this->commissionDeTest);

        $this->assertResponseStatus(200, $this->patch($this->manager, $note, ['status' => 'approved']));
    }

    /**
     * @dataProvider sansMessageProvider
     */
    public function testLeRenvoiParUnResponsableExigeUnMessage(array $message): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $this->assertRefusAvec(
            $this->patch($this->responsable, $note, ['status' => 'rejected'] + $message),
            'commentaireStatut',
            'Un message au demandeur est obligatoire pour lui renvoyer la note.',
        );
        $this->assertSame(ExpenseReportStatusEnum::SUBMITTED, $this->reload($note)->getStatus());
    }

    public static function sansMessageProvider(): iterable
    {
        yield 'sans commentaire' => [[]];
        yield 'commentaire blanc' => [['commentaireStatut' => "  \n "]];
    }

    public function testLeMessageDuRenvoiParUnResponsableEstEnregistre(): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $this->assertResponseStatus(200, $this->renvoyer($this->responsable, $note, self::MESSAGE));

        $renvoyee = $this->reload($note);
        $this->assertSame(ExpenseReportStatusEnum::REJECTED, $renvoyee->getStatus());
        $this->assertSame(self::MESSAGE, $renvoyee->getStatusComment());
        $decision = $this->show($this->adherent, $note)['body']['derniereDecision'];
        $this->assertSame(['rejected', 'Alex', 'DURAND', self::MESSAGE], [$decision['statut'], $decision['auteur']['prenom'], $decision['auteur']['nom'], $decision['message']]);
    }

    public function testLeProprietaireCorrigeLaCommissionEtEnvoieANouveau(): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);
        $this->assertResponseStatus(200, $this->renvoyer($this->responsable, $note, 'Mauvaise commission.'));

        $response = $this->envoyer($this->adherent, $note, ['commission' => '/api/commissions/' . $this->autreCommission->getId()]);

        $this->assertResponseStatus(200, $response);
        $corrigee = $this->reload($note);
        $this->assertSame(ExpenseReportStatusEnum::SUBMITTED, $corrigee->getStatus());
        $this->assertSame($this->autreCommission->getId(), $corrigee->getCommission()->getId());
        $this->assertTrue($this->show($this->responsableAutreCommission, $note)['body']['peutDecider']);
        $this->assertResponseStatus(403, $this->show($this->responsable, $note));
    }

    public function testLeResponsableNeComptabilisePas(): void
    {
        $note = $this->creerAchat($this->adherent, ExpenseReportStatusEnum::APPROVED, self::LIGNES_ACHAT, $this->commissionDeTest);

        $this->assertRefused($this->patch($this->responsable, $note, ['status' => 'accounted']), 'status');
    }

    public function testLeResponsableNEnvoiePasLaNoteDUnAutre(): void
    {
        $note = $this->creerAchat($this->adherent, ExpenseReportStatusEnum::DRAFT, self::LIGNES_ACHAT, $this->commissionDeTest);

        $this->assertRefusSurLeStatut($this->patch($this->responsable, $note, ['status' => 'submitted']));
        $this->assertSame(ExpenseReportStatusEnum::DRAFT, $this->reload($note)->getStatus());
    }

    /**
     * @dataProvider champsDuDemandeurProvider
     */
    public function testLeResponsableNeModifieQueLeCommentaireDeStatut(string $champ, mixed $valeur): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);
        if ('commission' === $champ) {
            $valeur = '/api/commissions/' . $this->autreCommission->getId();
        }

        $response = $this->patch($this->responsable, $note, ['status' => 'approved', $champ => $valeur]);

        // Une commission changée refuse aussi la transition : le responsable n'est pas valideur de l'autre commission.
        $this->assertRefusSur($response, $champ);
        $this->assertSame(ExpenseReportStatusEnum::SUBMITTED, $this->reload($note)->getStatus());
    }

    public static function champsDuDemandeurProvider(): iterable
    {
        yield 'lignes' => ['details', '{"lignes":[{"id":"ligne-1","montant":5000,"description":"Dégaines (x6)"}]}'];
        yield 'objet' => ['objet', 'Autre chose'];
        yield 'commission' => ['commission', null];
        yield 'date' => ['dateDepense', '2026-01-01'];
        yield 'mot pour le responsable' => ['motPourResponsable', 'Ajouté par le responsable'];
    }

    public function testLeResponsableNEcritLeCommentaireQueDansLaRequeteDeSaTransition(): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $this->assertRefused($this->patch($this->responsable, $note, ['commentaireStatut' => self::MESSAGE]), 'commentaireStatut');
        $this->assertResponseStatus(200, $this->patch($this->responsable, $note, ['status' => 'approved', 'commentaireStatut' => 'Prévu au budget.']));
    }

    public function testUnGestionnaireResponsableGardeLesDroitsDuGestionnaire(): void
    {
        $this->rendreResponsable($this->manager, $this->commissionDeTest);
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $this->assertResponseStatus(200, $this->patch($this->manager, $note, ['status' => 'approved']));
        $this->assertResponseStatus(200, $this->patch($this->manager, $note, ['status' => 'accounted']));
    }

    public function testPeutDeciderSuitLeDroitDuResponsable(): void
    {
        $deLAdherent = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);
        $duResponsable = $this->noteEnvoyee($this->responsable, commission: $this->commissionDeTest);

        $this->assertTrue($this->show($this->responsable, $deLAdherent)['body']['peutDecider']);
        $this->assertFalse($this->show($this->responsable, $duResponsable)['body']['peutDecider']);
        $this->assertTrue($this->show($this->coResponsable, $duResponsable)['body']['peutDecider']);

        $this->assertResponseStatus(200, $this->patch($this->responsable, $deLAdherent, ['status' => 'approved']));
        $this->assertFalse($this->show($this->coResponsable, $deLAdherent)['body']['peutDecider']);
    }

    public function testUnResponsableQuiDoitRenouvelerNeTraitePlusLesNotes(): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);
        $this->em()->find(User::class, $this->responsable->getId())->setDoitRenouveler(true);
        $this->em()->flush();

        $this->assertResponseStatus(403, $this->patch($this->responsable, $note, ['status' => 'approved']));
        $this->assertResponseStatus(200, $this->patch($this->coResponsable, $note, ['status' => 'approved']));
    }

    public function testLeResponsableNIntervientPasSurUneNoteDeSortie(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::SUBMITTED);
        $this->rendreResponsable($this->responsable, $report->getEvent()->getCommission());

        $this->assertResponseStatus(403, $this->show($this->responsable, $report));
        $this->assertResponseStatus(403, $this->patch($this->responsable, $report, ['status' => 'approved']));
        $this->assertResponseStatus(200, $this->patch($this->manager, $report, ['status' => 'approved']));
    }

    /**
     * Refus (422) qui porte au moins sur le statut ; d'autres règles peuvent refuser la même requête.
     */
    private function assertRefusSurLeStatut(array $response): void
    {
        $this->assertRefusSur($response, 'status');
    }

    private function assertRefusSur(array $response, string $champ): void
    {
        $this->assertResponseStatus(422, $response);
        $this->assertContains($champ, array_column($response['body']['violations'], 'propertyPath'));
    }
}
