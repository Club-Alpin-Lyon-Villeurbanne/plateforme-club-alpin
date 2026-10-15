<?php

namespace App\Tests\Api;

use App\Entity\UserAttr;
use App\Service\NoteDeFrais\ControlesNoteHorsSortie;
use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * Les notes hors sortie sont réservées aux rôles d'encadrement et aux responsables de commission,
 * dans n'importe quelle commission : la note peut aller à une autre commission que celle du rôle.
 */
class NoteHorsSortieAccesTest extends ExpenseReportApiTestCase
{
    /**
     * @dataProvider roleDEncadrementProvider
     */
    public function testChaqueRoleOuvreLAchatEtLaFormationSurUneAutreCommission(string $role): void
    {
        $this->donnerUnRole($this->adherent, $role, $this->autreCommission);

        $this->assertResponseStatus(201, $this->create($this->adherent, $this->achat(['commissionId' => $this->commissionDeTest->getId()])));
        $this->assertResponseStatus(201, $this->create($this->adherent, $this->formation()));
    }

    public static function roleDEncadrementProvider(): iterable
    {
        yield 'responsable de commission' => [UserAttr::RESPONSABLE_COMMISSION];
        yield 'encadrant' => [UserAttr::ENCADRANT];
        yield 'coencadrant' => [UserAttr::COENCADRANT];
        yield 'stagiaire' => [UserAttr::STAGIAIRE];
        yield 'bénévole d\'encadrement' => [UserAttr::BENEVOLE];
    }

    /**
     * @dataProvider typeProvider
     */
    public function testSansRoleAucuneNoteNEstCreee(string $type): void
    {
        $avant = $this->nombreDeNotesHorsSortieDeLAdherent();
        $corps = 'formation' === $type ? $this->formation() : $this->achat();

        $this->assertRefusAvec($this->create($this->adherent, $corps), 'type', ControlesNoteHorsSortie::RESERVEES_A_L_ENCADREMENT);
        $this->assertSame($avant, $this->nombreDeNotesHorsSortieDeLAdherent());
    }

    public static function typeProvider(): iterable
    {
        yield 'achat' => ['autre'];
        yield 'formation' => ['formation'];
    }

    public function testUnRoleSansEncadrementNeSuffitPas(): void
    {
        $this->donnerUnRole($this->adherent, UserAttr::REDACTEUR, $this->commissionDeTest);

        $this->assertRefusAvec($this->create($this->adherent, $this->achat()), 'type', ControlesNoteHorsSortie::RESERVEES_A_L_ENCADREMENT);
    }

    public function testQuiPerdSonRoleTermineSaNote(): void
    {
        $note = $this->creerFormation($this->adherent, '{"lignes":[{"id":"ligne-1","nature":"voiture","distance":220}]}');

        $this->assertResponseStatus(200, $this->patch($this->adherent, $note, [
            'commission' => '/api/commissions/' . $this->autreCommission->getId(),
            'objet' => 'Formation initiateur SAE, Grenoble',
        ]));
        $this->assertResponseStatus(200, $this->envoyer($this->adherent, $note));
        $this->assertSame(ExpenseReportStatusEnum::SUBMITTED, $this->reload($note)->getStatus());
    }

    public function testLeMenuEtLeBoutonDeMesNotesNeSontQuePourLesRoles(): void
    {
        foreach ([[$this->adherent, false], [$this->encadrant, true]] as [$utilisateur, $peutDeposer]) {
            $this->signin($utilisateur);
            $page = $this->client->request('GET', '/notes-de-frais');

            $this->assertResponseIsSuccessful();
            $this->assertCount($peutDeposer ? 1 : 0, $page->filter('a[href="/notes-de-frais/nouvelle"]'));
            $this->assertSame($peutDeposer, json_decode($page->filter('#notes-de-frais')->attr('data-donnees'), true)['peutDeposer']);
        }
    }

    private function nombreDeNotesHorsSortieDeLAdherent(): int
    {
        return (int) $this->em()->getConnection()->fetchOne("SELECT COUNT(*) FROM expense_report WHERE type <> 'sortie' AND user_id = ?", [$this->adherent->getId()]);
    }
}
