<?php

namespace App\Tests\Api;

use App\Utils\Enums\ExpenseReportStatusEnum;
use Symfony\Component\Mime\Email;

/**
 * Mails de la décision sur une note sans valideur : seul le demandeur est prévenu, et la décision
 * est signée de la trésorerie, jamais du nom de la personne. Rien ne part à la trésorerie.
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

    public function testLaValidationNePrevientQueLeDemandeur(): void
    {
        $note = $this->noteEnvoyee($this->owner);

        $this->assertResponseStatus(200, $this->patch($this->manager, $note, ['status' => 'approved']));

        $this->assertSeulsDestinataires([$this->owner]);
    }

    public function testSansValideurLeDemandeurApprendQueLaTresorerieAValideSaNote(): void
    {
        $note = $this->noteEnvoyee($this->owner);

        $this->assertResponseStatus(200, $this->patch($this->manager, $note, ['status' => 'approved']));

        $mails = $this->mailsA($this->owner->getEmail());
        $this->assertCount(1, $mails);
        $this->assertSame('Votre note de frais « Dégaines pour la SAE » a été validée par la trésorerie.', $mails[0]->getSubject());
        foreach ($this->corps($mails[0]) as $corps) {
            $this->assertStringContainsString('a été validée par la trésorerie.', $corps);
        }
    }

    public function testLaComptabilisationNEstAnnonceeQuAuDemandeur(): void
    {
        $note = $this->noteEnvoyee($this->owner);
        $this->assertResponseStatus(200, $this->patch($this->manager, $note, ['status' => 'approved']));

        $this->assertResponseStatus(200, $this->patch($this->manager, $note, ['status' => 'accounted']));

        $this->assertSame(
            ['Votre note de frais « Dégaines pour la SAE » a été comptabilisée.'],
            array_map(static fn (Email $mail) => $mail->getSubject(), $this->mailsA($this->owner->getEmail())),
        );
        $this->assertSeulsDestinataires([$this->owner]);
    }

    public function testAuRenvoiLeMessageEstSigneDeLaTresorerie(): void
    {
        $note = $this->noteEnvoyee($this->owner);

        $this->assertResponseStatus(200, $this->renvoyer($this->manager, $note, self::MESSAGE));

        $this->assertSeulsDestinataires([$this->owner]);
        $mails = $this->mailsA($this->owner->getEmail());
        $this->assertCount(1, $mails);
        $this->assertSame('Votre note de frais « Dégaines pour la SAE » a été refusée par la trésorerie.', $mails[0]->getSubject());
        $this->assertSame([], $mails[0]->getReplyTo());
        foreach ($this->corps($mails[0]) as $corps) {
            $this->assertStringContainsString('Message laissé par la trésorerie', $corps);
            $this->assertStringContainsString('Merci de joindre la facture du magasin.', $corps);
            $this->assertStringNotContainsString('Alex DURAND', $corps);
        }
    }

    public function testLeMailDeRenvoiProposeDeCorrigerLaDemande(): void
    {
        $note = $this->noteEnvoyee($this->owner);

        $this->assertResponseStatus(200, $this->renvoyer($this->manager, $note, self::MESSAGE));

        $mail = $this->mailsA($this->owner->getEmail())[0];
        $lien = 'http://localhost/notes-de-frais/nouvelle?id=' . $note->getId();
        $this->assertStringContainsString('Corriger ma note', $mail->getHtmlBody());
        $this->assertStringContainsString('href="' . $lien . '"', $mail->getHtmlBody());
        $this->assertStringContainsString($lien, $mail->getTextBody());
    }

    public function testChaqueMailAuDemandeurOuvreSaNote(): void
    {
        $note = $this->noteEnvoyee($this->owner, commission: $this->commissionDeTest);
        $mails = [$this->mailsA($this->owner->getEmail())[0]];
        $this->assertResponseStatus(200, $this->patch($this->responsable, $note, ['status' => 'preapproved']));
        $mails[] = $this->mailsA($this->owner->getEmail())[0];
        $this->assertResponseStatus(200, $this->renvoyer($this->manager, $note, self::MESSAGE));
        $mails[] = $this->mailsA($this->owner->getEmail())[0];

        $lien = 'http://localhost/notes-de-frais/' . $note->getId();
        foreach ($mails as $mail) {
            $this->assertMatchesRegularExpression('#<a href="' . preg_quote($lien, '#') . '"[^>]*>Voir ma note</a>#', $mail->getHtmlBody(), $mail->getSubject());
            $this->assertStringContainsString('Voir ma note : ' . $lien . "\n", $mail->getTextBody(), $mail->getSubject());
        }
    }

    public function testAuSecondRenvoiSeulLeNouveauMessageEstRappele(): void
    {
        $this->nommer($this->otherManager, 'Lou', 'Martin');
        $note = $this->noteEnvoyee($this->owner);
        $this->assertResponseStatus(200, $this->renvoyer($this->manager, $note, 'Il manque un justificatif.'));
        $this->assertResponseStatus(200, $this->envoyer($this->owner, $note));

        $this->assertResponseStatus(200, $this->renvoyer($this->otherManager, $note, 'Montant à revoir.'));

        foreach ($this->corps($this->mailsA($this->owner->getEmail())[0]) as $corps) {
            $this->assertStringContainsString('Message laissé par la trésorerie', $corps);
            $this->assertStringContainsString('Montant à revoir.', $corps);
            $this->assertStringNotContainsString('Il manque un justificatif.', $corps);
        }
    }

    public function testLObjetResteEnClairDansLeTexteEtEchappeDansLeHtml(): void
    {
        $objet = 'Cordes <b>neuves</b> pour l\'école d\'aventure';
        $objetEchappe = 'Cordes &lt;b&gt;neuves&lt;/b&gt; pour l&#039;école d&#039;aventure';
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, self::LIGNES_ACHAT, $this->commissionDeTest);
        $this->assertResponseStatus(200, $this->patch($this->owner, $note, ['objet' => $objet]));
        $this->joindreLesJustificatifs($note, 'ligne-1', 'ligne-2');

        $this->assertResponseStatus(200, $this->envoyer($this->owner, $note));
        $mails = $this->getMailerMessages();
        $this->assertResponseStatus(200, $this->renvoyer($this->responsable, $note, 'Il manque l\'accord préalable.'));
        $renvoi = $this->mailsA($this->owner->getEmail())[0];
        $mails[] = $renvoi;
        $this->assertResponseStatus(200, $this->envoyer($this->owner, $note));
        $this->assertResponseStatus(200, $this->patch($this->responsable, $note, ['status' => 'preapproved']));
        $mails[] = $this->mailsA($this->owner->getEmail())[0];

        // Trois à l'envoi (les deux valideurs et le demandeur), un au renvoi, un à la validation.
        $this->assertCount(5, $mails);
        foreach ($mails as $mail) {
            $this->assertStringContainsString($objet, $mail->getSubject());
            $this->assertStringContainsString($objet, $mail->getTextBody());
            $this->assertStringNotContainsString('&#039;', $mail->getTextBody());
            $this->assertStringNotContainsString('&lt;', $mail->getTextBody());
            $this->assertMatchesRegularExpression('#<h1>[^<]*' . preg_quote($objetEchappe, '#') . '[^<]*</h1>#', $mail->getHtmlBody(), $mail->getSubject());
            $this->assertStringNotContainsString('<b>neuves</b>', $mail->getHtmlBody());
        }
        $this->assertStringContainsString('Il manque l\'accord préalable.', $renvoi->getTextBody());
    }

    public function testUneNoteDeSortieValideeNePrevientQueSonDemandeur(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::SUBMITTED);

        $this->assertResponseStatus(200, $this->patch($this->manager, $report, ['status' => 'approved']));

        $this->assertSeulsDestinataires([$this->owner]);
        $mail = $this->mailsA($this->owner->getEmail())[0];
        $this->assertStringContainsString('a été validée par la trésorerie', $mail->getTextBody());
        $this->assertStringNotContainsString('Voir ma note', $mail->getHtmlBody());
    }

    /**
     * @return list<string>
     */
    private function corps(Email $mail): array
    {
        return [html_entity_decode($mail->getTextBody(), \ENT_QUOTES), html_entity_decode($mail->getHtmlBody(), \ENT_QUOTES)];
    }
}
