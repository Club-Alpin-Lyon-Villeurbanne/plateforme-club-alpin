<?php

namespace App\Tests\Api;

use App\Entity\ExpenseReportStatusHistory;
use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * La trésorerie valide ou renvoie une note hors sortie ; le demandeur corrige et renvoie.
 */
class NoteHorsSortieDecisionTest extends ExpenseReportApiTestCase
{
    private const MESSAGE = 'Merci de joindre la facture du magasin.';

    public function testUnGestionnaireValideLaNoteDUnAutre(): void
    {
        $note = $this->noteEnvoyee($this->owner);

        $response = $this->patch($this->manager, $note, ['status' => 'approved']);

        $this->assertResponseStatus(200, $response);
        $this->assertSame('approved', $response['body']['status']);
        $this->assertSame(ExpenseReportStatusEnum::APPROVED, $this->reload($note)->getStatus());
        $historique = $this->em()->getRepository(ExpenseReportStatusHistory::class)->findBy(['expenseReport' => $note->getId()], ['id' => 'DESC']);
        $this->assertSame(ExpenseReportStatusEnum::APPROVED, $historique[0]->getNewStatus());
        $this->assertSame($this->manager->getId(), $historique[0]->getChangedBy()->getId());
    }

    public function testUnGestionnaireNeValidePasSaPropreNoteUnAutreGestionnaireLePeut(): void
    {
        $note = $this->noteEnvoyee($this->manager);

        $this->assertRefused($this->patch($this->manager, $note, ['status' => 'approved']), 'status');
        $this->assertResponseStatus(200, $this->patch($this->otherManager, $note, ['status' => 'approved']));
    }

