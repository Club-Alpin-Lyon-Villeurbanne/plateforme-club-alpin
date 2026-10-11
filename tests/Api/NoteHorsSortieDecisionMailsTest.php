<?php

namespace App\Tests\Api;

use App\Utils\Enums\ExpenseReportStatusEnum;
use Monolog\Handler\TestHandler;
use Symfony\Component\Mime\Email;

/**
 * Mails de la décision : récapitulatif à l'adresse de la trésorerie à la validation, mail au
 * demandeur qui nomme l'auteur de la décision.
 */
class NoteHorsSortieDecisionMailsTest extends ExpenseReportApiTestCase
{
    private const MESSAGE = 'Merci de joindre la facture du magasin.';

    protected function setUp(): void
    {
        parent::setUp();

        $this->nommer($this->owner, 'Sam', 'Petit');
        $this->nommer($this->manager, 'Alex', 'Durand');
    }

    public function testALaValidationLaTresorerieRecoitLeRecapitulatifComplet(): void
    {
        $note = $this->noteEnvoyee($this->owner);
        $justificatifs = array_column($this->show($this->owner, $note)['body']['piecesJointes'], 'fileUrl');

        $this->assertResponseStatus(200, $this->patch($this->manager, $note, ['status' => 'approved']));

        $mails = $this->mailsA($this->adresseTresorerie());
        $this->assertCount(1, $mails);
        $this->assertSame('Note de frais validée : Dégaines pour la SAE', $mails[0]->getSubject());
        $dateDepense = (new \DateTimeImmutable('-3 days'))->format('d/m/Y');
        foreach ($this->corps($mails[0]) as $corps) {
            $this->assertStringContainsString('Sam PETIT', $corps);
            $this->assertStringContainsString($this->commissionVisible()->getTitle(), $corps);
            $this->assertStringContainsString('Dégaines pour la SAE', $corps);
            $this->assertStringContainsString($dateDepense, $corps);
            $this->assertStringContainsString('Dégaines (x6)', $corps);
            $this->assertStringContainsString('64,90', $corps);
            $this->assertStringContainsString('Cordelette 6 mm', $corps);
            $this->assertStringContainsString('21,50', $corps);
            $this->assertStringContainsString('Total : 86,40', $corps);
            $this->assertStringContainsString('Remboursable : 86,40', $corps);
            $this->assertStringContainsString(sprintf('Validée par Alex DURAND le %s', date('d/m/Y')), $corps);
            $this->assertStringContainsString(sprintf('Libellé de saisie : PETIT Sam NDF %s Dégaines pour la SAE', $dateDepense), $corps);
            $this->assertStringContainsString('http://localhost/notes-de-frais/' . $note->getId(), $corps);
            foreach ($justificatifs as $justificatif) {
                $this->assertStringContainsString($justificatif, $corps);
            }
        }
    }

    /**
     * @dataProvider choixProvider
     */
    public function testLeRecapitulatifDonneLeChoixAvecLaPhraseDeclaree(bool $remboursement, string $choix, string $phrase): void
    {
        $note = $this->noteEnvoyee($this->owner, $remboursement);

        $this->assertResponseStatus(200, $this->patch($this->manager, $note, ['status' => 'approved']));

        foreach ($this->corps($this->mailsA($this->adresseTresorerie())[0]) as $corps) {
            $this->assertStringContainsString($choix, $corps);
            $this->assertStringContainsString($phrase, $corps);
        }
    }

    public static function choixProvider(): iterable
    {
        yield 'remboursement' => [true, 'Remboursement demandé', 'Je demande au Club Alpin de Lyon le remboursement de cette note de frais, limité au plafond décidé par le Comité Directeur.'];
        yield 'don' => [false, 'Don au club', 'Je renonce au remboursement du montant de ces frais pour en faire don au Club Alpin de Lyon.'];
    }

