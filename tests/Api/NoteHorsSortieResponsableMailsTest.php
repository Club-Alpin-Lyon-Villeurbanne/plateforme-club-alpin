<?php

namespace App\Tests\Api;

use App\Entity\User;
use Symfony\Component\Mime\Email;

/**
 * Mails du circuit quand la commission a des responsables : chaque valideur reçoit la note à
 * valider. Un responsable sans adresse mail n'est pas valideur ; sans valideur, aucun mail ne
 * part à la trésorerie.
 */
class NoteHorsSortieResponsableMailsTest extends ExpenseReportApiTestCase
{
    private const SUJET_A_VALIDER = 'Note de frais à valider : Dégaines pour la SAE';

    protected function tearDown(): void
    {
        // L'adresse est unique en base : une adresse vide laissée ici ferait échouer le test suivant.
        $this->em()->getConnection()->executeStatement('UPDATE caf_user SET email_user = NULL WHERE email_user = \'\'');

        parent::tearDown();
    }

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

    public function testLEnvoiNePrevientQueLesValideursEtLeDemandeur(): void
    {
        $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $this->assertSeulsDestinataires([$this->responsable, $this->coResponsable, $this->adherent]);
    }

    public function testSansResponsableSeulLeDemandeurRecoitUnMail(): void
    {
        $this->noteEnvoyee($this->adherent);

        $this->assertSeulsDestinataires([$this->adherent]);
    }

    public function testQuandLeSeulResponsableEstLeDemandeurSeulLeDemandeurRecoitUnMail(): void
    {
        $this->noteEnvoyee($this->responsableAutreCommission, commission: $this->autreCommission);

        $this->assertSeulsDestinataires([$this->responsableAutreCommission]);
        $this->assertSame(['Votre note de frais « Dégaines pour la SAE » est envoyée à la trésorerie.'], $this->sujetsA($this->responsableAutreCommission));
    }

    /**
     * @dataProvider sansAdresseProvider
     */
    public function testUnResponsableSansAdresseNEstPasValideur(?string $adresse): void
    {
        $this->changerLAdresse($this->responsable, $adresse);

        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $this->assertSeulsDestinataires([$this->coResponsable, $this->adherent]);
        $this->assertStringNotContainsString('reçu aussi', $this->seulMailAValider($this->coResponsable)->getTextBody());
        $this->assertSame(['Votre note de frais « Dégaines pour la SAE » est envoyée à Sam PETIT.'], $this->sujetsA($this->adherent));
        $this->assertSame([$this->coResponsable->getId()], array_column($this->show($this->adherent, $note)['body']['valideurs'], 'id'));
    }

    /**
     * @dataProvider sansAdresseProvider
     */
    public function testSansResponsableAvecUneAdresseLaNoteVaALaTresorerie(?string $adresse): void
    {
        $this->changerLAdresse($this->responsable, $adresse);
        $this->changerLAdresse($this->coResponsable, null);

        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $this->assertSeulsDestinataires([$this->adherent]);
        $this->assertSame(['Votre note de frais « Dégaines pour la SAE » est envoyée à la trésorerie.'], $this->sujetsA($this->adherent));
        $this->assertSame([], $this->show($this->adherent, $note)['body']['valideurs']);
    }

    public static function sansAdresseProvider(): iterable
    {
        yield 'adresse nulle' => [null];
        yield 'adresse vide' => [''];
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

        $this->assertSame(['Votre note de frais « Dégaines pour la SAE » est envoyée à Sam PETIT.'], $this->sujetsA($this->responsable));
    }

    public function testApresCorrectionLesValideursRecoiventANouveauLaNote(): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);
        $this->assertResponseStatus(200, $this->renvoyer($this->responsable, $note));

        $this->assertResponseStatus(200, $this->envoyer($this->adherent, $note));

        $this->seulMailAValider($this->responsable);
        $this->seulMailAValider($this->coResponsable);
        $this->assertSeulsDestinataires([$this->responsable, $this->coResponsable, $this->adherent]);
    }

    public function testLaValidationParUnResponsableNePrevientQueLeDemandeur(): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $this->assertResponseStatus(200, $this->patch($this->responsable, $note, ['status' => 'approved']));

        $this->assertSeulsDestinataires([$this->adherent]);
        $this->assertSame(
            ['Votre note de frais « Dégaines pour la SAE » a été validée par Alex DURAND et transmise à la trésorerie.'],
            $this->sujetsA($this->adherent),
        );
    }

    public function testLeRenvoiParUnResponsableEstAttribueASonAuteur(): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $this->assertResponseStatus(200, $this->renvoyer($this->coResponsable, $note, 'Mauvaise commission.'));

        $mail = $this->mailsA($this->adherent->getEmail())[0];
        $this->assertStringContainsString('Message laissé par Sam PETIT :', $mail->getTextBody());
        $this->assertStringContainsString('Mauvaise commission.', $mail->getTextBody());
        $this->assertSeulsDestinataires([$this->adherent]);
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
     * @return list<string>
     */
    private function sujetsA(User $destinataire): array
    {
        return array_map(static fn (Email $mail) => $mail->getSubject(), $this->mailsA($destinataire->getEmail()));
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
