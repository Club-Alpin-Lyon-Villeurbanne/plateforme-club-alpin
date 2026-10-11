<?php

namespace App\Tests\Api;

use App\Entity\ExpenseReport;
use App\Utils\Enums\ExpenseReportStatusEnum;
use Monolog\Handler\TestHandler;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Mails à l'envoi d'une note hors sortie : l'adresse de la trésorerie reçoit la note à valider,
 * le demandeur apprend qu'elle lui est transmise.
 */
class NoteHorsSortieMailsTest extends ExpenseReportApiTestCase
{
    private const ADRESSE_TRESORERIE_ENV = 'NOTES_DE_FRAIS_EMAIL_TRESORERIE';
    private const LIGNES = '{"lignes":[{"id":"ligne-1","montant":64.9,"description":"Dégaines (x6)"},{"id":"ligne-2","montant":21.5,"description":"Cordelette 6 mm"}]}';

    /** @var array{env: ?string, server: ?string} */
    private array $adresseTresorerieDOrigine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adresseTresorerieDOrigine = [
            'env' => $_ENV[self::ADRESSE_TRESORERIE_ENV] ?? null,
            'server' => $_SERVER[self::ADRESSE_TRESORERIE_ENV] ?? null,
        ];
    }

    protected function tearDown(): void
    {
        foreach (['env' => '_ENV', 'server' => '_SERVER'] as $cle => $superGlobale) {
            if (null === $this->adresseTresorerieDOrigine[$cle]) {
                unset($GLOBALS[$superGlobale][self::ADRESSE_TRESORERIE_ENV]);
            } else {
                $GLOBALS[$superGlobale][self::ADRESSE_TRESORERIE_ENV] = $this->adresseTresorerieDOrigine[$cle];
            }
        }

        parent::tearDown();
    }

    public function testLEnvoiPrevientLaTresorerie(): void
    {
        $this->owner->setFirstname('Sam')->setLastname('Petit');
        $this->em()->flush();
        $note = $this->noteAEnvoyer();

        $this->assertResponseStatus(200, $this->envoyer($this->owner, $note, ['motPourResponsable' => 'Vu avec Alex à la réunion de commission.']));

        $mails = $this->mailsA($this->adresseTresorerie());
        $this->assertCount(1, $mails);
        $this->assertSame('Note de frais à valider : Dégaines pour la SAE', $mails[0]->getSubject());
        foreach ([$mails[0]->getTextBody(), $mails[0]->getHtmlBody()] as $corps) {
            $this->assertStringContainsStringIgnoringCase('Sam Petit', $corps);
            $this->assertStringContainsString('achat', $corps);
            $this->assertStringContainsString($this->commissionVisible()->getTitle(), $corps);
            $this->assertStringContainsString('Dégaines pour la SAE', $corps);
            $this->assertStringContainsString('86,40', $corps);
            $this->assertStringContainsString('Vu avec Alex à la réunion de commission.', $corps);
        }
    }

    /**
     * @dataProvider choixProvider
     */
    public function testLeMailALaTresorerieNeDitRienDuChoixEntreDonEtRemboursement(bool $remboursement): void
    {
        $note = $this->noteAEnvoyer();

        $this->assertResponseStatus(200, $this->envoyer($this->owner, $note, ['refundRequired' => $remboursement]));

        $mails = $this->mailsA($this->adresseTresorerie());
        $this->assertCount(1, $mails);
        foreach ([$mails[0]->getSubject(), $mails[0]->getTextBody(), $mails[0]->getHtmlBody()] as $texte) {
            $this->assertDoesNotMatchRegularExpression('/rembours|\bdon\b|reçu fiscal/i', $texte);
        }
    }

    public static function choixProvider(): iterable
    {
        yield 'remboursement' => [true];
        yield 'don' => [false];
    }

    public function testLeMailALaTresorerieDonneLesAvertissements(): void
    {
        $note = $this->noteAEnvoyer('{"lignes":[{"id":"ligne-1","montant":5000,"description":"Matelas de réception"}]}', ['ligne-1']);

        $response = $this->envoyer($this->owner, $note);

        $this->assertResponseStatus(200, $response);
        $this->assertNotEmpty($response['body']['avertissements']);
        $mail = $this->mailsA($this->adresseTresorerie())[0];
        foreach ($response['body']['avertissements'] as $avertissement) {
            $this->assertStringContainsString($avertissement, $mail->getTextBody());
            $this->assertStringContainsString(htmlspecialchars($avertissement), $mail->getHtmlBody());
        }
    }

    public function testLeDemandeurApprendQueSaNoteEstTransmiseALaTresorerie(): void
    {
        $note = $this->noteAEnvoyer();

        $this->assertResponseStatus(200, $this->envoyer($this->owner, $note));

        $mails = $this->mailsA($this->owner->getEmail());
        $this->assertCount(1, $mails);
        $this->assertSame('Votre note de frais « Dégaines pour la SAE » est transmise à la trésorerie.', $mails[0]->getSubject());
        foreach ([$mails[0]->getTextBody(), $mails[0]->getHtmlBody()] as $corps) {
            $this->assertStringContainsString('est transmise à la trésorerie', $corps);
            $this->assertStringContainsString('Dégaines (x6)', $corps);
            $this->assertStringContainsString('Cordelette 6 mm', $corps);
            $this->assertStringContainsString('86,40', $corps);
            $this->assertStringNotContainsString('comptabilité', $corps);
        }
    }

    public function testUnRenvoiApresCorrectionPrevientAussiLaTresorerie(): void
    {
        $note = $this->noteAEnvoyer(self::LIGNES, ['ligne-1', 'ligne-2'], ExpenseReportStatusEnum::REJECTED);

        $this->assertResponseStatus(200, $this->envoyer($this->owner, $note));

        $this->assertCount(1, $this->mailsA($this->adresseTresorerie()));
        $this->assertCount(1, $this->mailsA($this->owner->getEmail()));
    }

    public function testUnEnvoiRefuseNEnvoieAucunMail(): void
    {
        $note = $this->noteAEnvoyer(self::LIGNES, ['ligne-1']);

        $this->assertResponseStatus(422, $this->envoyer($this->owner, $note));

        $this->assertCount(0, $this->getMailerMessages());
    }

    public function testSansAdresseDeLaTresorerieLEnvoiAboutitEtLOubliEstSignale(): void
    {
        $note = $this->noteAEnvoyer();
        $_ENV[self::ADRESSE_TRESORERIE_ENV] = $_SERVER[self::ADRESSE_TRESORERIE_ENV] = '';

        $this->assertResponseStatus(200, $this->envoyer($this->owner, $note));

        $this->assertCount(1, $this->getMailerMessages());
        $this->assertCount(1, $this->mailsA($this->owner->getEmail()));
        $this->assertTrue($this->getContainer()->get(TestHandler::class)->hasErrorThatContains(self::ADRESSE_TRESORERIE_ENV));
    }

    public function testUneNoteDeSortieNePrevientPasLaTresorerie(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::DRAFT);

        $this->assertResponseStatus(200, $this->patch($this->owner, $report, ['status' => 'submitted']));

        $this->assertCount(0, $this->mailsA($this->adresseTresorerie()));
        $mails = $this->mailsA($this->owner->getEmail());
        $this->assertCount(1, $mails);
        $this->assertStringContainsString('est envoyée à la comptabilité', $mails[0]->getTextBody());
    }

    /**
     * @param list<string> $justificatifs lignes qui reçoivent un justificatif
     */
    private function noteAEnvoyer(string $details = self::LIGNES, array $justificatifs = ['ligne-1', 'ligne-2'], ExpenseReportStatusEnum $status = ExpenseReportStatusEnum::DRAFT): ExpenseReport
    {
        $note = $this->creerAchat($this->owner, $status, $details);
        $this->joindreLesJustificatifs($note, ...$justificatifs);

        return $note;
    }

    private function adresseTresorerie(): string
    {
        $adresse = $_ENV[self::ADRESSE_TRESORERIE_ENV] ?? '';
        $this->assertNotSame('', $adresse, 'L\'adresse de la trésorerie doit être définie pour les tests.');

        return $adresse;
    }

    /**
     * @return list<Email>
     */
    private function mailsA(string $adresse): array
    {
        return array_values(array_filter(
            $this->getMailerMessages(),
            static fn (Email $mail) => \in_array($adresse, array_map(static fn (Address $destinataire) => $destinataire->getAddress(), $mail->getTo()), true),
        ));
    }
}
