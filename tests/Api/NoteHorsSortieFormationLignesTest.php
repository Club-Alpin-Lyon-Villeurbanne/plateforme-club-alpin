<?php

namespace App\Tests\Api;

use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * Lignes d'une formation : une nature par ligne, des kilomètres pour la voiture, et un justificatif
 * pour toutes les autres natures.
 */
class NoteHorsSortieFormationLignesTest extends ExpenseReportApiTestCase
{
    public function testUneFormationAccepteChaqueNature(): void
    {
        $note = $this->creerFormation($this->encadrant);
        $details = '{"lignes":['
            . '{"id":"ligne-1","nature":"stage","montant":280},'
            . '{"id":"ligne-2","nature":"voiture","distance":220},'
            . '{"id":"ligne-3","nature":"peage","montant":32.5},'
            . '{"id":"ligne-4","nature":"train","montant":48},'
            . '{"id":"ligne-5","nature":"hebergement","montant":54},'
            . '{"id":"ligne-6","nature":"autre","montant":12}]}';

        $this->assertResponseStatus(200, $this->patch($this->encadrant, $note, ['details' => $details]));
        $this->assertSame($details, $this->reload($note)->getDetails());
    }

    /**
     * @dataProvider lignesRefuseesProvider
     */
    public function testLesLignesMalFormeesDUneFormationSontRefusees(string $details, string $message): void
    {
        $note = $this->creerFormation($this->encadrant);

        $this->assertRefusAvec($this->patch($this->encadrant, $note, ['details' => $details]), 'details', $message);
    }

    public static function lignesRefuseesProvider(): iterable
    {
        yield 'nature inconnue' => ['{"lignes":[{"id":"ligne-1","nature":"essence","montant":40}]}', 'Ligne 1 : cette nature de dépense n\'est pas permise pour ce type de note.'];
        yield 'distance hors voiture' => ['{"lignes":[{"id":"ligne-1","nature":"train","montant":40,"distance":120}]}', 'Ligne 1 : seule la voiture se déclare en kilomètres.'];
        yield 'montant sur la voiture' => ['{"lignes":[{"id":"ligne-1","nature":"voiture","montant":88}]}', 'Ligne 1 : la voiture se déclare en kilomètres, sans montant.'];
        yield 'distance négative' => ['{"lignes":[{"id":"ligne-1","nature":"voiture","distance":-5}]}', 'Ligne 1 : la distance doit être un nombre positif.'];
        yield 'distance en texte' => ['{"lignes":[{"id":"ligne-1","nature":"voiture","distance":"220"}]}', 'Ligne 1 : la distance doit être un nombre positif.'];
        yield 'distance à plus de deux décimales' => ['{"lignes":[{"id":"ligne-1","nature":"voiture","distance":12.345}]}', 'Ligne 1 : la distance ne peut pas avoir plus de deux décimales.'];
    }

    /**
     * @dataProvider lignesSansNatureProvider
     */
    public function testUnBrouillonDeFormationGardeUneLigneSansNature(string $details): void
    {
        $note = $this->creerFormation($this->encadrant);

        $this->assertResponseStatus(200, $this->patch($this->encadrant, $note, ['details' => $details]));
        $this->assertSame($details, $this->reload($note)->getDetails());
    }

    public static function lignesSansNatureProvider(): iterable
    {
        yield 'nature absente' => ['{"lignes":[{"id":"ligne-1","montant":40}]}'];
        yield 'nature vide' => ['{"lignes":[{"id":"ligne-1","montant":40,"description":"","nature":null}]}'];
    }

    /**
     * @dataProvider envoiSansNatureProvider
     */
    public function testUneFormationNeSEnvoiePasSansLaNatureDeChaqueLigne(array $lignes, string $message): void
    {
        $note = $this->creerFormation($this->encadrant, json_encode(['lignes' => $lignes]));
        $this->joindreLesJustificatifs($note, ...array_column($lignes, 'id'));

        $this->assertRefusAvec($this->envoyer($this->encadrant, $note), 'details', $message);
    }

    public static function envoiSansNatureProvider(): iterable
    {
        yield 'une ligne' => [
            [['id' => 'ligne-1', 'nature' => 'stage', 'montant' => 280], ['id' => 'ligne-2', 'montant' => 54]],
            'Il manque la nature de la dépense 2.',
        ];
        yield 'deux lignes' => [
            [['id' => 'ligne-1', 'montant' => 40], ['id' => 'ligne-2', 'nature' => 'stage', 'montant' => 280], ['id' => 'ligne-3', 'nature' => null, 'montant' => 12]],
            'Il manque la nature des dépenses 1 et 3.',
        ];
    }

