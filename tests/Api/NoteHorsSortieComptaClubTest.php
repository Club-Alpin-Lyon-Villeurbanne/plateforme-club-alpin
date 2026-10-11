<?php

namespace App\Tests\Api;

use App\Entity\Commission;
use App\Entity\Evt;
use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * La liste de compta-club, `/admin/notes-de-frais` : les notes de sortie, inchangées, et les notes
 * hors sortie qui attendent la trésorerie ou qu'elle a traitées, avec ce qu'il faut pour les traiter.
 */
class NoteHorsSortieComptaClubTest extends ExpenseReportApiTestCase
{
    private const LISTE = '/api/admin/notes-de-frais';

    // Nom unique : les notes de la base de test s'accumulent d'un passage à l'autre.
    private string $nom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->nom = 'Lefèvre' . bin2hex(random_bytes(4));
        $this->nommer($this->adherent, 'Élise', $this->nom);
    }

    public function testLaListeRenvoieLesNotesHorsSortieQuiAttendentLaTresorerieOuQuElleATraitees(): void
    {
        $noteDeSortie = $this->noteDeSortie(ExpenseReportStatusEnum::SUBMITTED);
        $attendues = [$noteDeSortie->getId()];
        foreach ([ExpenseReportStatusEnum::PREAPPROVED, ExpenseReportStatusEnum::APPROVED, ExpenseReportStatusEnum::ACCOUNTED, ExpenseReportStatusEnum::REJECTED] as $statut) {
            $attendues[] = $this->achatAuStatut($statut, $this->commissionDeTest)->getId();
        }
        $attendues[] = $this->achatAuStatut(ExpenseReportStatusEnum::SUBMITTED, null)->getId();
        $brouillon = $this->achatAuStatut(ExpenseReportStatusEnum::DRAFT, null);
        $chezLeResponsable = $this->achatAuStatut(ExpenseReportStatusEnum::SUBMITTED, $this->commissionDeTest);

        $ids = $this->ids(['user.lastname' => $this->nom]);

        $this->assertEqualsCanonicalizing($attendues, $ids);
        $this->assertNotContains($brouillon->getId(), $ids);
        $this->assertNotContains($chezLeResponsable->getId(), $ids);
    }

    public function testAucuneNoteHorsSortieBrouillonOuChezUnResponsableNeSort(): void
    {
        $brouillonDeSortie = $this->noteDeSortie(ExpenseReportStatusEnum::DRAFT);
        $brouillon = $this->achatAuStatut(ExpenseReportStatusEnum::DRAFT, null);
        $chezLeResponsable = $this->achatAuStatut(ExpenseReportStatusEnum::SUBMITTED, $this->commissionDeTest);

        foreach ([[], ['inclure_brouillons' => 'true']] as $filtres) {
            $notes = $this->notes($filtres);
            foreach ($notes as $note) {
                if ('sortie' !== $note['type']) {
                    $this->assertNotSame('draft', $note['status'], json_encode($note));
                    $this->assertFalse('submitted' === $note['status'] && [] !== $note['valideurs'], json_encode($note));
                }
            }
            $this->assertNotContains($brouillon->getId(), array_column($notes, 'id'));
            $this->assertNotContains($chezLeResponsable->getId(), array_column($notes, 'id'));
        }

        // Inchangé pour les notes de sortie.
        $this->assertContains($brouillonDeSortie->getId(), array_column($this->notes(['inclure_brouillons' => 'true']), 'id'));
        $this->assertNotContains($brouillonDeSortie->getId(), array_column($this->notes(), 'id'));
    }

    /**
     * La règle des valideurs est celle de `ValideursNoteHorsSortie` : un responsable sans adresse
     * ne valide pas, et personne ne valide sa propre note.
     */
    public function testUneNoteSansValideurEstChezLaTresorerieDesLEnvoi(): void
    {
        $this->em()->getConnection()->executeStatement('UPDATE caf_user SET email_user = NULL WHERE id_user = ?', [$this->responsableAutreCommission->getId()]);
        $responsableSansAdresse = $this->achatAuStatut(ExpenseReportStatusEnum::SUBMITTED, $this->autreCommission);
        $this->nommer($this->coResponsable, 'Sam', $this->nom);
        $this->em()->find(User::class, $this->responsable->getId())->setDoitRenouveler(true);
        $this->em()->flush();
        $seulResponsable = $this->achatAuStatut(ExpenseReportStatusEnum::SUBMITTED, $this->commissionDeTest, $this->coResponsable);

        $this->assertEqualsCanonicalizing([$responsableSansAdresse->getId(), $seulResponsable->getId()], $this->ids(['user.lastname' => $this->nom]));
    }

    public function testLeFiltreDeStatutPrendPlusieursValeurs(): void
    {
        $noteDeSortie = $this->noteDeSortie(ExpenseReportStatusEnum::SUBMITTED);
        $sansValideur = $this->achatAuStatut(ExpenseReportStatusEnum::SUBMITTED, null);
        $preValidee = $this->achatAuStatut(ExpenseReportStatusEnum::PREAPPROVED, $this->commissionDeTest);
        $this->achatAuStatut(ExpenseReportStatusEnum::SUBMITTED, $this->commissionDeTest);
        $this->achatAuStatut(ExpenseReportStatusEnum::APPROVED, $this->commissionDeTest);
        $this->noteDeSortie(ExpenseReportStatusEnum::APPROVED);

        // Ce qu'envoie le filtre « À valider » de compta-club.
        $notes = $this->notesPar('status[]=submitted&status[]=preapproved&user.lastname=' . rawurlencode($this->nom));

        $this->assertEqualsCanonicalizing([$noteDeSortie->getId(), $sansValideur->getId(), $preValidee->getId()], array_column($notes, 'id'));
    }

    /**
     * @dataProvider typesProvider
     */
    public function testLeFiltreDeType(string $type): void
    {
        $notes = [
            'sortie' => $this->noteDeSortie(ExpenseReportStatusEnum::SUBMITTED),
            'autre' => $this->achatAuStatut(ExpenseReportStatusEnum::APPROVED, null),
            'formation' => $this->formationAuStatut(ExpenseReportStatusEnum::PREAPPROVED),
        ];

        $this->assertSame([$notes[$type]->getId()], $this->ids(['type' => $type, 'user.lastname' => $this->nom]));
    }

    public static function typesProvider(): iterable
    {
        yield 'sorties' => ['sortie'];
        yield 'achats' => ['autre'];
        yield 'formations' => ['formation'];
    }

    public function testLeFiltreDuChoixEntreDonEtRemboursement(): void
    {
        $donDeSortie = $this->noteDeSortie(ExpenseReportStatusEnum::SUBMITTED, false);
        $remboursementDeSortie = $this->noteDeSortie(ExpenseReportStatusEnum::SUBMITTED);
        $don = $this->achatAuStatut(ExpenseReportStatusEnum::APPROVED, null, remboursement: false);
        $remboursement = $this->achatAuStatut(ExpenseReportStatusEnum::APPROVED, null);

        $this->assertEqualsCanonicalizing([$donDeSortie->getId(), $don->getId()], $this->ids(['refundRequired' => 'false', 'user.lastname' => $this->nom]));
        $this->assertEqualsCanonicalizing([$remboursementDeSortie->getId(), $remboursement->getId()], $this->ids(['refundRequired' => 'true', 'user.lastname' => $this->nom]));
    }

    public function testLaRechercheSurLeTitreDeLaSortieGardeLesNotesHorsSortie(): void
    {
        $trouvee = $this->noteDeSortie(ExpenseReportStatusEnum::SUBMITTED);
        $titre = 'Traversée ' . bin2hex(random_bytes(4));
        $this->changerLaSortie($trouvee, static fn (Evt $sortie) => $sortie->setTitre($titre));
        $autreSortie = $this->noteDeSortie(ExpenseReportStatusEnum::SUBMITTED);
        $horsSortie = $this->achatAuStatut(ExpenseReportStatusEnum::APPROVED, null);

        $ids = $this->ids(['event.titre' => substr($titre, -8), 'user.lastname' => $this->nom]);

        $this->assertEqualsCanonicalizing([$trouvee->getId(), $horsSortie->getId()], $ids);
        $this->assertNotContains($autreSortie->getId(), $ids);
        $this->assertSame([$trouvee->getId()], array_column(array_filter($this->notes(['event.titre' => substr($titre, -8)]), static fn (array $note) => 'sortie' === $note['type']), 'id'));
    }

    public function testLeFiltreDeDateDeLaSortieGardeLesNotesHorsSortie(): void
    {
        $trouvee = $this->noteDeSortie(ExpenseReportStatusEnum::SUBMITTED);
        $this->changerLaSortie($trouvee, static fn (Evt $sortie) => $sortie->setStartDate(new \DateTimeImmutable('2031-02-14 09:30:00')));
        $autreSortie = $this->noteDeSortie(ExpenseReportStatusEnum::SUBMITTED);
        $horsSortie = $this->achatAuStatut(ExpenseReportStatusEnum::APPROVED, null);

        // Ce qu'envoie le filtre de date de compta-club.
        $ids = array_column($this->notesPar('event.startDate[after]=2031-02-14T00:00:00&event.startDate[before]=2031-02-14T23:59:59&user.lastname=' . rawurlencode($this->nom)), 'id');

        $this->assertEqualsCanonicalizing([$trouvee->getId(), $horsSortie->getId()], $ids);
        $this->assertNotContains($autreSortie->getId(), $ids);
    }

    /**
     * @dataProvider filtresInconnusProvider
     */
    public function testUnFiltreInconnuNeProvoquePasDErreur(string $filtres, bool $listeVide): void
    {
        $this->achatAuStatut(ExpenseReportStatusEnum::APPROVED, null);

        $this->assertSame($listeVide, [] === $this->notesPar($filtres . '&user.lastname=' . rawurlencode($this->nom)));
    }

    public static function filtresInconnusProvider(): iterable
    {
        yield 'type inconnu' => ['type=inconnu', true];
        yield 'statut inconnu' => ['status=inconnu', true];
        yield 'statuts inconnus' => ['status[]=inconnu&status[]=1%27%20OR%201=1', true];
        yield 'type imbriqué' => ['type[a][b]=autre', false];
        yield 'statut imbriqué' => ['status[a]=approved', false];
        yield 'date illisible' => ['event.startDate[after]=pas-une-date', false];
        yield 'titre vide' => ['event.titre=', false];
        yield 'choix illisible' => ['refundRequired=peut-etre', false];
    }

    /**
     * @dataProvider demandeursProvider
     */
    public function testLaTresorerieLitLaValidationDuResponsableEtLeLibelleComptable(string $type): void
    {
        $note = 'formation' === $type ? $this->formationEnvoyee() : $this->noteEnvoyee($this->adherent, false, commission: $this->commissionDeTest);
        $this->assertResponseStatus(200, $this->patch($this->responsable, $note, ['status' => 'preapproved']));

        $dansLaListe = $this->notes(['user.lastname' => $this->nom]);
        $lue = $this->show($this->manager, $note)['body'];

        $this->assertSame([$note->getId()], array_column($dansLaListe, 'id'));
        $libelle = sprintf('LEFÈVRE%s Élise NDF %s %s', strtoupper(substr($this->nom, -8)), (new \DateTimeImmutable($lue['dateDepense']))->format('d/m/Y'), $lue['objet']);
        foreach ([$dansLaListe[0], $lue] as $vue) {
            $this->assertSame(['prenom' => 'Alex', 'nom' => 'DURAND'], $vue['validationResponsable']['auteur']);
            $this->assertSame($libelle, $vue['libelleComptable']);
            $this->assertSame('formation' === $type, $vue['refundRequired']);
        }
        $this->assertEquals($lue, $dansLaListe[0]);
    }

    public static function demandeursProvider(): iterable
    {
        yield 'achat' => ['autre'];
        yield 'formation' => ['formation'];
    }

    public function testLeLibelleComptableSuitLaNote(): void
    {
        $brouillon = $this->creerAchat($this->adherent);
        $envoyee = $this->noteEnvoyee($this->adherent);

        $this->assertNull($this->show($this->adherent, $brouillon)['body']['libelleComptable']);
        $this->assertStringStartsWith('LEFÈVRE', $this->show($this->adherent, $envoyee)['body']['libelleComptable']);
        $this->assertArrayNotHasKey('libelleComptable', $this->show($this->manager, $this->noteDeSortie(ExpenseReportStatusEnum::SUBMITTED))['body']);
    }

    public function testUneNoteDeSortieGardeSaFormeDansLaListe(): void
    {
        $noteDeSortie = $this->noteDeSortie(ExpenseReportStatusEnum::SUBMITTED);

        $lue = array_values(array_filter($this->notes(['user.lastname' => $this->nom]), static fn (array $note) => $note['id'] === $noteDeSortie->getId()))[0];

        $this->assertSame([
            'id', 'status', 'refundRequired', 'utilisateur', 'sortie', 'type', 'commission', 'objet', 'dateDepense', 'lienFormation',
            'motPourResponsable', 'dateCreation', 'commentaireStatut', 'details', 'piecesJointes', 'totalDeclare', 'totalRemboursable',
        ], array_keys($lue));
    }

    /**
     * @dataProvider sansDroitProvider
     */
    public function testSeulUnGestionnaireLitLaListe(string $acteur): void
    {
        $this->achatAuStatut(ExpenseReportStatusEnum::APPROVED, null);

        $this->assertResponseStatus(403, $this->list($this->{$acteur}, self::LISTE));
    }

    public static function sansDroitProvider(): iterable
    {
        yield 'adhérent' => ['stranger'];
        yield 'demandeur' => ['adherent'];
        yield 'responsable' => ['responsable'];
    }

    private function achatAuStatut(ExpenseReportStatusEnum $statut, ?Commission $commission, ?User $demandeur = null, bool $remboursement = true): ExpenseReport
    {
        // Sans commission, une commission sans responsable : la note n'a pas de valideur.
        $note = $this->creerAchat($demandeur ?? $this->adherent, ExpenseReportStatusEnum::DRAFT, self::LIGNES_ACHAT, $commission);

        return $this->auStatut($note, $statut, $remboursement);
    }

    private function formationAuStatut(ExpenseReportStatusEnum $statut): ExpenseReport
    {
        return $this->auStatut($this->creerFormation($this->adherent, self::LIGNES_FORMATION), $statut, true);
    }

    private function noteDeSortie(ExpenseReportStatusEnum $statut, bool $remboursement = true): ExpenseReport
    {
        // Les autres notes vident l'EntityManager : la sortie veut un demandeur qu'il connaît.
        return $this->auStatut($this->createReport($this->em()->find(User::class, $this->adherent->getId()), $statut), $statut, $remboursement);
    }

    private function auStatut(ExpenseReport $note, ExpenseReportStatusEnum $statut, bool $remboursement): ExpenseReport
    {
        $this->em()->getConnection()->executeStatement('UPDATE expense_report SET status = ?, refund_required = ? WHERE id = ?', [$statut->value, (int) $remboursement, $note->getId()]);

        return $note;
    }

    /**
     * Formation de l'adhérent sur la commission de test, envoyée : elle attend Alex Durand et Sam Petit.
     */
    private function formationEnvoyee(): ExpenseReport
    {
        $note = $this->creerFormation($this->adherent, self::LIGNES_FORMATION);
        $this->joindreLesJustificatifs($note, 'ligne-1', 'ligne-3', 'ligne-4');
        $this->assertResponseStatus(200, $this->envoyer($this->adherent, $note));

        return $note;
    }

    /**
     * @param callable(Evt): mixed $changement
     */
    private function changerLaSortie(ExpenseReport $note, callable $changement): void
    {
        $changement($this->reload($note)->getEvent());
        $this->em()->flush();
    }

    /**
     * @return list<int>
     */
    private function ids(array $filtres = []): array
    {
        return array_column($this->notes($filtres), 'id');
    }

    private function notes(array $filtres = []): array
    {
        $response = $this->list($this->manager, self::LISTE, $filtres);
        $this->assertResponseStatus(200, $response);

        return $response['body']['data'];
    }

    /**
     * Filtres écrits comme compta-club les envoie, `status[]=…` répété.
     */
    private function notesPar(string $filtres): array
    {
        $response = $this->list($this->manager, self::LISTE . '?' . $filtres);
        $this->assertResponseStatus(200, $response);

        return $response['body']['data'];
    }
}
