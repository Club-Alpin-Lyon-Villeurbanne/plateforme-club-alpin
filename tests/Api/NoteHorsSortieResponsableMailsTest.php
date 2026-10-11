<?php

namespace App\Tests\Api;

use App\Entity\User;
use Monolog\Handler\TestHandler;
use Symfony\Component\Mime\Email;

/**
 * Mails du circuit quand la commission a des responsables : chaque valideur reçoit la note à
 * valider, la trésorerie ne la reçoit qu'en l'absence de valideur.
 */
class NoteHorsSortieResponsableMailsTest extends ExpenseReportApiTestCase
{
    private const SUJET_A_VALIDER = 'Note de frais à valider : Dégaines pour la SAE';

    public function testChaqueValideurRecoitSonPropreMail(): void
    {
        $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        foreach ([[$this->responsable, 'Bonjour Alex,'], [$this->coResponsable, 'Bonjour Sam,']] as [$valideur, $bonjour]) {
            $mail = $this->seulMailAValider($valideur);
            $this->assertCount(1, $mail->getTo());
            $this->assertSame([], $mail->getBcc());
            $this->assertReponseAuDemandeur($mail, $this->adherent);
            foreach ($this->corps($mail) as $corps) {
                $this->assertStringContainsString($bonjour, $corps);
                $this->assertStringContainsString('Dégaines pour la SAE', $corps);
                $this->assertStringContainsString('Commission de test', $corps);
                $this->assertStringContainsString('86,40', $corps);
                $this->assertStringContainsString('http://localhost/notes-de-frais/', $corps);
            }
        }
    }

    public function testLeMailNommeLAutreDestinataire(): void
    {
        $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        foreach ($this->corps($this->seulMailAValider($this->responsable)) as $corps) {
            $this->assertStringContainsString('Sam PETIT l\'a reçu aussi : la réponse de l\'un de vous deux suffit.', html_entity_decode($corps, \ENT_QUOTES));
        }
        foreach ($this->corps($this->seulMailAValider($this->coResponsable)) as $corps) {
            $this->assertStringContainsString('Alex DURAND l\'a reçu aussi : la réponse de l\'un de vous deux suffit.', html_entity_decode($corps, \ENT_QUOTES));
        }
    }

    public function testAvecTroisValideursLeMailNommeLesDeuxAutres(): void
    {
        $troisieme = $this->signup();
        $this->nommer($troisieme, 'Lou', 'Martin');
        $this->rendreResponsable($troisieme, $this->commissionDeTest);

        $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $this->assertStringContainsString(
            'Lou MARTIN et Sam PETIT l\'ont reçu aussi : la réponse de l\'un de vous suffit.',
            $this->seulMailAValider($this->responsable)->getTextBody(),
        );
    }

    public function testLeDemandeurResponsableNeRecoitPasLaNoteAValider(): void
    {
        $this->noteEnvoyee($this->responsable, commission: $this->commissionDeTest);

        $this->assertSame([], $this->mailsAValider($this->responsable));
        $mail = $this->seulMailAValider($this->coResponsable);
        $this->assertStringNotContainsString('reçu aussi', $mail->getTextBody());
    }

    /**
     * @dataProvider choixProvider
     */
    public function testLeMailAuxValideursNeDitRienDuChoixEntreDonEtRemboursement(bool $remboursement): void
    {
        $this->noteEnvoyee($this->adherent, $remboursement, commission: $this->commissionDeTest);

        foreach ([$this->responsable, $this->coResponsable] as $valideur) {
            $mail = $this->seulMailAValider($valideur);
            foreach ([$mail->getSubject(), ...$this->corps($mail)] as $texte) {
                $this->assertDoesNotMatchRegularExpression('/rembours|\bdon\b|reçu fiscal/i', $texte);
            }
        }
    }

    public static function choixProvider(): iterable
    {
        yield 'remboursement' => [true];
        yield 'don' => [false];
    }

    public function testLaTresorerieNeRecoitPasLaNoteQuandIlYADesValideurs(): void
    {
        $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $this->assertSame([], $this->mailsA($this->adresseTresorerie()));
    }

    public function testSansResponsableLaNoteAValiderPartALaTresorerie(): void
    {
        $this->noteEnvoyee($this->adherent);

        $mails = $this->mailsA($this->adresseTresorerie());
        $this->assertCount(1, $mails);
        $this->assertSame(self::SUJET_A_VALIDER, $mails[0]->getSubject());
        $this->assertStringContainsString("Bonjour,\n", $mails[0]->getTextBody());
        $this->assertStringNotContainsString('reçu aussi', $mails[0]->getTextBody());
    }

    public function testQuandLeSeulResponsableEstLeDemandeurLaNotePartALaTresorerie(): void
    {
        $this->noteEnvoyee($this->responsableAutreCommission, commission: $this->autreCommission);

        $this->assertCount(1, $this->mailsA($this->adresseTresorerie()));
        $this->assertSame([], $this->mailsAValider($this->responsableAutreCommission));
    }

