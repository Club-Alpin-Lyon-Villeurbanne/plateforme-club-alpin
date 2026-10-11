<?php

namespace App\Tests\Api;

use App\Entity\ExpenseReport;
use Symfony\Component\Mime\Email;

/**
 * Mails de la double validation : à chaque étape, seul le demandeur est prévenu ; jamais la
 * trésorerie, qui suit les notes dans compta-club.
 */
class NoteHorsSortieDoubleValidationMailsTest extends ExpenseReportApiTestCase
{
    private const MESSAGE = 'Merci de joindre la facture du magasin.';

    protected function setUp(): void
    {
        parent::setUp();

        $this->nommer($this->manager, 'Lou', 'Martin');
    }

    public function testLaValidationParLaTresorerieEstAnnonceeAuDemandeur(): void
    {
        $note = $this->notePreValidee();

        $this->assertResponseStatus(200, $this->patch($this->manager, $note, ['status' => 'approved']));

        $this->assertSeulsDestinataires([$this->adherent]);
        $mail = $this->seulMailAuDemandeur();
        $this->assertSame('Votre note de frais « Dégaines pour la SAE » a été validée par la trésorerie.', $mail->getSubject());
        foreach ($this->corps($mail) as $corps) {
            $this->assertStringContainsString('a été validée par la trésorerie.', $corps);
            $this->assertStringNotContainsString('Lou MARTIN', $corps);
        }
    }

    public function testLeRefusParLaTresorerieEstAttribueASonAuteur(): void
    {
        $note = $this->notePreValidee();

        $this->assertResponseStatus(200, $this->renvoyer($this->manager, $note, self::MESSAGE));

        $this->assertSeulsDestinataires([$this->adherent]);
        $mail = $this->seulMailAuDemandeur();
        $this->assertSame('Votre note de frais « Dégaines pour la SAE » a été refusée par Lou MARTIN.', $mail->getSubject());
        foreach ($this->corps($mail) as $corps) {
            $this->assertStringContainsString('Message laissé par Lou MARTIN', $corps);
            $this->assertStringContainsString(self::MESSAGE, $corps);
            $this->assertStringContainsString('Corriger ma demande', $corps);
        }
    }

    public function testLaComptabilisationEstAnnonceeAuDemandeur(): void
    {
        $note = $this->notePreValidee();
        $this->assertResponseStatus(200, $this->patch($this->manager, $note, ['status' => 'approved']));

        $this->assertResponseStatus(200, $this->patch($this->manager, $note, ['status' => 'accounted']));

        $this->assertSeulsDestinataires([$this->adherent]);
        $this->assertSame('Votre note de frais « Dégaines pour la SAE » a été comptabilisée.', $this->seulMailAuDemandeur()->getSubject());
    }

    public function testCorrigeeApresUnRefusDeLaTresorerieLaNoteRepartAuxValideurs(): void
    {
        $note = $this->notePreValidee();
        $this->assertResponseStatus(200, $this->renvoyer($this->manager, $note, self::MESSAGE));

        $this->assertResponseStatus(200, $this->envoyer($this->adherent, $note));

        $this->assertSeulsDestinataires([$this->responsable, $this->coResponsable, $this->adherent]);
        $this->assertSame('Votre note de frais « Dégaines pour la SAE » est envoyée à Alex DURAND et Sam PETIT.', $this->seulMailAuDemandeur()->getSubject());
    }

    private function notePreValidee(): ExpenseReport
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);
        $this->assertResponseStatus(200, $this->patch($this->responsable, $note, ['status' => 'preapproved']));

        return $note;
    }

    private function seulMailAuDemandeur(): Email
    {
        $mails = $this->mailsA($this->adherent->getEmail());
        $this->assertCount(1, $mails);

        return $mails[0];
    }

    /**
     * @return list<string>
     */
    private function corps(Email $mail): array
    {
        return [html_entity_decode((string) $mail->getTextBody(), \ENT_QUOTES), html_entity_decode((string) $mail->getHtmlBody(), \ENT_QUOTES)];
    }
}
