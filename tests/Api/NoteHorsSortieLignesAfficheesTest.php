<?php

namespace App\Tests\Api;

use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * Lignes telles que le détail et les mails les montrent, calculées par le serveur : libellé,
 * détail du calcul et montant retenu. Une note envoyée garde le taux kilométrique de son envoi.
 */
class NoteHorsSortieLignesAfficheesTest extends ExpenseReportApiTestCase
{
    private const VOITURE = '{"lignes":[{"id":"ligne-1","nature":"voiture","distance":220}]}';

    public function testUneFormationExposeSesLignesParNature(): void
    {
        $note = $this->creerFormation($this->encadrant, self::LIGNES_FORMATION);

        $lignes = $this->lignesAffichees($note);

        $this->assertSame([
            ['id' => 'ligne-1', 'nature' => 'stage', 'libelle' => 'Stage', 'detail' => null, 'montant' => 280.0],
            ['id' => 'ligne-2', 'nature' => 'voiture', 'libelle' => 'Voiture', 'detail' => $this->calculDeLaVoiture(220, $this->taux()), 'montant' => round(220 * $this->taux(), 2)],
            ['id' => 'ligne-3', 'nature' => 'peage', 'libelle' => 'Péage', 'detail' => null, 'montant' => 32.0],
            ['id' => 'ligne-4', 'nature' => 'hebergement', 'libelle' => 'Nuit d\'hébergement', 'detail' => null, 'montant' => 54.0],
        ], $lignes);
    }

    public function testUnAchatExposeSesLignesParDescription(): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, self::LIGNES_ACHAT);

        $this->assertSame([
            ['id' => 'ligne-1', 'nature' => null, 'libelle' => 'Dégaines (x6)', 'detail' => null, 'montant' => 64.9],
            ['id' => 'ligne-2', 'nature' => null, 'libelle' => 'Cordelette 6 mm', 'detail' => null, 'montant' => 21.5],
        ], $this->lignesAffichees($note, $this->owner));
    }

    public function testUneNoteDeSortieNAPasDeLignesAffichees(): void
    {
        $note = $this->createReport($this->owner, ExpenseReportStatusEnum::DRAFT);

        $this->assertArrayNotHasKey('lignesAffichees', $this->show($this->owner, $note)['body']);
    }

    public function testLEnvoiFigeLeTauxKilometrique(): void
    {
        $note = $this->envoyerLaVoiture();

        $details = json_decode($this->reload($note)->getDetails(), true);

        $this->assertEquals($this->taux(), $details['tauxKilometrique']);
        $this->assertSame([['id' => 'ligne-1', 'nature' => 'voiture', 'distance' => 220]], $details['lignes']);
    }

    public function testUneNoteEnvoyeeGardeLeTauxDeSonEnvoi(): void
    {
        $note = $this->envoyerLaVoiture();
        $ancienTaux = $this->taux() + 0.1;
        $this->changerLeTauxFige($note, $ancienTaux);

        $reponse = $this->show($this->responsable, $note);

        $montant = round(220 * $ancienTaux, 2);
        $this->assertSame($montant, (float) $reponse['body']['totalDeclare']);
        $this->assertSame($this->calculDeLaVoiture(220, $ancienTaux), $reponse['body']['lignesAffichees'][0]['detail']);
        $this->assertSame($montant, (float) $reponse['body']['lignesAffichees'][0]['montant']);
    }

    public function testUneNoteRenvoyeeSuitLeTauxEnVigueur(): void
    {
        $note = $this->envoyerLaVoiture();
        $this->changerLeTauxFige($note, $this->taux() + 0.1);
        $this->assertResponseStatus(200, $this->renvoyer($this->responsable, $note));

        $ligne = $this->lignesAffichees($note)[0];

        $this->assertSame($this->calculDeLaVoiture(220, $this->taux()), $ligne['detail']);
        $this->assertSame(round(220 * $this->taux(), 2), (float) $ligne['montant']);
    }

    private function envoyerLaVoiture(): ExpenseReport
    {
        $note = $this->creerFormation($this->encadrant, self::VOITURE);
        $this->assertResponseStatus(200, $this->envoyer($this->encadrant, $note));

        return $note;
    }

    /**
     * Comme si le barème avait changé depuis l'envoi : le taux et les montants figés restent cohérents.
     */
    private function changerLeTauxFige(ExpenseReport $note, float $taux): void
    {
        $details = json_decode($this->reload($note)->getDetails(), true);
        $details['tauxKilometrique'] = $taux;
        $montant = number_format(220 * $taux, 2, '.', '');
        // La colonne JSON contient la chaîne `details` elle-même, encodée en JSON.
        $this->em()->getConnection()->executeStatement(
            'UPDATE expense_report SET details = ?, total_declare = ?, total_remboursable = ? WHERE id = ?',
            [json_encode(json_encode($details)), $montant, $montant, $note->getId()],
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function lignesAffichees(ExpenseReport $note, ?User $lecteur = null): array
    {
        $reponse = $this->show($lecteur ?? $this->encadrant, $note);
        $this->assertResponseStatus(200, $reponse);

        // Un montant rond arrive en entier dans le JSON.
        return array_map(static fn (array $ligne) => array_merge($ligne, ['montant' => (float) $ligne['montant']]), $reponse['body']['lignesAffichees']);
    }

    private function taux(): float
    {
        return (float) $this->baremeFormation()['tauxKilometrique'];
    }

    private function calculDeLaVoiture(int $kilometres, float $taux): string
    {
        return sprintf('%d km × %s', $kilometres, $this->euros($taux));
    }
}