    /**
     * @dataProvider sansMessageProvider
     */
    public function testLeRenvoiDUneNoteHorsSortieExigeUnMessage(array $message): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::SUBMITTED);

        $this->assertRefusAvec(
            $this->patch($this->manager, $note, ['status' => 'rejected'] + $message),
            'commentaireStatut',
            'Un message au demandeur est obligatoire pour refuser la note.',
        );
        $this->assertSame(ExpenseReportStatusEnum::SUBMITTED, $this->reload($note)->getStatus());
    }

    public static function sansMessageProvider(): iterable
    {
        yield 'sans commentaire' => [[]];
        yield 'commentaire nul' => [['commentaireStatut' => null]];
        yield 'commentaire blanc' => [['commentaireStatut' => "  \n "]];
    }

    public function testLeMessageDeRenvoiEstEnregistre(): void
    {
        $note = $this->noteEnvoyee($this->owner);

        $this->assertResponseStatus(200, $this->renvoyer($this->manager, $note, self::MESSAGE));

        $renvoyee = $this->reload($note);
        $this->assertSame(ExpenseReportStatusEnum::REJECTED, $renvoyee->getStatus());
        $this->assertSame(self::MESSAGE, $renvoyee->getStatusComment());
    }

    public function testLeMessageResteFacultatifPourUneNoteDeSortie(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::SUBMITTED);

        $this->assertResponseStatus(200, $this->patch($this->manager, $report, ['status' => 'rejected']));
    }

    public function testUneNoteValideeNAdmetPlusAucuneTransitionDeLInterface(): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::APPROVED);

        $this->assertRefused($this->renvoyer($this->manager, $note), 'status');
        $this->assertRefused($this->patch($this->manager, $note, ['status' => 'submitted']), 'status');
        $this->assertRefused($this->patch($this->owner, $note, ['status' => 'submitted']), 'status');
        $this->assertFalse($this->show($this->manager, $note)['body']['peutDecider']);
    }

    /**
     * @dataProvider peutDeciderProvider
     */
    public function testPeutDecider(string $lecteur, string $demandeur, ExpenseReportStatusEnum $statut, bool $attendu): void
    {
        $note = $this->creerAchat($this->{$demandeur}, $statut);

        $lue = $this->show($this->{$lecteur}, $note);

        $this->assertResponseStatus(200, $lue);
        $this->assertSame($attendu, $lue['body']['peutDecider']);
    }

    public static function peutDeciderProvider(): iterable
    {
        yield 'gestionnaire, note envoyée par un autre' => ['manager', 'owner', ExpenseReportStatusEnum::SUBMITTED, true];
        yield 'demandeur, sa note envoyée' => ['owner', 'owner', ExpenseReportStatusEnum::SUBMITTED, false];
        yield 'gestionnaire, sa propre note' => ['manager', 'manager', ExpenseReportStatusEnum::SUBMITTED, false];
        yield 'gestionnaire, brouillon d\'un autre' => ['manager', 'owner', ExpenseReportStatusEnum::DRAFT, false];
        yield 'gestionnaire, note à corriger' => ['manager', 'owner', ExpenseReportStatusEnum::REJECTED, false];
    }

    public function testLaDerniereDecisionDonneLAuteurLaDateEtLeMessageDuRenvoi(): void
    {
        $this->nommer($this->manager, 'Alex', 'Durand');
        $note = $this->noteEnvoyee($this->owner);
        $avant = new \DateTimeImmutable('-1 minute');
        $this->assertResponseStatus(200, $this->renvoyer($this->manager, $note, self::MESSAGE));

        $decision = $this->show($this->owner, $note)['body']['derniereDecision'];

        $this->assertSame('rejected', $decision['statut']);
        $this->assertSame(['prenom' => 'Alex', 'nom' => 'DURAND'], $decision['auteur']);
        $this->assertGreaterThanOrEqual($avant, new \DateTimeImmutable($decision['date']));
        $this->assertSame(self::MESSAGE, $decision['message']);
    }

    public function testApresCorrectionEtValidationLaDerniereDecisionEstLaValidation(): void
    {
        $this->nommer($this->otherManager, 'Sam', 'Petit');
        $note = $this->noteEnvoyee($this->owner);
        $this->assertResponseStatus(200, $this->renvoyer($this->manager, $note));
        $this->assertResponseStatus(200, $this->envoyer($this->owner, $note));
        $this->assertResponseStatus(200, $this->patch($this->otherManager, $note, ['status' => 'approved']));

        $decision = $this->show($this->owner, $note)['body']['derniereDecision'];

        $this->assertSame('approved', $decision['statut']);
        $this->assertSame(['prenom' => 'Sam', 'nom' => 'PETIT'], $decision['auteur']);
        $this->assertNull($decision['message']);
    }

    public function testUnSecondRenvoiExigeUnNouveauMessage(): void
    {
        $this->nommer($this->otherManager, 'Sam', 'Petit');
        $note = $this->noteEnvoyee($this->owner);
        $this->assertResponseStatus(200, $this->renvoyer($this->manager, $note, 'Il manque un justificatif.'));
        $this->assertResponseStatus(200, $this->envoyer($this->owner, $note));
        $this->assertNull($this->reload($note)->getStatusComment());

        $this->assertRefusAvec(
            $this->patch($this->otherManager, $note, ['status' => 'rejected']),
            'commentaireStatut',
            'Un message au demandeur est obligatoire pour refuser la note.',
        );
        $this->assertResponseStatus(200, $this->renvoyer($this->otherManager, $note, 'Montant à revoir.'));

        $decision = $this->show($this->owner, $note)['body']['derniereDecision'];
        $this->assertSame(['prenom' => 'Sam', 'nom' => 'PETIT'], $decision['auteur']);
        $this->assertSame('Montant à revoir.', $decision['message']);
    }

    public function testLeMessageLaisseALaValidationEstExpose(): void
    {
        $note = $this->noteEnvoyee($this->owner);

        $this->assertResponseStatus(200, $this->patch($this->manager, $note, ['status' => 'approved', 'commentaireStatut' => 'Prévu au budget de la commission.']));

        $decision = $this->show($this->owner, $note)['body']['derniereDecision'];
        $this->assertSame('approved', $decision['statut']);
        $this->assertSame('Prévu au budget de la commission.', $decision['message']);
    }

    public function testUneNoteJamaisTraiteeNAPasDeDecision(): void
    {
        $note = $this->noteEnvoyee($this->owner);

        $this->assertNull($this->show($this->owner, $note)['body']['derniereDecision']);
    }

    public function testUneNoteDeSortieNExposeNiDecisionNiPeutDecider(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::SUBMITTED);

        $lue = $this->show($this->manager, $report);

        $this->assertArrayNotHasKey('derniereDecision', $lue['body']);
        $this->assertArrayNotHasKey('peutDecider', $lue['body']);
    }

    public function testLeDemandeurCorrigeLaCommissionEtRenvoieMemeApresLeDelai(): void
    {
        $delai = $this->configHorsSortie()['delaiEnvoiJours']['autre'];
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, self::LIGNES_ACHAT, null, new \DateTimeImmutable(sprintf('-%d days', $delai + 10)));
        $this->joindreLesJustificatifs($note, 'ligne-1', 'ligne-2');
        // Envoyée à temps, il y a longtemps.
        $this->em()->getConnection()->executeStatement('UPDATE expense_report SET status = ?, refund_required = 1 WHERE id = ?', ['submitted', $note->getId()]);
        $this->assertResponseStatus(200, $this->renvoyer($this->manager, $note, 'Mauvaise commission.'));
        $bonneCommission = $this->commissionVisible(1);

        $this->assertResponseStatus(200, $this->patch($this->owner, $note, ['commission' => '/api/commissions/' . $bonneCommission->getId()]));
        $this->assertResponseStatus(200, $this->envoyer($this->owner, $note));

        $renvoyee = $this->reload($note);
        $this->assertSame(ExpenseReportStatusEnum::SUBMITTED, $renvoyee->getStatus());
        $this->assertSame($bonneCommission->getId(), $renvoyee->getCommission()?->getId());
    }
}
