<?php

namespace App\Tests\Api;

use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * Envoi d'une note hors sortie (brouillon ou note à corriger vers « envoyée ») : contrôles de
 * soumission, puis note figée pour son propriétaire.
 */
class NoteHorsSortieEnvoiTest extends ExpenseReportApiTestCase
{
    private const LIGNES = '{"lignes":[{"id":"ligne-1","montant":64.9,"description":"Dégaines (x6)"},{"id":"ligne-2","montant":21.5,"description":"Cordelette 6 mm"}]}';

    public function testLeDemandeurEnvoieSonAchatEnUneSeuleRequete(): void
    {
        $note = $this->creerAchat($this->owner);
        $this->joindreLesJustificatifs($note, 'ligne-1', 'ligne-2');

        $response = $this->envoyer($this->owner, $note, [
            'details' => self::LIGNES,
            'refundRequired' => false,
            'motPourResponsable' => 'Vu avec Sam à la réunion de commission.',
        ]);

        $this->assertResponseStatus(200, $response);
        $this->assertSame('submitted', $response['body']['status']);
        $envoyee = $this->reload($note);
        $this->assertSame(ExpenseReportStatusEnum::SUBMITTED, $envoyee->getStatus());
        $this->assertSame(self::LIGNES, $envoyee->getDetails());
        $this->assertFalse($envoyee->isRefundRequired());
    }

    // —— Contrôles de soumission ——

