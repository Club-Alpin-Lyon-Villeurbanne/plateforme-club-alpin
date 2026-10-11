<?php

namespace App\Tests\Api;

use App\Entity\ExpenseReport;
use Symfony\Component\Mime\Email;

/**
 * Mails d'une formation : le type, le lien vers la formation, les lignes avec leur nature et le
 * calcul de la voiture, et la part remboursable avec le total quand un plafond s'applique.
 */
class NoteHorsSortieFormationMailsTest extends ExpenseReportApiTestCase
{
    private const LIEN = 'https://example.org/formation-initiateur';

    public function testLeMailAuxValideursDonneLeTypeEtLeLienVersLaFormation(): void
    {
        $this->envoyerLaFormation(self::LIGNES_FORMATION, 'ligne-1', 'ligne-3', 'ligne-4');

        $mails = $this->mailsA($this->responsable->getEmail());
        $this->assertCount(1, $mails);
        $this->assertSame('Note de frais à valider : Formation initiateur SAE', $mails[0]->getSubject());
        foreach ($this->corps($mails[0]) as $corps) {
            $this->assertStringContainsString('Type : formation', $corps);
            $this->assertStringContainsString('Formation : ' . self::LIEN, $corps);
        }
        $this->assertStringContainsString(sprintf('href="%s"', self::LIEN), $mails[0]->getHtmlBody());
    }

    public function testLeMailAuxValideursDonneLaPartRemboursableAvecLeMontant(): void
    {
        $peage = $this->plafondDuPeage() + 15;
        $this->envoyerLaFormation(json_encode(['lignes' => [['id' => 'ligne-1', 'nature' => 'peage', 'montant' => $peage]]]), 'ligne-1');

        $mails = $this->mailsA($this->responsable->getEmail());
        $this->assertCount(1, $mails);
        foreach ($this->corps($mails[0]) as $corps) {
            $this->assertStringContainsString(sprintf('Montant : %s (dont %s remboursables selon les plafonds du club)', $this->euros($peage), $this->euros($this->plafondDuPeage())), $corps);
            $this->assertStringNotContainsString('À vérifier', $corps);
        }
    }

    public function testSansPlafondAppliqueLeMailNeParlePasDuRemboursable(): void
    {
        $this->envoyerLaFormation(self::LIGNES_FORMATION, 'ligne-1', 'ligne-3', 'ligne-4');

        foreach ([$this->responsable->getEmail(), $this->encadrant->getEmail()] as $adresse) {
            foreach ($this->corps($this->mailsA($adresse)[0]) as $corps) {
                $this->assertStringNotContainsString('remboursables selon les plafonds du club', $corps);
            }
        }
    }

    public function testLeDemandeurRetrouveSesDepensesParNature(): void
    {
        $this->envoyerLaFormation(self::LIGNES_FORMATION, 'ligne-1', 'ligne-3', 'ligne-4');

        $mails = $this->mailsA($this->encadrant->getEmail());
        $this->assertCount(1, $mails);
        foreach ($this->corps($mails[0]) as $corps) {
            $this->assertStringContainsString('Date de la formation : ' . (new \DateTimeImmutable('-3 days'))->format('d/m/Y'), $corps);
            $this->assertStringContainsString('Formation : ' . self::LIEN, $corps);
            $this->assertLignesDeLaFormation($corps);
        }
    }

    public function testLeDemandeurLitLaPartRemboursableSousLeTotal(): void
    {
        $peage = $this->plafondDuPeage() + 15;
        $this->envoyerLaFormation(json_encode(['lignes' => [['id' => 'ligne-1', 'nature' => 'peage', 'montant' => $peage]]]), 'ligne-1');

        $mails = $this->mailsA($this->encadrant->getEmail());
        $this->assertCount(1, $mails);
        foreach ($this->corps($mails[0]) as $corps) {
            $this->assertMatchesRegularExpression(sprintf(
                '/Total : %s\s+dont %s remboursables selon les plafonds du club/u',
                preg_quote($this->euros($peage), '/'),
                preg_quote($this->euros($this->plafondDuPeage()), '/'),
            ), $corps);
        }
    }

