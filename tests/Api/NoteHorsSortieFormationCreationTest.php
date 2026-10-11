<?php

namespace App\Tests\Api;

use App\Entity\UserAttr;
use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * Création d'une formation : réservée aux encadrants de la commission, avec un lien facultatif,
 * sans délai d'envoi.
 */
class NoteHorsSortieFormationCreationTest extends ExpenseReportApiTestCase
{
    private const MESSAGE_ENCADRANTS = 'Seuls les encadrants de la commission peuvent demander le remboursement d\'une formation.';

    public function testUnEncadrantDeLaCommissionDemandeUneFormation(): void
    {
        $response = $this->create($this->encadrant, $this->formation(['lienFormation' => 'https://example.org/formation-initiateur']));

        $this->assertResponseStatus(201, $response);
        $this->assertSame('formation', $response['body']['type']);
        $this->assertSame('draft', $response['body']['status']);
        $this->assertSame($this->commissionDeTest->getId(), $response['body']['commission']['id']);
        $this->assertSame('https://example.org/formation-initiateur', $response['body']['lienFormation']);
    }

    /**
     * @dataProvider roleDEncadrementProvider
     */
    public function testChaqueRoleDEncadrementPermetDeDemanderUneFormation(string $role): void
    {
        $this->donnerUnRole($this->adherent, $role, $this->commissionDeTest);

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

    public function testUnAdherentSansRoleNeDemandePasDeFormation(): void
    {
        $avant = $this->nombreDeNotes();

        $this->assertRefusAvec($this->create($this->adherent, $this->formation()), 'commission', self::MESSAGE_ENCADRANTS);
        $this->assertSame($avant, $this->nombreDeNotes());
    }

    public function testEncadrerUneAutreCommissionNeSuffitPas(): void
    {
        $this->assertRefusAvec(
            $this->create($this->encadrant, $this->formation(['commissionId' => $this->autreCommission->getId()])),
            'commission',
            self::MESSAGE_ENCADRANTS,
        );
    }

    public function testUnRoleSansEncadrementNeSuffitPas(): void
    {
        $this->donnerUnRole($this->adherent, UserAttr::REDACTEUR, $this->commissionDeTest);

        $this->assertRefusAvec($this->create($this->adherent, $this->formation()), 'commission', self::MESSAGE_ENCADRANTS);
    }

    public function testUnAchatResteOuvertATousLesAdherents(): void
    {
        $this->assertResponseStatus(201, $this->create($this->adherent, $this->achat(['commissionId' => $this->commissionDeTest->getId()])));
    }

    public function testUneFormationSansLienEstAcceptee(): void
    {
        $response = $this->create($this->encadrant, $this->formation());

        $this->assertResponseStatus(201, $response);
        $this->assertNull($response['body']['lienFormation']);
    }

    public function testUnLienQuiNEstPasUneAdresseWebEstRefuse(): void
    {
        $this->assertRefused($this->create($this->encadrant, $this->formation(['lienFormation' => 'javascript:alert(1)'])), 'lienFormation');
    }

    public function testUneFormationPlusAncienneQueLeDelaiDesAchatsEstAcceptee(): void
    {
        $date = (new \DateTimeImmutable('today'))->modify(sprintf('-%d days', $this->configHorsSortie()['delaiEnvoiJours']['autre'] + 60));

        $this->assertResponseStatus(201, $this->create($this->encadrant, $this->formation(['dateDepense' => $date->format('Y-m-d')])));
    }

    public function testUneFormationDateeDansLeFuturEstRefusee(): void
    {
        $response = $this->create($this->encadrant, $this->formation(['dateDepense' => (new \DateTimeImmutable('+1 day'))->format('Y-m-d')]));

        $this->assertRefusAvec($response, 'dateDepense', 'La date ne peut pas être dans le futur.');
    }

    public function testLeDemandeurNeDeplacePasSaFormationVersUneCommissionQuIlNEncadrePas(): void
    {
        $note = $this->creerFormation($this->encadrant);

        $response = $this->patch($this->encadrant, $note, ['commission' => '/api/commissions/' . $this->autreCommission->getId()]);

        $this->assertRefusAvec($response, 'commission', self::MESSAGE_ENCADRANTS);
        $this->assertSame($this->commissionDeTest->getId(), $this->reload($note)->getCommission()->getId());
    }

    public function testLeDemandeurDeplaceSaFormationVersUneAutreCommissionQuIlEncadre(): void
    {
        $this->donnerUnRole($this->encadrant, UserAttr::STAGIAIRE, $this->autreCommission);
        $note = $this->creerFormation($this->encadrant);

        $this->assertResponseStatus(200, $this->patch($this->encadrant, $note, ['commission' => '/api/commissions/' . $this->autreCommission->getId()]));
        $this->assertSame($this->autreCommission->getId(), $this->reload($note)->getCommission()->getId());
    }

    public function testLeControleNeJoueQuAuChoixDeLaCommission(): void
    {
        // Le demandeur a perdu son rôle après la création : il garde sa demande.
        $note = $this->creerFormation($this->adherent, '{"lignes":[{"id":"ligne-1","nature":"voiture","distance":220}]}');

        $this->assertResponseStatus(200, $this->patch($this->adherent, $note, ['objet' => 'Formation initiateur SAE, Grenoble']));
        $this->assertResponseStatus(200, $this->envoyer($this->adherent, $note));
        $this->assertSame(ExpenseReportStatusEnum::SUBMITTED, $this->reload($note)->getStatus());
    }

    public function testMesNotesDeFraisListeLesFormations(): void
    {
        $formation = $this->creerFormation($this->encadrant);

        $response = $this->list($this->encadrant, '/api/notes-de-frais', ['type' => ['autre', 'formation'], 'inclure_brouillons' => 'true']);

        $this->assertResponseStatus(200, $response);
        $notes = array_column($response['body']['data'], null, 'id');
        $this->assertArrayHasKey($formation->getId(), $notes);
        $this->assertSame('formation', $notes[$formation->getId()]['type']);
    }

    private function nombreDeNotes(): int
    {
        return (int) $this->em()->getConnection()->fetchOne('SELECT COUNT(*) FROM expense_report WHERE type = ?', ['formation']);
    }
}
