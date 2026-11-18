<?php

namespace App\Tests\Api;

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
            $this->assertStringContainsString('Type : Formation', $corps);
            $this->assertStringContainsString('Date de la formation : ' . (new \DateTimeImmutable('-3 days'))->format('d/m/Y'), $corps);
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
            $this->assertStringContainsString(sprintf('Montant : %s (dont %s dans les plafonds du club)', $this->euros($peage), $this->euros($this->plafondDuPeage())), $corps);
            $this->assertStringNotContainsString('À vérifier', $corps);
        }
    }

    public function testSansPlafondAppliqueLeMailNeParlePasDuRemboursable(): void
    {
        $this->envoyerLaFormation(self::LIGNES_FORMATION, 'ligne-1', 'ligne-3', 'ligne-4');

        foreach ([$this->responsable->getEmail(), $this->encadrant->getEmail()] as $adresse) {
            foreach ($this->corps($this->mailsA($adresse)[0]) as $corps) {
                $this->assertStringNotContainsString('dans les plafonds du club', $corps);
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
                '/Total : %s\s+dont %s dans les plafonds du club/u',
                preg_quote($this->euros($peage), '/'),
                preg_quote($this->euros($this->plafondDuPeage()), '/'),
            ), $corps);
        }
    }

    public function testUnDonNePrometNiRemboursementNiRecu(): void
    {
        $peage = $this->plafondDuPeage() + 15;
        $note = $this->creerFormation($this->encadrant, json_encode(['lignes' => [['id' => 'ligne-1', 'nature' => 'peage', 'montant' => $peage]]]), null, self::LIEN);
        $this->joindreLesJustificatifs($note, 'ligne-1');

        $this->assertResponseStatus(200, $this->envoyer($this->encadrant, $note, ['refundRequired' => false]));

        foreach ($this->corps($this->mailsA($this->encadrant->getEmail())[0]) as $corps) {
            $this->assertStringContainsString(sprintf('dont %s dans les plafonds du club', $this->euros($this->plafondDuPeage())), $corps);
            $this->assertStringContainsString('Don au club.', $corps);
            $this->assertStringNotContainsString('remboursables', $corps);
            $this->assertStringNotContainsString('reçu', $corps);
        }
        // Le responsable ne doit pas deviner le choix : son mail garde la même phrase.
        foreach ($this->corps($this->mailsA($this->responsable->getEmail())[0]) as $corps) {
            $this->assertStringContainsString(sprintf('dont %s dans les plafonds du club', $this->euros($this->plafondDuPeage())), $corps);
            $this->assertStringNotContainsString('remboursables', $corps);
        }
    }

    public function testUneDepenseAutreEstDecriteDansLeMailDuDemandeur(): void
    {
        $this->envoyerLaFormation('{"lignes":[{"id":"ligne-1","nature":"autre","montant":95,"description":"Livre du moniteur"}]}', 'ligne-1');

        foreach ($this->corps($this->mailsA($this->encadrant->getEmail())[0]) as $corps) {
            $this->assertStringContainsString($this->euros(95) . ' - Autre (Livre du moniteur)', $corps);
        }
    }

    private function envoyerLaFormation(string $details, string ...$lignesJustifiees): void
    {
        $note = $this->creerFormation($this->encadrant, $details, null, self::LIEN);
        $this->joindreLesJustificatifs($note, ...$lignesJustifiees);
        $this->assertResponseStatus(200, $this->envoyer($this->encadrant, $note));
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
