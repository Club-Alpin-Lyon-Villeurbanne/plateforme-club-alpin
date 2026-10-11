<?php

namespace App\Tests\Api;

use App\Entity\ExpenseReport;

/**
 * Montants d'une formation selon le barème de la configuration : voiture en kilomètres, plafonds
 * par note ou par ligne, avertissement quand un plafond s'applique, montants figés à l'envoi.
 */
class NoteHorsSortieFormationMontantsTest extends ExpenseReportApiTestCase
{
    public function testLaVoitureSeCompteEnKilometres(): void
    {
        $note = $this->creerFormation($this->encadrant, $this->lignes(['voiture', 220]));

        $montants = $this->montants($note);

        $attendu = round(220 * $this->baremeFormation()['tauxKilometrique'], 2);
        $this->assertSame($attendu, $montants['totalDeclare']);
        $this->assertSame(min($attendu, $this->plafond('voiture')), $montants['totalRemboursable']);
    }

    public function testLePlafondDeLaVoiturePorteSurLaNote(): void
    {
        $this->assertSame('note', $this->baremeFormation()['plafonds']['voiture']['portee']);
        // Chaque trajet reste sous le plafond, leur somme le dépasse.
        $kilometres = (int) ceil($this->plafond('voiture') * 0.75 / $this->baremeFormation()['tauxKilometrique']);
        $note = $this->creerFormation($this->encadrant, $this->lignes(['voiture', $kilometres], ['voiture', $kilometres]));

        $montants = $this->montants($note);

        $this->assertSame(round(2 * $kilometres * $this->baremeFormation()['tauxKilometrique'], 2), $montants['totalDeclare']);
        $this->assertSame($this->plafond('voiture'), $montants['totalRemboursable']);
    }

    public function testDeuxPeagesSontPlafonnesEnsemble(): void
    {
        $this->assertSame('note', $this->baremeFormation()['plafonds']['peage']['portee']);
        $peage = round($this->plafond('peage') * 0.75, 2);
        $note = $this->creerFormation($this->encadrant, $this->lignes(['peage', $peage], ['peage', $peage]));

        $montants = $this->montants($note);

        $this->assertSame(round(2 * $peage, 2), $montants['totalDeclare']);
        $this->assertSame($this->plafond('peage'), $montants['totalRemboursable']);
    }

    public function testLeTrainEstPlafonne(): void
    {
        $train = $this->plafond('train') + 20;
        $note = $this->creerFormation($this->encadrant, $this->lignes(['train', $train]));

        $montants = $this->montants($note);

        $this->assertSame($train, $montants['totalDeclare']);
        $this->assertSame($this->plafond('train'), $montants['totalRemboursable']);
    }

    public function testLHebergementEstPlafonneParNuit(): void
    {
        $this->assertSame('ligne', $this->baremeFormation()['plafonds']['hebergement']['portee']);
        $plafond = $this->plafond('hebergement');
        $note = $this->creerFormation($this->encadrant, $this->lignes(['hebergement', $plafond + 10], ['hebergement', $plafond - 10]));

        $montants = $this->montants($note);

        $this->assertSame(2 * $plafond, $montants['totalDeclare']);
        $this->assertSame(2 * $plafond - 10, $montants['totalRemboursable']);
    }

    public function testLeStageNAPasDePlafond(): void
    {
        $note = $this->creerFormation($this->encadrant, $this->lignes(['stage', 1500.0]));

        $reponse = $this->show($this->encadrant, $note);

        $this->assertSame(1500.0, (float) $reponse['body']['totalDeclare']);
        $this->assertSame(1500.0, (float) $reponse['body']['totalRemboursable']);
        $this->assertSame([], $this->avertissementsDePlafond($reponse['body']['avertissements']));
    }

    public function testUnPlafondAppliqueEstSignaleAvecLeMontantRemboursable(): void
    {
        $peage = $this->plafond('peage') + 15;
        $note = $this->creerFormation($this->encadrant, $this->lignes(['peage', $peage]));

        $avertissements = $this->show($this->encadrant, $note)['body']['avertissements'];

        $this->assertContains(sprintf('dont %s remboursables selon les plafonds du club', $this->euros($this->plafond('peage'))), $avertissements);
    }

    public function testSansPlafondAppliqueAucunAvertissementDePlafond(): void
    {
        $note = $this->creerFormation($this->encadrant, self::LIGNES_FORMATION);

        $reponse = $this->show($this->encadrant, $note);

        $this->assertSame((float) $reponse['body']['totalDeclare'], (float) $reponse['body']['totalRemboursable']);
        $this->assertSame([], $this->avertissementsDePlafond($reponse['body']['avertissements']));
    }

    public function testLeRemboursableEstFigeALEnvoi(): void
    {
        $peage = $this->plafond('peage') + 15;
        $note = $this->creerFormation($this->encadrant, $this->lignes(['peage', $peage]));
        $this->joindreLesJustificatifs($note, 'ligne-1');
        $this->assertResponseStatus(200, $this->envoyer($this->encadrant, $note));

        $envoyee = $this->reload($note);
        $this->assertSame(number_format($peage, 2, '.', ''), $envoyee->getTotalDeclare());
        $this->assertSame(number_format($this->plafond('peage'), 2, '.', ''), $envoyee->getTotalRemboursable());

        // La colonne JSON contient la chaîne `details` elle-même, encodée en JSON.
        $this->em()->getConnection()->executeStatement('UPDATE expense_report SET details = ? WHERE id = ?', [json_encode($this->lignes(['peage', 10.0])), $note->getId()]);

        $reponse = $this->show($this->encadrant, $note);
        $this->assertSame($peage, (float) $reponse['body']['totalDeclare']);
        $this->assertSame($this->plafond('peage'), (float) $reponse['body']['totalRemboursable']);
        $this->assertCount(1, $this->avertissementsDePlafond($reponse['body']['avertissements']));
    }

    /**
     * @return array{totalDeclare: float, totalRemboursable: float}
     */
    private function montants(ExpenseReport $note): array
    {
        $reponse = $this->show($this->encadrant, $note);
        $this->assertResponseStatus(200, $reponse);

        return ['totalDeclare' => (float) $reponse['body']['totalDeclare'], 'totalRemboursable' => (float) $reponse['body']['totalRemboursable']];
    }

    private function plafond(string $nature): float
    {
        return (float) $this->baremeFormation()['plafonds'][$nature]['montant'];
    }

    /**
     * Lignes d'une formation, chacune [nature, montant ou kilomètres pour la voiture].
     *
     * @param array{0: string, 1: float|int} ...$lignes
     */
    private function lignes(array ...$lignes): string
    {
        return json_encode(['lignes' => array_map(static fn (array $ligne, int $index) => [
            'id' => 'ligne-' . ($index + 1),
            'nature' => $ligne[0],
            ('voiture' === $ligne[0] ? 'distance' : 'montant') => $ligne[1],
        ], $lignes, array_keys($lignes))]);
    }

    /**
     * @param list<string> $avertissements
     *
     * @return list<string>
     */
    private function avertissementsDePlafond(array $avertissements): array
    {
        return array_values(array_filter($avertissements, static fn (string $message) => str_contains($message, 'plafonds du club')));
    }
}
