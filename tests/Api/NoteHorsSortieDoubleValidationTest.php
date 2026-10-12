<?php

namespace App\Tests\Api;

use App\Entity\ExpenseReport;
use App\Entity\ExpenseReportStatusHistory;
use App\Entity\User;
use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * Double validation d'une note hors sortie : le responsable la pré-valide sur le site, puis la
 * trésorerie la valide ou la refuse par l'API (compta-club), et la comptabilise.
 */
class NoteHorsSortieDoubleValidationTest extends ExpenseReportApiTestCase
{
    private const MESSAGE = 'Merci de joindre la facture du magasin.';
    private const MESSAGE_OBLIGATOIRE = 'Un message au demandeur est obligatoire pour refuser la note.';
    private const DEJA_TRAITEE = 'Cette note a déjà été traitée.';

    private const OK = 'acceptée';
    private const SANS_EFFET = 'sans effet';
    private const DEJA = 'déjà traitée';
    private const NON = 'refusée';
    private const INTERDITE = 'interdite';

    private const CIBLES = ['draft', 'submitted', 'preapproved', 'approved', 'rejected', 'accounted'];

    public function testLaTresorerieValideUneNotePreValidee(): void
    {
        $note = $this->notePreValidee();

        $this->assertResponseStatus(200, $this->patch($this->manager, $note, ['status' => 'approved']));

        $this->assertSame(ExpenseReportStatusEnum::APPROVED, $this->reload($note)->getStatus());
        $this->assertSame($this->manager->getId(), $this->derniereLigneDHistorique($note)->getChangedBy()->getId());
    }

    public function testLaTresorerieRefuseUneNotePreValideeAvecUnMessage(): void
    {
        $note = $this->notePreValidee();

        $this->assertResponseStatus(200, $this->renvoyer($this->manager, $note, self::MESSAGE));

        $refusee = $this->reload($note);
        $this->assertSame(ExpenseReportStatusEnum::REJECTED, $refusee->getStatus());
        $this->assertSame(self::MESSAGE, $refusee->getStatusComment());
    }

    /**
     * @dataProvider sansMessageProvider
     */
    public function testLeRefusParLaTresorerieExigeUnMessage(array $message): void
    {
        $note = $this->notePreValidee();

        $this->assertRefusAvec($this->patch($this->manager, $note, ['status' => 'rejected'] + $message), 'commentaireStatut', self::MESSAGE_OBLIGATOIRE);
        $this->assertSame(ExpenseReportStatusEnum::PREAPPROVED, $this->reload($note)->getStatus());
    }

    public static function sansMessageProvider(): iterable
    {
        yield 'sans commentaire' => [[]];
        yield 'commentaire nul' => [['commentaireStatut' => null]];
        yield 'commentaire blanc' => [['commentaireStatut' => "  \n "]];
    }