    public function testLeRecapitulatifDeLaTresorerieDonneNaturesRemboursableEtLien(): void
    {
        $peage = $this->plafondDuPeage() + 15;
        $note = $this->envoyerLaFormation(json_encode(['lignes' => [
            ['id' => 'ligne-1', 'nature' => 'stage', 'montant' => 280],
            ['id' => 'ligne-2', 'nature' => 'voiture', 'distance' => 220],
            ['id' => 'ligne-3', 'nature' => 'peage', 'montant' => $peage],
            ['id' => 'ligne-4', 'nature' => 'hebergement', 'montant' => 54],
        ]]), 'ligne-1', 'ligne-3', 'ligne-4');
        $declare = 280 + $this->voiture(220) + $peage + 54;
        $remboursable = 280 + $this->voiture(220) + $this->plafondDuPeage() + 54;

        $this->assertResponseStatus(200, $this->patch($this->responsable, $note, ['status' => 'approved']));

        $mails = $this->mailsA($this->adresseTresorerie());
        $this->assertCount(1, $mails);
        foreach ($this->corps($mails[0]) as $corps) {
            $this->assertStringContainsString('Type : formation', $corps);
            $this->assertStringContainsString('Date de la formation : ' . (new \DateTimeImmutable('-3 days'))->format('d/m/Y'), $corps);
            $this->assertStringContainsString('Formation : ' . self::LIEN, $corps);
            $this->assertStringContainsString('Stage', $corps);
            $this->assertStringContainsString(sprintf('Voiture (%s)', $this->calculDeLaVoiture(220)), $corps);
            $this->assertStringContainsString('Péage', $corps);
            $this->assertStringContainsString('Nuit d\'hébergement', $corps);
            $this->assertStringNotContainsString('1 nuit', $corps);
            $this->assertStringContainsString('Total : ' . $this->euros($declare), $corps);
            $this->assertStringContainsString('Remboursable : ' . $this->euros($remboursable), $corps);
            $this->assertStringNotContainsString('remboursables selon les plafonds du club', $corps);
        }
    }

    private function envoyerLaFormation(string $details, string ...$lignesJustifiees): ExpenseReport
    {
        $note = $this->creerFormation($this->encadrant, $details, null, self::LIEN);
        $this->joindreLesJustificatifs($note, ...$lignesJustifiees);
        $this->assertResponseStatus(200, $this->envoyer($this->encadrant, $note));

        return $note;
    }

    private function assertLignesDeLaFormation(string $corps): void
    {
        $this->assertStringContainsString($this->euros(280) . ' - Stage', $corps);
        $this->assertStringContainsString(sprintf('%s - Voiture (%s)', $this->euros($this->voiture(220)), $this->calculDeLaVoiture(220)), $corps);
        $this->assertStringContainsString($this->euros(32) . ' - Péage', $corps);
        $this->assertStringContainsString($this->euros(54) . ' - Nuit d\'hébergement', $corps);
        $this->assertStringNotContainsString('1 nuit', $corps);
    }

    private function voiture(int $kilometres): float
    {
        return round($kilometres * $this->baremeFormation()['tauxKilometrique'], 2);
    }

    /**
     * « 220 km × 0,40 € » avec le taux de la configuration.
     */
    private function calculDeLaVoiture(int $kilometres): string
    {
        return sprintf('%d km × %s', $kilometres, $this->euros($this->baremeFormation()['tauxKilometrique']));
    }

    private function plafondDuPeage(): float
    {
        return (float) $this->baremeFormation()['plafonds']['parNature']['peage']['montant'];
    }

    /**
     * @return array{0: string, 1: string} partie texte, puis HTML sans ses entités
     */
    private function corps(Email $mail): array
    {
        return [html_entity_decode($mail->getTextBody(), \ENT_QUOTES), html_entity_decode(strip_tags($mail->getHtmlBody()), \ENT_QUOTES)];
    }
}