    /**
     * @dataProvider autreSansDescriptionProvider
     */
    public function testUneDepenseAutreNeSEnvoiePasSansDescription(array $lignes, string $message): void
    {
        $note = $this->creerFormation($this->encadrant, json_encode(['lignes' => $lignes]));
        $this->joindreLesJustificatifs($note, ...array_column($lignes, 'id'));

        $this->assertRefusAvec($this->envoyer($this->encadrant, $note), 'details', $message);
    }

    public static function autreSansDescriptionProvider(): iterable
    {
        yield 'description vide' => [
            [['id' => 'ligne-1', 'nature' => 'stage', 'montant' => 280], ['id' => 'ligne-2', 'nature' => 'autre', 'montant' => 95, 'description' => '']],
            'Précisez la dépense 2 : la nature « Autre » demande une description.',
        ];
        yield 'description faite d\'espaces' => [
            [['id' => 'ligne-1', 'nature' => 'autre', 'montant' => 95, 'description' => '   ']],
            'Précisez la dépense 1 : la nature « Autre » demande une description.',
        ];
        yield 'deux lignes sans description' => [
            [['id' => 'ligne-1', 'nature' => 'autre', 'montant' => 95], ['id' => 'ligne-2', 'nature' => 'autre', 'montant' => 12, 'description' => '']],
            'Précisez les dépenses 1 et 2 : la nature « Autre » demande une description.',
        ];
    }

    public function testUneDepenseAutreDecriteSEnvoie(): void
    {
        $note = $this->creerFormation($this->encadrant, '{"lignes":[{"id":"ligne-1","nature":"autre","montant":95,"description":"Livre du moniteur"}]}');
        $this->joindreLesJustificatifs($note, 'ligne-1');

        $this->assertResponseStatus(200, $this->envoyer($this->encadrant, $note));
    }

    public function testUneNatureSurUneLigneDAchatEstRefusee(): void
    {
        $note = $this->creerAchat($this->owner);

        $this->assertRefusAvec(
            $this->patch($this->owner, $note, ['details' => '{"lignes":[{"id":"ligne-1","montant":12,"nature":"stage"}]}']),
            'details',
            'Ligne 1 : cette nature de dépense n\'est pas permise pour ce type de note.',
        );
    }

    public function testUneVoitureSEnvoieSansJustificatif(): void
    {
        $note = $this->creerFormation($this->encadrant, '{"lignes":[{"id":"ligne-1","nature":"voiture","distance":220}]}');

        $this->assertResponseStatus(200, $this->envoyer($this->encadrant, $note));
        $this->assertSame(ExpenseReportStatusEnum::SUBMITTED, $this->reload($note)->getStatus());
    }

    public function testLesAutresNaturesExigentUnJustificatif(): void
    {
        $note = $this->creerFormation($this->encadrant, self::LIGNES_FORMATION);
        $this->joindreLesJustificatifs($note, 'ligne-1', 'ligne-4');

        $this->assertRefusAvec($this->envoyer($this->encadrant, $note), 'details', 'Il manque le justificatif de la dépense 3.');
    }

    public function testUneFormationJustifieeSEnvoie(): void
    {
        $note = $this->creerFormation($this->encadrant, self::LIGNES_FORMATION);
        $this->joindreLesJustificatifs($note, 'ligne-1', 'ligne-3', 'ligne-4');

        $this->assertResponseStatus(200, $this->envoyer($this->encadrant, $note));
    }

    /**
     * @dataProvider voitureSansKilometresProvider
     */
    public function testUneVoitureSansKilometresNeSEnvoiePas(string $details): void
    {
        $note = $this->creerFormation($this->encadrant, $details);

        $this->assertRefusAvec($this->envoyer($this->encadrant, $note), 'details', 'Le montant de la dépense 1 doit être supérieur à zéro.');
    }

    public static function voitureSansKilometresProvider(): iterable
    {
        yield 'zéro kilomètre' => ['{"lignes":[{"id":"ligne-1","nature":"voiture","distance":0}]}'];
        yield 'distance vide' => ['{"lignes":[{"id":"ligne-1","nature":"voiture","distance":null}]}'];
    }

    public function testUneFormationAncienneSEnvoieSansDelai(): void
    {
        $date = (new \DateTimeImmutable('today'))->modify(sprintf('-%d days', $this->configHorsSortie()['delaiEnvoiJours']['autre'] + 60));
        $note = $this->creerFormation($this->encadrant, '{"lignes":[{"id":"ligne-1","nature":"voiture","distance":220}]}', $date);

        $response = $this->envoyer($this->encadrant, $note);

        $this->assertResponseStatus(200, $response);
        $this->assertSame([], array_filter($response['body']['avertissements'], static fn (string $message) => str_contains($message, 'jours')));
    }
}
