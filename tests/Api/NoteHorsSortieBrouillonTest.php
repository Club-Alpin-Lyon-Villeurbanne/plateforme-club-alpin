<?php

namespace App\Tests\Api;

use App\Utils\Enums\ExpenseReportStatusEnum;
use App\Utils\Enums\ExpenseReportTypeEnum;

/**
 * Brouillon d'une note hors sortie : relecture, modification par PATCH, droits et justificatifs.
 */
class NoteHorsSortieBrouillonTest extends ExpenseReportApiTestCase
{
    private const LIGNES = '{"lignes":[{"id":"ligne-1","montant":64.9,"description":"Dégaines (x6)"},{"id":"ligne-2","montant":21.5,"description":"Cordelette 6 mm"}]}';

    // —— Relecture et modification ——

    public function testLeBrouillonSeRelitParSonIdentifiant(): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, self::LIGNES);

        $response = $this->show($this->owner, $note);

        $this->assertResponseStatus(200, $response);
        $this->assertSame('autre', $response['body']['type']);
        $this->assertSame($this->commissionVisible()->getId(), $response['body']['commission']['id']);
        $this->assertSame('Dégaines pour la SAE', $response['body']['objet']);
        $this->assertSame((new \DateTimeImmutable('-3 days'))->format('Y-m-d'), $response['body']['dateDepense']);
        $this->assertSame(self::LIGNES, $response['body']['details']);
    }

    public function testLeProprietaireModifieSonBrouillon(): void
    {
        $note = $this->creerAchat($this->owner);
        $autreCommission = $this->commissionVisible(1);
        $date = (new \DateTimeImmutable('-10 days'))->format('Y-m-d');

        $response = $this->patch($this->owner, $note, [
            'details' => self::LIGNES,
            'commission' => '/api/commissions/' . $autreCommission->getId(),
            'objet' => 'Cordes pour la SAE',
            'dateDepense' => $date,
            'motPourResponsable' => 'Vu avec Sam à la réunion de commission.',
        ]);

        $this->assertResponseStatus(200, $response);
        $enregistree = $this->reload($note);
        $this->assertSame(self::LIGNES, $enregistree->getDetails());
        $this->assertSame($autreCommission->getId(), $enregistree->getCommission()->getId());
        $this->assertSame('Cordes pour la SAE', $enregistree->getObjet());
        $this->assertSame($date, $enregistree->getDateDepense()->format('Y-m-d'));
        $this->assertSame('Vu avec Sam à la réunion de commission.', $enregistree->getMotPourResponsable());
        $this->assertSame(ExpenseReportStatusEnum::DRAFT, $enregistree->getStatus());
    }

    public function testLeProprietaireCorrigeUneNoteRenvoyee(): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::REJECTED);
        $champs = ['details', 'commission', 'objet', 'dateDepense', 'motPourResponsable'];

        $response = $this->patch($this->owner, $note, array_combine($champs, array_map($this->nouvelleValeur(...), $champs)));

        $this->assertResponseStatus(200, $response);
        $this->assertSame('Cordes pour la SAE', $this->reload($note)->getObjet());
    }

    /**
     * @dataProvider champDeLaDemandeProvider
     */
    public function testLeProprietaireNeModifiePlusUneNoteTransmise(string $champ): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::APPROVED);

        $this->assertRefused($this->patch($this->owner, $note, [$champ => $this->nouvelleValeur($champ)]), $champ);
    }

    /**
     * @dataProvider champDeLaDemandeProvider
     */
    public function testUnGestionnaireNeModifiePasLeBrouillonDUnAdherent(string $champ): void
    {
        $note = $this->creerAchat($this->owner);

        $this->assertRefused($this->patch($this->manager, $note, [$champ => $this->nouvelleValeur($champ)]), $champ);
    }

    public static function champDeLaDemandeProvider(): iterable
    {
        foreach (['details', 'commission', 'objet', 'dateDepense', 'motPourResponsable'] as $champ) {
            yield $champ => [$champ];
        }
    }

    public function testRenvoyerLesValeursEnregistreesNEstPasUneModification(): void
    {
        $note = $this->creerAchat($this->owner);

        $this->assertResponseStatus(200, $this->patch($this->manager, $note, [
            'commission' => '/api/commissions/' . $this->commissionVisible()->getId(),
            'objet' => 'Dégaines pour la SAE',
            'dateDepense' => (new \DateTimeImmutable('-3 days'))->format('Y-m-d'),
        ]));
    }

    public function testUnTiersNeModifiePasLeBrouillon(): void
    {
        $note = $this->creerAchat($this->owner);

        $this->assertResponseStatus(403, $this->patch($this->stranger, $note, ['objet' => 'Cordes pour la SAE']));
        $this->assertSame('Dégaines pour la SAE', $this->reload($note)->getObjet());
    }

    public function testLeTypeNeChangePasApresLaCreation(): void
    {
        $note = $this->creerAchat($this->owner);

        $this->assertResponseStatus(200, $this->patch($this->owner, $note, ['type' => 'formation']));
        $this->assertSame(ExpenseReportTypeEnum::AUTRE, $this->reload($note)->getType());
    }

    // —— Justificatifs ——

    public function testChaqueJustificatifSeRattacheASaLigneEtSeRemplace(): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, self::LIGNES);

        $this->assertResponseStatus(201, $this->uploadAttachment($this->owner, $note, 'ligne-1'));
        $this->assertResponseStatus(201, $this->uploadAttachment($this->owner, $note, 'ligne-1'));
        $this->assertResponseStatus(201, $this->uploadAttachment($this->owner, $note, 'ligne-2'));

        $justificatifs = $this->listAttachments($this->owner, $note)['body']['data'];
        $this->assertEqualsCanonicalizing(['ligne-1', 'ligne-2'], array_column($justificatifs, 'expenseId'));
    }

    private function nouvelleValeur(string $champ): mixed
    {
        return match ($champ) {
            'details' => self::LIGNES,
            'commission' => '/api/commissions/' . $this->commissionVisible(1)->getId(),
            'objet' => 'Cordes pour la SAE',
            'dateDepense' => (new \DateTimeImmutable('-10 days'))->format('Y-m-d'),
            'motPourResponsable' => 'Vu avec Sam à la réunion de commission.',
        };
    }
}
