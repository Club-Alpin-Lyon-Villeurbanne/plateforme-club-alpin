<?php

namespace App\Tests\Api;

use App\Entity\ExpenseReport;
use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * Un onglet resté ouvert sur une note renvoie la version qu'il a lue : s'il est périmé, son
 * enregistrement est refusé au lieu d'écraser ce qui a été enregistré ailleurs.
 */
class NoteDeFraisVersionTest extends ExpenseReportApiTestCase
{
    private const DEJA_ENVOYEE = 'Cette note a déjà été envoyée : elle ne peut plus être modifiée.';
    private const AUTRE_ONGLET = 'Cette note a été modifiée dans un autre onglet ou sur un autre appareil.';
    private const DEJA_TRAITEE = 'Cette note a déjà été traitée.';
    private const LIGNES_AILLEURS = '{"lignes":[{"id":"ligne-1","montant":64.9,"description":"Dégaines (x6)"},{"id":"ligne-3","montant":12,"description":"Ajoutée sur le téléphone"}]}';

    public function testChaqueLecteurLitLaVersion(): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        foreach ([$this->adherent, $this->responsable, $this->manager] as $lecteur) {
            $this->assertIsInt($this->show($lecteur, $note)['body']['version']);
        }
    }

    public function testUneDecisionPriseSurUnePagePerimeeEstRefusee(): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);
        $versionDeLaPageOuverte = $this->show($this->responsable, $note)['body']['version'];
        $this->assertResponseStatus(200, $this->renvoyer($this->coResponsable, $note));
        $this->assertResponseStatus(200, $this->envoyer($this->adherent, $this->reload($note)));

        $reponse = $this->patch($this->responsable, $note, ['status' => 'preapproved', 'version' => $versionDeLaPageOuverte]);

        $this->assertResponseStatus(409, $reponse);
        $this->assertSame(ExpenseReportStatusEnum::SUBMITTED, $this->reload($note)->getStatus());
        $this->assertCount(0, $this->getMailerMessages());
    }

    /**
     * La trésorerie décide depuis compta-club, qui renvoie la version de la page affichée.
     */
    public function testUneDecisionDeLaTresorerieSurUnePagePerimeeEstRefusee(): void
    {
        $note = $this->noteEnvoyee($this->adherent);
        $versionDeLaPageOuverte = $this->show($this->manager, $note)['body']['version'];
        $this->assertResponseStatus(200, $this->renvoyer($this->otherManager, $note));
        $this->assertResponseStatus(200, $this->envoyer($this->adherent, $this->reload($note)));
        $versionRenvoyee = $this->reload($note)->getVersion();

        $reponse = $this->patch($this->manager, $note, ['status' => 'approved', 'version' => $versionDeLaPageOuverte]);

        $this->assertResponseStatus(409, $reponse);
        $enBase = $this->reload($note);
        $this->assertSame(ExpenseReportStatusEnum::SUBMITTED, $enBase->getStatus());
        $this->assertSame($versionRenvoyee, $enBase->getVersion());
        $this->assertCount(0, $this->getMailerMessages());
    }

    /**
     * compta-club affiche « Note déjà traitée » sur ce refus : il passe avant celui de la version.
     */
    public function testUneDecisionPerimeeSurUneNoteDejaTraiteeApprendQuElleEstTraitee(): void
    {
        $note = $this->noteEnvoyee($this->adherent);
        $versionDeLaPageOuverte = $this->show($this->manager, $note)['body']['version'];
        $this->assertResponseStatus(200, $this->patch($this->otherManager, $note, ['status' => 'approved']));

        $reponse = $this->patch($this->manager, $note, ['status' => 'rejected', 'commentaireStatut' => 'Il manque un justificatif.', 'version' => $versionDeLaPageOuverte]);

        $this->assertRefusAvec($reponse, 'status', self::DEJA_TRAITEE);
        $this->assertSame(ExpenseReportStatusEnum::APPROVED, $this->reload($note)->getStatus());
        $this->assertCount(0, $this->getMailerMessages());
    }

    public function testUnEnregistrementAvecLaBonneVersionPasseEtLaFaitAvancer(): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, self::LIGNES_ACHAT);
        $version = $this->versionLue($note);

        $reponse = $this->patch($this->owner, $note, ['details' => self::LIGNES_AILLEURS, 'version' => $version]);

        $this->assertResponseStatus(200, $reponse);
        $this->assertSame($version + 1, $reponse['body']['version']);
        $this->assertSame($version + 1, $this->versionLue($note));
    }

    public function testUnEnregistrementSurUneVersionPerimeeEstRefuseSansRienEcrire(): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, self::LIGNES_ACHAT);
        $versionDuPremierOnglet = $this->versionLue($note);
        $this->assertResponseStatus(200, $this->patch($this->owner, $note, ['details' => self::LIGNES_AILLEURS, 'motPourResponsable' => 'Vu avec Sam', 'version' => $versionDuPremierOnglet]));

        $reponse = $this->patch($this->owner, $note, ['details' => self::LIGNES_ACHAT, 'motPourResponsable' => null, 'version' => $versionDuPremierOnglet]);

        $this->assertResponseStatus(409, $reponse);
        $this->assertSame(self::AUTRE_ONGLET, $reponse['body']['detail']);
        $enBase = $this->reload($note);
        $this->assertSame(self::LIGNES_AILLEURS, $enBase->getDetails());
        $this->assertSame('Vu avec Sam', $enBase->getMotPourResponsable());
        $this->assertSame($versionDuPremierOnglet + 1, $enBase->getVersion());
    }

    public function testUnEnvoiDepuisUnOngletPerimeEstRefuse(): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, self::LIGNES_ACHAT);
        $this->joindreLesJustificatifs($note, 'ligne-1', 'ligne-2');
        $versionPerimee = $this->versionLue($note);
        $this->assertResponseStatus(200, $this->patch($this->owner, $note, ['objet' => 'Dégaines et cordelette', 'version' => $versionPerimee]));

        $reponse = $this->envoyer($this->owner, $note, ['version' => $versionPerimee]);

        $this->assertResponseStatus(409, $reponse);
        $this->assertSame(ExpenseReportStatusEnum::DRAFT, $this->reload($note)->getStatus());
        $this->assertCount(0, $this->getMailerMessages());
    }

    public function testSansVersionLeDernierEnregistrementPasseCommeAvant(): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, self::LIGNES_ACHAT);
        $this->assertResponseStatus(200, $this->patch($this->owner, $note, ['details' => self::LIGNES_AILLEURS]));

        $this->assertResponseStatus(200, $this->patch($this->owner, $note, ['details' => self::LIGNES_ACHAT]));

        $this->assertSame(self::LIGNES_ACHAT, $this->reload($note)->getDetails());
    }

    public function testUneVersionQuiNEstPasUnEntierEstRefusee(): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, self::LIGNES_ACHAT);

        $this->assertResponseStatus(400, $this->patch($this->owner, $note, ['details' => self::LIGNES_AILLEURS, 'version' => '1']));
        $this->assertSame(self::LIGNES_ACHAT, $this->reload($note)->getDetails());
    }

    public function testUnJustificatifNeFaitPasAvancerLaVersion(): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, self::LIGNES_ACHAT);
        $version = $this->versionLue($note);

        $this->joindreLesJustificatifs($note, 'ligne-1');

        $this->assertSame($version, $this->versionLue($note));
    }

    /**
     * @dataProvider champsDuDemandeurProvider
     */
    public function testUnOngletResteOuvertApresLEnvoiApprendQueLaNoteEstPartie(string $champ, mixed $valeur): void
    {
        $note = $this->noteEnvoyee($this->owner, commission: $this->commissionDeTest);
        $versionAvantLEnvoi = $this->versionLue($note) - 1;

        $reponse = $this->patch($this->owner, $note, [$champ => $valeur, 'version' => $versionAvantLEnvoi]);

        $this->assertRefusAvec($reponse, $champ, self::DEJA_ENVOYEE);
    }

    public function testUnAutreQueLeDemandeurApprendQueLaNoteNEstPasLaSienne(): void
    {
        $note = $this->noteEnvoyee($this->owner, commission: $this->commissionDeTest);

        $reponse = $this->patch($this->manager, $note, ['details' => self::LIGNES_AILLEURS]);

        $this->assertRefusAvec($reponse, 'details', 'Seul le demandeur peut modifier cette note.');
    }

    public static function champsDuDemandeurProvider(): iterable
    {
        yield 'lignes' => ['details', self::LIGNES_AILLEURS];
        yield 'choix' => ['refundRequired', false];
        yield 'mot pour le responsable' => ['motPourResponsable', 'Ajouté après coup'];
    }

    private function versionLue(ExpenseReport $note): int
    {
        $lecture = $this->show($this->reload($note)->getUser(), $note);
        $this->assertResponseStatus(200, $lecture);

        return $lecture['body']['version'];
    }
}