    public function testLeMotLaisseALaPreValidationNeSertPasDeMessageDeRefus(): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);
        $this->assertResponseStatus(200, $this->patch($this->responsable, $note, ['status' => 'preapproved', 'commentaireStatut' => 'Prévu au budget de la commission.']));

        $this->assertRefusAvec($this->patch($this->manager, $note, ['status' => 'rejected']), 'commentaireStatut', self::MESSAGE_OBLIGATOIRE);
        $this->assertSame(ExpenseReportStatusEnum::PREAPPROVED, $this->reload($note)->getStatus());

        $this->assertResponseStatus(200, $this->renvoyer($this->manager, $note, self::MESSAGE));
        $this->assertSame(self::MESSAGE, $this->show($this->adherent, $note)['body']['derniereDecision']['message']);
    }

    /**
     * @dataProvider decisionsDeLaTresorerieProvider
     */
    public function testLaTresorerieNeDecidePasAvantLeResponsable(array $decision): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $this->assertRefused($this->patch($this->manager, $note, $decision), 'status');
        $this->assertSame(ExpenseReportStatusEnum::SUBMITTED, $this->reload($note)->getStatus());
        $this->assertCount(0, $this->getMailerMessages());
    }

    public static function decisionsDeLaTresorerieProvider(): iterable
    {
        yield 'validation' => [['status' => 'approved']];
        yield 'refus' => [['status' => 'rejected', 'commentaireStatut' => self::MESSAGE]];
    }

    /**
     * @dataProvider decisionsDeLaTresorerieProvider
     */
    public function testSansValideurLaTresorerieDecideDesLEnvoi(array $decision): void
    {
        $note = $this->noteEnvoyee($this->adherent);

        $this->assertResponseStatus(200, $this->patch($this->manager, $note, $decision));
        $this->assertSame($decision['status'], $this->reload($note)->getStatus()->value);
    }

    /**
     * @dataProvider decisionsDuResponsableProvider
     */
    public function testLeResponsableNeDecidePlusSurUneNotePreValidee(string $acteur, array $decision): void
    {
        $note = $this->notePreValidee();

        $this->assertRefusAvec($this->patch($this->{$acteur}, $note, $decision), 'status', self::DEJA_TRAITEE);
        $this->assertSame(ExpenseReportStatusEnum::PREAPPROVED, $this->reload($note)->getStatus());
        $this->assertCount(0, $this->getMailerMessages());
    }

    public static function decisionsDuResponsableProvider(): iterable
    {
        foreach (['responsable' => 'responsable', 'co-responsable' => 'coResponsable'] as $nom => $acteur) {
            yield $nom . ', validation' => [$acteur, ['status' => 'preapproved']];
            yield $nom . ', refus' => [$acteur, ['status' => 'rejected', 'commentaireStatut' => self::MESSAGE]];
            yield $nom . ', validation à la place de la trésorerie' => [$acteur, ['status' => 'approved']];
        }
    }

    public function testUneNoteRefuseeParLaTresorerieRepartChezLeResponsable(): void
    {
        $note = $this->notePreValidee();
        $this->assertResponseStatus(200, $this->renvoyer($this->manager, $note, self::MESSAGE));

        $this->assertResponseStatus(200, $this->envoyer($this->adherent, $note));

        $this->assertSame(ExpenseReportStatusEnum::SUBMITTED, $this->reload($note)->getStatus());
        $this->assertTrue($this->show($this->responsable, $note)['body']['peutDecider']);
        $this->assertRefused($this->patch($this->manager, $note, ['status' => 'approved']), 'status');
        $this->assertResponseStatus(200, $this->patch($this->coResponsable, $note, ['status' => 'preapproved']));
        $this->assertResponseStatus(200, $this->patch($this->manager, $note, ['status' => 'approved']));
    }

    public function testLaTresorerieComptabiliseUneNoteValidee(): void
    {
        $note = $this->notePreValidee();
        $this->assertResponseStatus(200, $this->patch($this->manager, $note, ['status' => 'approved']));

        $this->assertResponseStatus(200, $this->patch($this->otherManager, $note, ['status' => 'accounted']));

        $this->assertSame(ExpenseReportStatusEnum::ACCOUNTED, $this->reload($note)->getStatus());
    }

    public function testLaDerniereDecisionEstLaPreValidationPuisLaValidationDeLaTresorerie(): void
    {
        $this->nommer($this->manager, 'Lou', 'Martin');
        $note = $this->notePreValidee();

        $preValidation = $this->show($this->adherent, $note)['body']['derniereDecision'];
        $this->assertSame(['preapproved', ['prenom' => 'Alex', 'nom' => 'DURAND'], null], [$preValidation['statut'], $preValidation['auteur'], $preValidation['message']]);

        $this->assertResponseStatus(200, $this->patch($this->manager, $note, ['status' => 'approved']));
        $validation = $this->show($this->adherent, $note)['body']['derniereDecision'];
        $this->assertSame(['approved', ['prenom' => 'Lou', 'nom' => 'MARTIN']], [$validation['statut'], $validation['auteur']]);

        // La comptabilisation n'est pas une décision.
        $this->assertResponseStatus(200, $this->patch($this->manager, $note, ['status' => 'accounted']));
        $this->assertSame('approved', $this->show($this->adherent, $note)['body']['derniereDecision']['statut']);
    }

    public function testLaDerniereDecisionDitSiElleVientDuResponsableOuDeLaTresorerie(): void
    {
        $note = $this->notePreValidee();
        $this->assertSame('responsable', $this->derniereDecision($note)['niveau']);

        $this->assertResponseStatus(200, $this->renvoyer($this->manager, $note));
        $this->assertSame(['rejected', 'tresorerie'], [$this->derniereDecision($note)['statut'], $this->derniereDecision($note)['niveau']]);

        $this->assertResponseStatus(200, $this->envoyer($this->adherent, $note));
        $this->assertResponseStatus(200, $this->renvoyer($this->coResponsable, $note));
        $this->assertSame(['rejected', 'responsable'], [$this->derniereDecision($note)['statut'], $this->derniereDecision($note)['niveau']]);

        $this->assertResponseStatus(200, $this->envoyer($this->adherent, $note));
        $this->assertResponseStatus(200, $this->patch($this->responsable, $note, ['status' => 'preapproved']));
        $this->assertResponseStatus(200, $this->patch($this->manager, $note, ['status' => 'approved']));
        $this->assertSame(['approved', 'tresorerie'], [$this->derniereDecision($note)['statut'], $this->derniereDecision($note)['niveau']]);
    }

    /**
     * @dataProvider decisionsDeLaTresorerieProvider
     */
    public function testSansValideurLaDecisionVientDeLaTresorerie(array $decision): void
    {
        $note = $this->noteEnvoyee($this->adherent);

        $this->assertResponseStatus(200, $this->patch($this->manager, $note, $decision));

        $this->assertSame([$decision['status'], 'tresorerie'], [$this->derniereDecision($note)['statut'], $this->derniereDecision($note)['niveau']]);
    }

    /**
     * Les valideurs sont recalculés à chaque lecture ; le niveau d'une décision est celui du moment où elle a été prise.
     */
    public function testLeNiveauNeChangePasQuandLesValideursChangent(): void
    {
        $refuseeParLeResponsable = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);
        $this->assertResponseStatus(200, $this->renvoyer($this->responsable, $refuseeParLeResponsable));
        $refuseeParLaTresorerie = $this->noteEnvoyee($this->responsableAutreCommission, commission: $this->autreCommission);
        $this->assertResponseStatus(200, $this->renvoyer($this->manager, $refuseeParLaTresorerie));

        foreach ([$this->responsable, $this->coResponsable] as $responsable) {
            $this->em()->find(User::class, $responsable->getId())->setDoitRenouveler(true);
        }
        $this->em()->flush();
        $this->rendreResponsable($this->adherent, $this->autreCommission);

        $this->assertSame([], $this->show($this->adherent, $refuseeParLeResponsable)['body']['valideurs']);
        $this->assertSame('responsable', $this->derniereDecision($refuseeParLeResponsable)['niveau']);
        $this->assertNotSame([], $this->show($this->responsableAutreCommission, $refuseeParLaTresorerie)['body']['valideurs']);
        $this->assertSame('tresorerie', $this->show($this->responsableAutreCommission, $refuseeParLaTresorerie)['body']['derniereDecision']['niveau']);
    }

    public function testValidationResponsableEstCelleDuDernierEnvoi(): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);
        $this->assertNull($this->validationResponsable($note));

        $avant = new \DateTimeImmutable('-1 minute');
        $this->assertResponseStatus(200, $this->patch($this->responsable, $note, ['status' => 'preapproved']));
        $premiere = $this->validationResponsable($note);
        $this->assertSame(['auteur', 'date'], array_keys($premiere));
        $this->assertSame(['prenom' => 'Alex', 'nom' => 'DURAND'], $premiere['auteur']);
        $this->assertGreaterThanOrEqual($avant, new \DateTimeImmutable($premiere['date']));

        $this->assertResponseStatus(200, $this->renvoyer($this->manager, $note));
        $this->assertSame($premiere, $this->validationResponsable($note));

        $this->assertResponseStatus(200, $this->envoyer($this->adherent, $note));
        $this->assertNull($this->validationResponsable($note));
        $this->assertResponseStatus(200, $this->patch($this->coResponsable, $note, ['status' => 'preapproved']));
        $seconde = $this->validationResponsable($note);
        $this->assertSame(['prenom' => 'Sam', 'nom' => 'PETIT'], $seconde['auteur']);

        $this->assertResponseStatus(200, $this->patch($this->manager, $note, ['status' => 'approved']));
        $this->assertSame($seconde, $this->validationResponsable($note));
        $this->assertResponseStatus(200, $this->patch($this->manager, $note, ['status' => 'accounted']));
        $this->assertSame($seconde, $this->validationResponsable($note));
    }

    public function testUneValidationDAvantLeRenvoiNeComptePlusQuandLaTresorerieDecideSeule(): void
    {
        $note = $this->notePreValidee();
        $this->assertResponseStatus(200, $this->renvoyer($this->manager, $note));
        $this->assertResponseStatus(200, $this->envoyer($this->adherent, $note));
        foreach ([$this->responsable, $this->coResponsable] as $responsable) {
            $this->em()->find(User::class, $responsable->getId())->setDoitRenouveler(true);
        }
        $this->em()->flush();

        $this->assertResponseStatus(200, $this->patch($this->manager, $note, ['status' => 'approved']));

        $this->assertSame(['approved', 'tresorerie'], [$this->derniereDecision($note)['statut'], $this->derniereDecision($note)['niveau']]);
        $this->assertNull($this->validationResponsable($note));
    }

    public function testSansValideurIlNYAPasDeValidationDuResponsable(): void
    {
        $note = $this->noteEnvoyee($this->adherent);

        $this->assertResponseStatus(200, $this->patch($this->manager, $note, ['status' => 'approved']));

        $this->assertNull($this->validationResponsable($note));
    }

    public function testUneNoteDeSortieNExposePasDeValidationDuResponsable(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::SUBMITTED);

        $this->assertArrayNotHasKey('validationResponsable', $this->show($this->manager, $report)['body']);
    }

    public function testLeMessageLaisseALaPreValidationEstExpose(): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $this->assertResponseStatus(200, $this->patch($this->responsable, $note, ['status' => 'preapproved', 'commentaireStatut' => 'Prévu au budget de la commission.']));

        $this->assertSame('Prévu au budget de la commission.', $this->show($this->adherent, $note)['body']['derniereDecision']['message']);
    }

    public function testPersonneNePeutDeciderSurLeSiteDUneNotePreValidee(): void
    {
        $this->rendreResponsable($this->manager, $this->commissionDeTest);
        $note = $this->notePreValidee();

        foreach (['adherent', 'responsable', 'coResponsable', 'manager', 'otherManager'] as $lecteur) {
            $this->assertFalse($this->show($this->{$lecteur}, $note)['body']['peutDecider'], $lecteur);
        }
    }

    public function testLeDemandeurNeModifiePlusUneNotePreValidee(): void
    {
        $note = $this->notePreValidee();

        $this->assertRefused($this->patch($this->adherent, $note, ['objet' => 'Autre chose']), 'objet');
        $this->assertRefused($this->patch($this->adherent, $note, ['refundRequired' => false]), 'refundRequired');
        $this->assertResponseStatus(422, $this->uploadAttachment($this->adherent, $note, 'ligne-1'));
        $this->assertSame('Dégaines pour la SAE', $this->reload($note)->getObjet());
    }

    public function testUneNoteDeSortieNePassePasParLaPreValidation(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::SUBMITTED);

        $this->assertRefused($this->patch($this->manager, $report, ['status' => 'preapproved']), 'status');
        $this->assertSame(ExpenseReportStatusEnum::SUBMITTED, $this->reload($report)->getStatus());
    }

    /**
     * Un refus est envoyé avec son message, comme le font le site et compta-club.
     *
     * @param array<string, list<string>> $attendu par statut de départ, une issue par statut de self::CIBLES
     *
     * @dataProvider matriceProvider
     */
    public function testMatriceDesTransitions(string $role, array $attendu): void
    {
        $acteur = match ($role) {
            'demandeur' => $this->adherent,
            'valideur' => $this->responsable,
            'valideur d\'une autre commission' => $this->responsableAutreCommission,
            'gestionnaire' => $this->manager,
            'gestionnaire valideur' => $this->manager,
            'tiers' => $this->stranger,
        };
        if ('gestionnaire valideur' === $role) {
            $this->rendreResponsable($this->manager, $this->commissionDeTest);
        }

        $obtenu = [];
        foreach (array_keys($attendu) as $depart) {
            foreach (self::CIBLES as $cible) {
                $obtenu[$depart][] = $this->issue($acteur, $this->noteAuStatut($depart, $cible), $cible);
            }
        }

        $this->assertSame($attendu, $obtenu);
    }

    public static function matriceProvider(): iterable
    {
        [$ok, $sansEffet, $deja, $non, $interdite] = [self::OK, self::SANS_EFFET, self::DEJA, self::NON, self::INTERDITE];

        // Colonnes : draft, submitted, preapproved, approved, rejected, accounted.
        yield 'demandeur' => ['demandeur', [
            'draft' => [$sansEffet, $ok, $non, $non, $non, $non],
            'submitted' => [$non, $sansEffet, $non, $non, $non, $non],
            'submitted sans valideur' => [$non, $sansEffet, $non, $non, $non, $non],
            'preapproved' => [$non, $non, $sansEffet, $non, $non, $non],
            // rejected → rejected : refusée pour le message joint, que seul celui qui décide écrit.
            'rejected' => [$non, $ok, $non, $non, $non, $non],
            'approved' => [$non, $non, $non, $sansEffet, $non, $non],
            'accounted' => [$non, $non, $non, $non, $non, $sansEffet],
        ]];
        yield 'valideur' => ['valideur', [
            'draft' => [$sansEffet, $non, $non, $non, $non, $non],
            'submitted' => [$non, $sansEffet, $ok, $non, $ok, $non],
            'submitted sans valideur' => array_fill(0, 6, $interdite),
            'preapproved' => [$non, $non, $deja, $deja, $deja, $non],
            'rejected' => [$non, $non, $deja, $deja, $deja, $non],
            'approved' => [$non, $non, $deja, $deja, $deja, $non],
            'accounted' => [$non, $non, $deja, $deja, $deja, $sansEffet],
        ]];
        $gestionnaire = [
            'draft' => [$sansEffet, $non, $non, $non, $non, $non],
            'submitted' => [$non, $sansEffet, $non, $non, $non, $non],
            'submitted sans valideur' => [$non, $sansEffet, $non, $ok, $ok, $non],
            'preapproved' => [$non, $non, $sansEffet, $ok, $ok, $non],
            'rejected' => [$non, $non, $deja, $deja, $deja, $non],
            'approved' => [$non, $non, $deja, $deja, $deja, $ok],
            'accounted' => [$non, $non, $deja, $deja, $deja, $sansEffet],
        ];
        yield 'gestionnaire' => ['gestionnaire', $gestionnaire];
        // Valideur tant que la note est envoyée, gestionnaire ensuite.
        yield 'gestionnaire valideur' => ['gestionnaire valideur', array_replace($gestionnaire, ['submitted' => [$non, $sansEffet, $ok, $non, $ok, $non]])];
        foreach (['tiers', 'valideur d\'une autre commission'] as $role) {
            yield $role => [$role, array_fill_keys(['draft', 'submitted', 'submitted sans valideur', 'preapproved', 'rejected', 'approved', 'accounted'], array_fill(0, 6, $interdite))];
        }
    }

    /**
     * Note de l'adhérent sur la commission de test, ou sans valideur. Le demandeur ne peut
     * l'envoyer que choix fait et justificatifs joints.
     */
    private function noteAuStatut(string $depart, string $cible): ExpenseReport
    {
        $statut = ExpenseReportStatusEnum::from(explode(' ', $depart)[0]);
        $commission = 'submitted sans valideur' === $depart ? null : $this->commissionDeTest;
        $note = $this->creerAchat($this->adherent, ExpenseReportStatusEnum::DRAFT, self::LIGNES_ACHAT, $commission);
        if ('submitted' === $cible && $statut->isEditableByOwner()) {
            $this->joindreLesJustificatifs($note, 'ligne-1', 'ligne-2');
        }
        $this->em()->getConnection()->executeStatement('UPDATE expense_report SET status = ?, refund_required = 1 WHERE id = ?', [$statut->value, $note->getId()]);

        return $note;
    }

    /**
     * Hors une transition acceptée, rien ne change : ni le statut, ni l'historique, et aucun mail ne part.
     */
    private function issue(User $acteur, ExpenseReport $note, string $cible): string
    {
        $avant = [$this->reload($note)->getStatus(), $this->lignesDHistorique($note)];
        $response = $this->patch($acteur, $note, ['status' => $cible] + ('rejected' === $cible ? ['commentaireStatut' => self::MESSAGE] : []));
        $apres = [$this->reload($note)->getStatus(), $this->lignesDHistorique($note)];
        $inchangee = $apres === $avant && [] === $this->getMailerMessages();
        $surLeStatut = array_column(array_filter($response['body']['violations'] ?? [], static fn (array $violation) => 'status' === $violation['propertyPath']), 'message');

        return match (true) {
            200 === $response['status'] && $apres[0]->value === $cible && $apres[0] !== $avant[0] => self::OK,
            200 === $response['status'] && $inchangee => self::SANS_EFFET,
            403 === $response['status'] && $inchangee => self::INTERDITE,
            422 === $response['status'] && $inchangee && [self::DEJA_TRAITEE] === $surLeStatut => self::DEJA,
            422 === $response['status'] && $inchangee => self::NON,
            default => sprintf('inattendu : %d, %s, %d ligne(s) d\'historique, %d mail(s)', $response['status'], $apres[0]->value, $apres[1], \count($this->getMailerMessages())),
        };
    }

    private function lignesDHistorique(ExpenseReport $note): int
    {
        return $this->em()->getRepository(ExpenseReportStatusHistory::class)->count(['expenseReport' => $note->getId()]);
    }

    /**
     * Achat de l'adhérent envoyé sur la commission de test, puis pré-validé par Alex Durand.
     */
    private function notePreValidee(): ExpenseReport
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);
        $this->assertResponseStatus(200, $this->patch($this->responsable, $note, ['status' => 'preapproved']));

        return $note;
    }

    private function derniereDecision(ExpenseReport $note): array
    {
        return $this->show($this->adherent, $note)['body']['derniereDecision'];
    }

    private function validationResponsable(ExpenseReport $note): ?array
    {
        $lue = $this->show($this->adherent, $note);
        $this->assertArrayHasKey('validationResponsable', $lue['body']);

        return $lue['body']['validationResponsable'];
    }

    private function derniereLigneDHistorique(ExpenseReport $note): ExpenseReportStatusHistory
    {
        $this->em()->clear();

        return $this->em()->getRepository(ExpenseReportStatusHistory::class)->findBy(['expenseReport' => $note->getId()], ['id' => 'DESC'])[0];
    }
}