    public function testSansAdresseValideChezLesValideursLaNoteAValiderPartALaTresorerie(): void
    {
        $this->changerLAdresse($this->responsable, 'pas-une-adresse-' . bin2hex(random_bytes(4)));
        $this->changerLAdresse($this->coResponsable, null);

        $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $mails = $this->mailsA($this->adresseTresorerie());
        $this->assertCount(1, $mails);
        $this->assertSame(self::SUJET_A_VALIDER, $mails[0]->getSubject());
        $this->assertReponseAuDemandeur($mails[0], $this->adherent);
        $this->assertTrue($this->getContainer()->get(TestHandler::class)->hasErrorThatContains('aucun valideur n\'a d\'adresse mail valide'));
    }

    public function testUnValideurSansAdresseValideNEmpechePasLesAutresDeRecevoirLaNote(): void
    {
        $this->changerLAdresse($this->responsable, null);

        $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $this->seulMailAValider($this->coResponsable);
        $this->assertSame([], $this->mailsA($this->adresseTresorerie()));
    }

    public function testLeDemandeurApprendAQuiSaNoteEstEnvoyee(): void
    {
        $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $mails = $this->mailsA($this->adherent->getEmail());
        $this->assertCount(1, $mails);
        $this->assertSame('Votre note de frais « Dégaines pour la SAE » est envoyée à Alex DURAND et Sam PETIT.', $mails[0]->getSubject());
        foreach ($this->corps($mails[0]) as $corps) {
            $this->assertStringContainsString('est envoyée à Alex DURAND et Sam PETIT.', $corps);
            $this->assertStringNotContainsString('trésorerie', $corps);
        }
    }

    public function testLeDemandeurResponsableApprendQueSaNoteEstEnvoyeeAuCoResponsable(): void
    {
        $this->noteEnvoyee($this->responsable, commission: $this->commissionDeTest);

        $this->assertSame(
            ['Votre note de frais « Dégaines pour la SAE » est envoyée à Sam PETIT.'],
            array_map(static fn (Email $mail) => $mail->getSubject(), $this->mailsA($this->responsable->getEmail())),
        );
    }

    public function testApresCorrectionLesValideursRecoiventANouveauLaNote(): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);
        $this->assertResponseStatus(200, $this->renvoyer($this->responsable, $note));

        $this->assertResponseStatus(200, $this->envoyer($this->adherent, $note));

        $this->seulMailAValider($this->responsable);
        $this->seulMailAValider($this->coResponsable);
        $this->assertSame([], $this->mailsA($this->adresseTresorerie()));
    }

    public function testLaValidationParUnResponsablePrevientLaTresorerieEtLeDemandeur(): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $this->assertResponseStatus(200, $this->patch($this->responsable, $note, ['status' => 'approved']));

        $recapitulatif = $this->mailsA($this->adresseTresorerie());
        $this->assertCount(1, $recapitulatif);
        $this->assertStringContainsString(sprintf('Validée par Alex DURAND le %s', date('d/m/Y')), $recapitulatif[0]->getTextBody());
        $this->assertStringContainsString('Remboursement demandé', $recapitulatif[0]->getTextBody());
        $this->assertSame(
            'Votre note de frais « Dégaines pour la SAE » a été validée par Alex DURAND et transmise à la trésorerie.',
            $this->mailsA($this->adherent->getEmail())[0]->getSubject(),
        );
        $this->assertSame([], $this->mailsA($this->responsable->getEmail()));
        $this->assertSame([], $this->mailsA($this->coResponsable->getEmail()));
    }

    public function testLeRenvoiParUnResponsableEstAttribueASonAuteur(): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $this->assertResponseStatus(200, $this->renvoyer($this->coResponsable, $note, 'Mauvaise commission.'));

        $mail = $this->mailsA($this->adherent->getEmail())[0];
        $this->assertStringContainsString('Message laissé par Sam PETIT :', $mail->getTextBody());
        $this->assertStringContainsString('Mauvaise commission.', $mail->getTextBody());
        $this->assertSame([], $this->mailsA($this->adresseTresorerie()));
    }

    public function testLesNomsSontEnClairDansLeTexteEtEchappesDansLeHtml(): void
    {
        $this->nommer($this->coResponsable, 'Zoé', 'D\'Arc');

        $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $mail = $this->seulMailAValider($this->responsable);
        $this->assertStringContainsString('Zoé D\'ARC l\'a reçu aussi', $mail->getTextBody());
        $this->assertStringContainsString('Zoé D&#039;ARC', $mail->getHtmlBody());
        $this->assertStringContainsString('est envoyée à Alex DURAND et Zoé D\'ARC.', $this->mailsA($this->adherent->getEmail())[0]->getTextBody());
    }

    private function changerLAdresse(User $utilisateur, ?string $adresse): void
    {
        $this->em()->find(User::class, $utilisateur->getId())->setEmail($adresse);
        $this->em()->flush();
    }

    /**
     * @return list<Email>
     */
    private function mailsAValider(User $destinataire): array
    {
        return array_values(array_filter(
            $this->mailsA($destinataire->getEmail()),
            static fn (Email $mail) => self::SUJET_A_VALIDER === $mail->getSubject(),
        ));
    }

    private function seulMailAValider(User $destinataire): Email
    {
        $mails = $this->mailsAValider($destinataire);
        $this->assertCount(1, $mails, sprintf('Un mail « à valider » pour %s.', $destinataire->getEmail()));

        return $mails[0];
    }

    /**
     * @return list<string>
     */
    private function corps(Email $mail): array
    {
        return [(string) $mail->getTextBody(), (string) $mail->getHtmlBody()];
    }
}
