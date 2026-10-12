<?php

namespace App\Tests\Api;

use App\Entity\Commission;
use App\Entity\ExpenseReport;
use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * Mails à l'envoi d'une note hors sortie : chaque valideur reçoit la note à valider, le demandeur
 * apprend à qui elle est envoyée. Aucun mail ne part à la trésorerie.
 */
class NoteHorsSortieMailsTest extends ExpenseReportApiTestCase
{
    private const LIGNES = self::LIGNES_ACHAT;

    public function testLeMailAuValideurDecritLaDemande(): void
    {
        $this->nommer($this->owner, 'Lou', 'Martin');
        $note = $this->noteAEnvoyer(commission: $this->commissionDeTest);

        $this->assertResponseStatus(200, $this->envoyer($this->owner, $note, ['motPourResponsable' => 'Vu avec Alex à la réunion de commission.']));

        $mails = $this->mailsA($this->responsable->getEmail());
        $this->assertCount(1, $mails);
        $this->assertSame('Note de frais à valider : Dégaines pour la SAE', $mails[0]->getSubject());
        $this->assertReponseAuDemandeur($mails[0], $this->owner);
        foreach ([$mails[0]->getTextBody(), $mails[0]->getHtmlBody()] as $corps) {
            $this->assertStringContainsStringIgnoringCase('Lou Martin', $corps);
            $this->assertMatchesRegularExpression('#Type :(</strong>)? Achat#', $corps);
            $this->assertMatchesRegularExpression('#Date de l(\'|&\#039;)achat :(</strong>)? ' . (new \DateTimeImmutable('-3 days'))->format('d/m/Y') . '#', $corps);
            $this->assertStringContainsString('Commission de test', $corps);
            $this->assertStringContainsString('Dégaines pour la SAE', $corps);
            $this->assertStringContainsString('86,40', $corps);
            $this->assertStringContainsString('Vu avec Alex à la réunion de commission.', $corps);
        }
    }

    public function testLeMailAuValideurDonneLesAvertissements(): void
    {
        $note = $this->noteAEnvoyer('{"lignes":[{"id":"ligne-1","montant":5000,"description":"Matelas de réception"}]}', ['ligne-1'], commission: $this->commissionDeTest);

        $response = $this->envoyer($this->owner, $note);

        $this->assertResponseStatus(200, $response);
        $this->assertNotEmpty($response['body']['avertissements']);
        $mail = $this->mailsA($this->responsable->getEmail())[0];
        foreach ($response['body']['avertissements'] as $avertissement) {
            $this->assertStringContainsString($avertissement, $mail->getTextBody());
            $this->assertStringContainsString(htmlspecialchars($avertissement), $mail->getHtmlBody());
        }
    }

    public function testLeMailAuValideurOuvreLaDemande(): void
    {
        $note = $this->noteAEnvoyer(commission: $this->commissionDeTest);

        $this->assertResponseStatus(200, $this->envoyer($this->owner, $note));

        $mail = $this->mailsA($this->responsable->getEmail())[0];
        $this->assertStringContainsString('Voir la note', $mail->getHtmlBody());
        $this->assertStringContainsString('href="http://localhost/notes-de-frais/' . $note->getId() . '"', $mail->getHtmlBody());
        $this->assertStringContainsString('http://localhost/notes-de-frais/' . $note->getId(), $mail->getTextBody());
    }

    public function testSansValideurSeulLeDemandeurRecoitUnMail(): void
    {
        $note = $this->noteAEnvoyer();

        $this->assertResponseStatus(200, $this->envoyer($this->owner, $note));

        $this->assertSeulsDestinataires([$this->owner]);
        $mail = $this->mailsA($this->owner->getEmail())[0];
        $this->assertSame('Votre note de frais « Dégaines pour la SAE » est envoyée à la trésorerie.', $mail->getSubject());
        foreach ([$mail->getTextBody(), $mail->getHtmlBody()] as $corps) {
            $this->assertStringContainsString('est envoyée à la trésorerie', $corps);
            $this->assertStringContainsString('Dégaines (x6)', $corps);
            $this->assertStringContainsString('Cordelette 6 mm', $corps);
            $this->assertStringContainsString('86,40', $corps);
            $this->assertStringNotContainsString('comptabilité', $corps);
        }
    }

    public function testUnRenvoiApresCorrectionSansValideurNePrevientQueLeDemandeur(): void
    {
        $note = $this->noteAEnvoyer(self::LIGNES, ['ligne-1', 'ligne-2'], ExpenseReportStatusEnum::REJECTED);

        $this->assertResponseStatus(200, $this->envoyer($this->owner, $note));

        $this->assertSeulsDestinataires([$this->owner]);
    }

    public function testUnEnvoiRefuseNEnvoieAucunMail(): void
    {
        $note = $this->noteAEnvoyer(self::LIGNES, ['ligne-1']);

        $this->assertResponseStatus(422, $this->envoyer($this->owner, $note));

        $this->assertCount(0, $this->getMailerMessages());
    }

    public function testUneNoteDeSortieNePrevientQueSonDemandeur(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::DRAFT);

        $this->assertResponseStatus(200, $this->patch($this->owner, $report, ['status' => 'submitted']));

        $this->assertSeulsDestinataires([$this->owner]);
        $this->assertStringContainsString('est envoyée à la trésorerie', $this->mailsA($this->owner->getEmail())[0]->getTextBody());
    }

    /**
     * @param list<string> $justificatifs lignes qui reçoivent un justificatif
     */
    private function noteAEnvoyer(string $details = self::LIGNES, array $justificatifs = ['ligne-1', 'ligne-2'], ExpenseReportStatusEnum $status = ExpenseReportStatusEnum::DRAFT, ?Commission $commission = null): ExpenseReport
    {
        $note = $this->creerAchat($this->owner, $status, $details, $commission);
        $this->joindreLesJustificatifs($note, ...$justificatifs);

        return $note;
    }
}