    /**
     * @dataProvider sansDepenseProvider
     */
    public function testEnvoiRefuseSansDepense(?string $details): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, $details);

        $this->assertRefusAvec($this->envoyer($this->owner, $note), 'details', 'Il manque au moins une dépense.');
        $this->assertSame(ExpenseReportStatusEnum::DRAFT, $this->reload($note)->getStatus());
    }

    public static function sansDepenseProvider(): iterable
    {
        yield 'aucune ligne enregistrée' => [null];
        yield 'liste vide' => ['{"lignes":[]}'];
    }

    /**
     * @dataProvider montantNonPositifProvider
     */
    public function testEnvoiRefuseAvecUnMontantNulOuNegatif(?float $montant, string $message): void
    {
        $lignes = json_encode(['lignes' => [
            ['id' => 'ligne-1', 'montant' => 64.9, 'description' => 'Dégaines (x6)'],
            ['id' => 'ligne-2', 'montant' => $montant, 'description' => 'Cordelette 6 mm'],
        ]]);
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, $lignes);
        $this->joindreLesJustificatifs($note, 'ligne-1', 'ligne-2');

        $this->assertRefusAvec($this->envoyer($this->owner, $note), 'details', $message);
    }

    public static function montantNonPositifProvider(): iterable
    {
        yield 'montant nul' => [0.0, 'Le montant de la dépense 2 doit être supérieur à zéro.'];
        yield 'montant absent' => [null, 'Il manque le montant de la dépense 2.'];
        yield 'montant négatif' => [-5.0, 'Le montant de la dépense 2 doit être supérieur à zéro.'];
    }

    public function testLesMontantsEnDefautSontTousNommes(): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, '{"lignes":[{"id":"ligne-1","montant":0},{"id":"ligne-2","montant":12},{"id":"ligne-3","montant":-1},{"id":"ligne-4","montant":null},{"id":"ligne-5"}]}');
        $this->joindreLesJustificatifs($note, 'ligne-1', 'ligne-2', 'ligne-3', 'ligne-4', 'ligne-5');

        $reponse = $this->envoyer($this->owner, $note);

        $this->assertRefusAvec($reponse, 'details', 'Les montants des dépenses 1 et 3 doivent être supérieurs à zéro.');
        $this->assertRefusAvec($reponse, 'details', 'Il manque les montants des dépenses 4 et 5.');
    }

    public function testEnvoiRefuseSansJustificatif(): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, self::LIGNES);
        $this->joindreLesJustificatifs($note, 'ligne-1');

        $this->assertRefusAvec($this->envoyer($this->owner, $note), 'details', 'Il manque le justificatif de la dépense 2.');
    }

    public function testEnvoiRefuseSansAucunJustificatif(): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, self::LIGNES);

        $this->assertRefusAvec($this->envoyer($this->owner, $note), 'details', 'Il manque les justificatifs des dépenses 1 et 2.');
    }

    public function testEnvoiRefuseSansChoixEntreDonEtRemboursement(): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, self::LIGNES);
        $this->joindreLesJustificatifs($note, 'ligne-1', 'ligne-2');

        $this->assertRefusAvec($this->patch($this->owner, $note, ['status' => 'submitted']), 'refundRequired', 'Il manque le choix entre remboursement et don.');
    }

    public function testEnvoiRefuseAvecUneDateFuture(): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, self::LIGNES, null, new \DateTimeImmutable('+2 days'));
        $this->joindreLesJustificatifs($note, 'ligne-1', 'ligne-2');

        $this->assertRefusAvec($this->envoyer($this->owner, $note), 'dateDepense', 'La date ne peut pas être dans le futur.');
    }

    public function testUnAchatHorsDelaiEstRefuseAuPremierEnvoi(): void
    {
        $delai = $this->configHorsSortie()['delaiEnvoiJours']['autre'];
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, self::LIGNES, null, new \DateTimeImmutable(sprintf('-%d days', $delai + 1)));
        $this->joindreLesJustificatifs($note, 'ligne-1', 'ligne-2');

        $this->assertRefusAvec($this->envoyer($this->owner, $note), 'dateDepense', sprintf('Le délai de %d jours après la dépense est dépassé.', $delai));
    }

    public function testUnAchatACorrigerSeRenvoieApresLeDelai(): void
    {
        $delai = $this->configHorsSortie()['delaiEnvoiJours']['autre'];
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::REJECTED, self::LIGNES, null, new \DateTimeImmutable(sprintf('-%d days', $delai + 10)));
        $this->joindreLesJustificatifs($note, 'ligne-1', 'ligne-2');

        $this->assertResponseStatus(200, $this->envoyer($this->owner, $note));
        $this->assertSame(ExpenseReportStatusEnum::SUBMITTED, $this->reload($note)->getStatus());
    }

    public function testUnAchatACorrigerRepasseLesControlesDeChaqueEnvoi(): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::REJECTED, self::LIGNES);
        $this->joindreLesJustificatifs($note, 'ligne-1');

        $this->assertRefusAvec($this->envoyer($this->owner, $note), 'details', 'Il manque le justificatif de la dépense 2.');
    }

    public function testChaqueControleEnDefautDonneSonMessage(): void
    {
        $note = $this->creerAchat($this->owner);

        $response = $this->patch($this->owner, $note, ['status' => 'submitted']);

        $this->assertResponseStatus(422, $response);
        $this->assertEqualsCanonicalizing([
            ['propertyPath' => 'details', 'message' => 'Il manque au moins une dépense.'],
            ['propertyPath' => 'refundRequired', 'message' => 'Il manque le choix entre remboursement et don.'],
        ], array_map(
            static fn (array $violation) => ['propertyPath' => $violation['propertyPath'], 'message' => $violation['message']],
            $response['body']['violations'],
        ));
    }

    // —— Une fois envoyée ——

    /**
     * @dataProvider champDeLaNoteProvider
     */
    public function testLeProprietaireNeModifiePlusUneNoteEnvoyee(string $champ, mixed $valeur): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, self::LIGNES);
        $this->joindreLesJustificatifs($note, 'ligne-1', 'ligne-2');
        $this->assertResponseStatus(200, $this->envoyer($this->owner, $note));

        $this->assertRefused($this->patch($this->owner, $note, [$champ => $valeur]), $champ);
    }

    public static function champDeLaNoteProvider(): iterable
    {
        yield 'lignes' => ['details', '{"lignes":[{"id":"ligne-1","montant":640.9,"description":"Dégaines (x60)"}]}'];
        yield 'choix entre don et remboursement' => ['refundRequired', false];
        yield 'objet' => ['objet', 'Cordes pour la SAE'];
        yield 'date' => ['dateDepense', (new \DateTimeImmutable('-10 days'))->format('Y-m-d')];
        yield 'mot pour le responsable' => ['motPourResponsable', 'Vu avec Sam.'];
    }

    public function testLeProprietaireNeChangePlusLaCommissionDUneNoteEnvoyee(): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, self::LIGNES);
        $this->joindreLesJustificatifs($note, 'ligne-1', 'ligne-2');
        $this->assertResponseStatus(200, $this->envoyer($this->owner, $note));

        $this->assertRefused($this->patch($this->owner, $note, ['commission' => '/api/commissions/' . $this->commissionVisible(1)->getId()]), 'commission');
    }

    public function testLeProprietaireNeRemplacePlusUnJustificatifDUneNoteEnvoyee(): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, self::LIGNES);
        $this->joindreLesJustificatifs($note, 'ligne-1', 'ligne-2');
        $this->assertResponseStatus(200, $this->envoyer($this->owner, $note));

        $this->assertResponseStatus(422, $this->uploadAttachment($this->owner, $note, 'ligne-1'));
        $this->assertResponseStatus(422, $this->uploadAttachment($this->owner, $note, 'ligne-3'));
        $this->assertCount(2, $this->reload($note)->getAttachments());
    }
}