    public function testLeRecapitulatifDonneLesAvertissements(): void
    {
        $note = $this->noteEnvoyee($this->owner, true, '{"lignes":[{"id":"ligne-1","montant":5000,"description":"Matelas de réception"}]}');
        $avertissements = $this->show($this->owner, $note)['body']['avertissements'];
        $this->assertNotEmpty($avertissements);

        $this->assertResponseStatus(200, $this->patch($this->manager, $note, ['status' => 'approved']));

        $mail = $this->mailsA($this->adresseTresorerie())[0];
        foreach ($avertissements as $avertissement) {
            $this->assertStringContainsString($avertissement, $mail->getTextBody());
            $this->assertStringContainsString(htmlspecialchars($avertissement), $mail->getHtmlBody());
        }
    }

    public function testLeDemandeurApprendQuiAValideSaNote(): void
    {
        $note = $this->noteEnvoyee($this->owner);

        $this->assertResponseStatus(200, $this->patch($this->manager, $note, ['status' => 'approved']));

        $mails = $this->mailsA($this->owner->getEmail());
        $this->assertCount(1, $mails);
        $this->assertSame('Votre note de frais « Dégaines pour la SAE » a été validée par Alex DURAND et transmise à la trésorerie.', $mails[0]->getSubject());
        foreach ($this->corps($mails[0]) as $corps) {
            $this->assertStringContainsString('validée par Alex DURAND et transmise à la trésorerie', $corps);
        }
    }

    public function testAuRenvoiLeMessageEstAttribueASonAuteur(): void
    {
        $note = $this->noteEnvoyee($this->owner);

        $this->assertResponseStatus(200, $this->renvoyer($this->manager, $note, self::MESSAGE));

        $this->assertCount(0, $this->mailsA($this->adresseTresorerie()));
        $mails = $this->mailsA($this->owner->getEmail());
        $this->assertCount(1, $mails);
        foreach ($this->corps($mails[0]) as $corps) {
            $this->assertStringContainsString('Message laissé par Alex DURAND', $corps);
            $this->assertStringContainsString('Merci de joindre la facture du magasin.', $corps);
            $this->assertStringNotContainsString('Message laissé par la trésorerie', $corps);
        }
    }

    public function testApresCorrectionLeNouvelEnvoiPrevientLaTresorerieANouveau(): void
    {
        $note = $this->noteEnvoyee($this->owner);
        $this->assertResponseStatus(200, $this->renvoyer($this->manager, $note, self::MESSAGE));

        $this->assertResponseStatus(200, $this->envoyer($this->owner, $note));

        $mails = $this->mailsA($this->adresseTresorerie());
        $this->assertCount(1, $mails);
        $this->assertSame('Note de frais à valider : Dégaines pour la SAE', $mails[0]->getSubject());
    }

    public function testUneNoteDeSortieValideeNeSollicitePasLAdresseDeLaTresorerie(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::SUBMITTED);

        $this->assertResponseStatus(200, $this->patch($this->manager, $report, ['status' => 'approved']));

        $this->assertCount(0, $this->mailsA($this->adresseTresorerie()));
        $mails = $this->mailsA($this->owner->getEmail());
        $this->assertCount(1, $mails);
        $this->assertStringContainsString('a été acceptée', $mails[0]->getTextBody());
    }

    public function testSansAdresseDeLaTresorerieLaValidationAboutitEtLOubliEstSignale(): void
    {
        $note = $this->noteEnvoyee($this->owner);
        $this->sansAdresseDeTresorerie();

        $this->assertResponseStatus(200, $this->patch($this->manager, $note, ['status' => 'approved']));

        $this->assertCount(1, $this->getMailerMessages());
        $this->assertTrue($this->getContainer()->get(TestHandler::class)->hasErrorThatContains('NOTES_DE_FRAIS_EMAIL_TRESORERIE'));
    }

    /**
     * @return list<string>
     */
    private function corps(Email $mail): array
    {
        return [html_entity_decode($mail->getTextBody(), \ENT_QUOTES), html_entity_decode($mail->getHtmlBody(), \ENT_QUOTES)];
    }
}
