<?php

namespace App\Tests\Api;

use App\Entity\Commission;
use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Entity\UserAttr;
use App\Tests\WebTestCase;
use App\Utils\Enums\ExpenseReportStatusEnum;
use App\Utils\Enums\ExpenseReportTypeEnum;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Socle des tests API des notes de frais : acteurs, appels authentifiés par JWT, assertions.
 *
 * Acteurs : owner = adhérent propriétaire, manager / otherManager = gestionnaires, stranger = tiers.
 * Sur la commission de test : responsable (Alex Durand), coResponsable (Sam Petit), encadrant, et
 * adherent, un adhérent sans rôle qui y dépose ses notes ; responsableAutreCommission (Camille
 * Roux) sur l'autre commission.
 */
abstract class ExpenseReportApiTestCase extends WebTestCase
{
    protected const DETAILS = '{"transport":{"type":"PUBLIC_TRANSPORT","ticketPrice":0},"accommodations":[],"others":[]}';
    // Montant gonflé (5 000 km) sans justificatif requis : seul le contrôle de droits peut refuser.
    protected const INFLATED_DETAILS = '{"transport":{"type":"PERSONAL_VEHICLE","distance":5000,"tollFee":0},"accommodations":[],"others":[]}';

    protected const LIGNES_ACHAT = '{"lignes":[{"id":"ligne-1","montant":64.9,"description":"Dégaines (x6)"},{"id":"ligne-2","montant":21.5,"description":"Cordelette 6 mm"}]}';
    // Aucun plafond n'est atteint ; seule la voiture (ligne 2) se passe de justificatif.
    protected const LIGNES_FORMATION = '{"lignes":[{"id":"ligne-1","nature":"stage","montant":280},{"id":"ligne-2","nature":"voiture","distance":220},{"id":"ligne-3","nature":"peage","montant":32},{"id":"ligne-4","nature":"hebergement","montant":54}]}';

    private const MANAGERS_ENV = 'AUTHORIZED_IDS_FOR_EXPENSE_MANAGEMENT';

    protected User $owner;
    protected User $manager;
    protected User $otherManager;
    protected User $stranger;

    protected Commission $commissionDeTest;
    protected Commission $autreCommission;
    protected User $responsable;
    protected User $coResponsable;
    protected User $responsableAutreCommission;
    protected User $encadrant;
    protected User $adherent;

    /** @var list<string> codes des commissions où le test a donné des rôles, retirés à la fin */
    private array $commissionsAvecRoles = [];

    /** @var array{env: ?string, server: ?string} */
    private array $originalManagersEnv;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalManagersEnv = [
            'env' => $_ENV[self::MANAGERS_ENV] ?? null,
            'server' => $_SERVER[self::MANAGERS_ENV] ?? null,
        ];

        $this->owner = $this->signup();
        $this->manager = $this->signup();
        $this->otherManager = $this->signup();
        $this->stranger = $this->signup();
        $this->designateManagers($this->manager, $this->otherManager);

        $this->commissionDeTest = $this->createCommission('Commission de test');
        $this->autreCommission = $this->createCommission('Autre commission');
        $this->responsable = $this->signup();
        $this->nommer($this->responsable, 'Alex', 'Durand');
        $this->coResponsable = $this->signup();
        $this->nommer($this->coResponsable, 'Sam', 'Petit');
        $this->responsableAutreCommission = $this->signup();
        $this->nommer($this->responsableAutreCommission, 'Camille', 'Roux');
        $this->encadrant = $this->signup();
        $this->adherent = $this->signup();
        $this->rendreResponsable($this->responsable, $this->commissionDeTest);
        $this->rendreResponsable($this->coResponsable, $this->commissionDeTest);
        $this->rendreResponsable($this->responsableAutreCommission, $this->autreCommission);
        $this->donnerUnRole($this->encadrant, UserAttr::ENCADRANT, $this->commissionDeTest);
    }

    protected function tearDown(): void
    {
        $base = $this->em()->getConnection();
        foreach (array_unique($this->commissionsAvecRoles) as $code) {
            $base->executeStatement('DELETE FROM caf_user_attr WHERE params_user_attr = ?', ['commission:' . $code]);
        }
        // Les deux commissions de chaque test, avec leurs notes : la base de test ne grossit pas.
        foreach (array_filter([$this->commissionDeTest ?? null, $this->autreCommission ?? null]) as $commission) {
            $notes = 'SELECT id FROM expense_report WHERE commission_id = ?';
            $base->executeStatement(sprintf('DELETE FROM expense_report_status_history WHERE expense_report_id IN (SELECT id FROM (%s) AS notes)', $notes), [$commission->getId()]);
            $base->executeStatement(sprintf('DELETE FROM expense_attachment WHERE expense_report_id IN (SELECT id FROM (%s) AS notes)', $notes), [$commission->getId()]);
            $base->executeStatement('DELETE FROM expense_report WHERE commission_id = ?', [$commission->getId()]);
            $base->executeStatement('DELETE FROM caf_user_attr WHERE params_user_attr = ?', ['commission:' . $commission->getCode()]);
            $base->executeStatement('DELETE FROM caf_commission WHERE id_commission = ?', [$commission->getId()]);
        }

        // Restaurer la valeur d'origine (définie, même vide, par .env) : la supprimer
        // ferait échouer tous les tests suivants qui instancient le voter.
        foreach (['env' => '_ENV', 'server' => '_SERVER'] as $key => $superGlobal) {
            if (null === $this->originalManagersEnv[$key]) {
                unset($GLOBALS[$superGlobal][self::MANAGERS_ENV]);
            } else {
                $GLOBALS[$superGlobal][self::MANAGERS_ENV] = $this->originalManagersEnv[$key];
            }
        }

        parent::tearDown();
    }

    protected function rendreResponsable(User $utilisateur, Commission $commission): void
    {
        $this->donnerUnRole($utilisateur, UserAttr::RESPONSABLE_COMMISSION, $commission);
    }

    protected function donnerUnRole(User $utilisateur, string $role, Commission $commission): void
    {
        $this->commissionsAvecRoles[] = $commission->getCode();
        $this->addAttribute($this->em()->find(User::class, $utilisateur->getId()), $role, 'commission:' . $commission->getCode());
    }

    /**
     * Le voter lit la liste à son instanciation. Le KernelBrowser redémarre le kernel entre deux
     * requêtes : la valeur est donc relue à chaque requête ($_ENV est prioritaire sur getenv()).
     */
    protected function designateManagers(User ...$managers): void
    {
        $ids = implode(',', array_map(static fn (User $user) => (string) $user->getId(), $managers));
        $_ENV[self::MANAGERS_ENV] = $_SERVER[self::MANAGERS_ENV] = $ids;
    }

    protected function createReport(User $owner, ExpenseReportStatusEnum $status, string $details = self::DETAILS): ExpenseReport
    {
        $em = $this->em();
        $report = new ExpenseReport();
        $report->setUser($owner);
        $report->setEvent($this->createEvent($owner));
        $report->setStatus($status);
        $report->setRefundRequired(true);
        $report->setDetails($details);
        $em->persist($report);
        $em->flush();
        $em->clear();

        return $report;
    }

    protected function creerAchat(User $demandeur, ExpenseReportStatusEnum $status = ExpenseReportStatusEnum::DRAFT, ?string $details = null, ?Commission $commission = null, ?\DateTimeImmutable $dateDepense = null): ExpenseReport
    {
        $em = $this->em();
        $note = ExpenseReport::nouvelleNoteHorsSortie($em->find(User::class, $demandeur->getId()), ExpenseReportTypeEnum::AUTRE);
        $note->setCommission($em->find(Commission::class, ($commission ?? $this->commissionVisible())->getId()));
        $note->setObjet('Dégaines pour la SAE');
        $note->setDateDepense($dateDepense ?? new \DateTimeImmutable('-3 days'));
        $note->setStatus($status);
        $note->setDetails($details);
        $em->persist($note);
        $em->flush();
        $em->clear();

        return $note;
    }

    /**
     * Formation déposée sur la commission de test, où l'encadrant a son rôle, sans passer par le
     * contrôle de création.
     */
    protected function creerFormation(User $demandeur, ?string $details = null, ?\DateTimeImmutable $dateDepense = null, ?string $lienFormation = null): ExpenseReport
    {
        $em = $this->em();
        $note = ExpenseReport::nouvelleNoteHorsSortie($em->find(User::class, $demandeur->getId()), ExpenseReportTypeEnum::FORMATION);
        $note->setCommission($em->find(Commission::class, $this->commissionDeTest->getId()));
        $note->setObjet('Formation initiateur SAE');
        $note->setDateDepense($dateDepense ?? new \DateTimeImmutable('-3 days'));
        $note->setLienFormation($lienFormation);
        $note->setDetails($details);
        $em->persist($note);
        $em->flush();
        $em->clear();

        return $note;
    }

    /**
     * Corps de création d'une formation sur la commission de test ; null pour laisser un champ vide.
     */
    protected function formation(array $champs = []): array
    {
        return array_merge([
            'type' => 'formation',
            'commissionId' => $this->commissionDeTest->getId(),
            'objet' => 'Formation initiateur SAE',
            'dateDepense' => (new \DateTimeImmutable('-3 days'))->format('Y-m-d'),
        ], $champs);
    }

    /**
     * Barème formation de la configuration : taux kilométrique, plafonds par nature et du total.
     *
     * @return array{tauxKilometrique: float, plafonds: array{parNature: array<string, array{montant: float, portee: string}>, total: ?float}}
     */
    protected function baremeFormation(): array
    {
        return $this->configHorsSortie()['formation'];
    }

    protected function euros(float $montant): string
    {
        return (new \NumberFormatter('fr_FR', \NumberFormatter::CURRENCY))->formatCurrency($montant, 'EUR');
    }

    /**
     * Une commission visible sans responsable : ses notes vont directement à la trésorerie.
     */
    protected function commissionVisible(int $rang = 0): Commission
    {
        $commissions = $this->em()->createQuery(sprintf(
            'SELECT c FROM %s c WHERE c.vis = true AND NOT EXISTS (
                SELECT a.id FROM %s a JOIN a.userType t WHERE t.code = :responsable AND a.params = CONCAT(\'commission:\', c.code)
            ) ORDER BY c.id ASC',
            Commission::class,
            UserAttr::class,
        ))
            ->setParameter('responsable', UserAttr::RESPONSABLE_COMMISSION)
            ->setMaxResults($rang + 1)
            ->getResult();
        $this->assertArrayHasKey($rang, $commissions, 'La base de test doit contenir des commissions visibles sans responsable.');

        return $commissions[$rang];
    }

    protected function commissionMasquee(): Commission
    {
        $commission = $this->createCommission('Commission masquée');
        $commission->setVis(false);
        $this->em()->flush();

        return $commission;
    }

    /**
     * Corps de création d'un achat valide ; null pour laisser un champ vide.
     */
    protected function achat(array $champs = []): array
    {
        return array_merge([
            'type' => 'autre',
            'commissionId' => $this->commissionVisible()->getId(),
            'objet' => 'Dégaines pour la SAE',
            'dateDepense' => (new \DateTimeImmutable('-3 days'))->format('Y-m-d'),
        ], $champs);
    }

    /**
     * @return array{status: int, body: array}
     */
    protected function create(User $as, array $body): array
    {
        $this->client->request('POST', '/api/notes-de-frais', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwt($as),
        ], json_encode($body));

        return $this->lastResponse();
    }

    /**
     * Un justificatif pour chacune de ces lignes, envoyé par le propriétaire de la note.
     */
    protected function joindreLesJustificatifs(ExpenseReport $note, string ...$lignes): void
    {
        foreach ($lignes as $ligne) {
            $this->assertResponseStatus(201, $this->uploadAttachment($note->getUser(), $note, $ligne));
        }
    }

    /**
     * Envoi de la note par PATCH, choix entre don et remboursement fait sauf mention contraire.
     *
     * @return array{status: int, body: array}
     */
    protected function envoyer(User $as, ExpenseReport $note, array $champs = []): array
    {
        return $this->patch($as, $note, array_merge(['refundRequired' => true, 'status' => 'submitted'], $champs));
    }

    /**
     * Achat de deux lignes, justificatifs joints, envoyé par son demandeur : la note a son
     * historique d'envoi et ses montants figés.
     */
    protected function noteEnvoyee(User $demandeur, bool $remboursement = true, string $details = self::LIGNES_ACHAT, ?Commission $commission = null): ExpenseReport
    {
        $note = $this->creerAchat($demandeur, ExpenseReportStatusEnum::DRAFT, $details, $commission);
        $this->joindreLesJustificatifs($note, ...array_column(json_decode($details, true)['lignes'], 'id'));
        $this->assertResponseStatus(200, $this->envoyer($demandeur, $note, ['refundRequired' => $remboursement]));

        return $note;
    }

    protected function renvoyer(User $as, ExpenseReport $note, string $message = 'Merci de joindre la facture du magasin.'): array
    {
        return $this->patch($as, $note, ['status' => 'rejected', 'commentaireStatut' => $message]);
    }

    protected function nommer(User $utilisateur, string $prenom, string $nom): void
    {
        $this->em()->find(User::class, $utilisateur->getId())->setFirstname($prenom)->setLastname($nom);
        $this->em()->flush();
        $utilisateur->setFirstname($prenom)->setLastname($nom);
    }

    protected function configHorsSortie(): array
    {
        return $this->getContainer()->getParameter('expense_report')['horsSortie'];
    }

    /**
     * Mails de la dernière requête envoyés à cette adresse.
     *
     * @return list<Email>
     */
    protected function mailsA(string $adresse): array
    {
        return array_values(array_filter(
            $this->getMailerMessages(),
            static fn (Email $mail) => \in_array($adresse, array_map(static fn (Address $destinataire) => $destinataire->getAddress(), $mail->getTo()), true),
        ));
    }

    /**
     * Les mails de la dernière requête, copies comprises, ne vont qu'à ces personnes, un chacune :
     * aucun ne part à une autre adresse, celle d'une trésorerie par exemple.
     *
     * @param list<User> $personnes
     */
    protected function assertSeulsDestinataires(array $personnes): void
    {
        $adresses = [];
        foreach ($this->getMailerMessages() as $mail) {
            foreach ([...$mail->getTo(), ...$mail->getCc(), ...$mail->getBcc()] as $destinataire) {
                $adresses[] = $destinataire->getAddress();
            }
        }

        $this->assertEqualsCanonicalizing(array_map(static fn (User $personne) => $personne->getEmail(), $personnes), $adresses);
    }

    /**
     * Le destinataire répond directement au demandeur de la note.
     */
    protected function assertReponseAuDemandeur(Email $mail, User $demandeur): void
    {
        $this->assertSame([$demandeur->getEmail()], array_map(static fn (Address $adresse) => $adresse->getAddress(), $mail->getReplyTo()));
    }

    /**
     * @return array{status: int, body: array}
     */
    protected function list(User $as, string $uri, array $query = []): array
    {
        $this->client->request('GET', $uri . ($query ? '?' . http_build_query($query) : ''), [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwt($as),
        ]);

        return $this->lastResponse();
    }

    /**
     * @return array{status: int, body: array}
     */
    protected function patch(User $as, ExpenseReport $report, array $body): array
    {
        $this->client->request('PATCH', '/api/notes-de-frais/' . $report->getId(), [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwt($as),
        ], json_encode($body));

        return $this->lastResponse();
    }

    /**
     * @return array{status: int, body: array}
     */
    protected function show(User $as, ExpenseReport $report): array
    {
        $this->client->request('GET', '/api/notes-de-frais/' . $report->getId(), [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwt($as),
        ]);

        return $this->lastResponse();
    }

    /**
     * @return array{status: int, body: array}
     */
    protected function uploadAttachment(User $as, ExpenseReport $report, string $expenseId): array
    {
        // PNG 1x1 valide : la contrainte File vérifie le type MIME réel.
        $path = tempnam(sys_get_temp_dir(), 'justificatif');
        file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==', true));

        $this->client->request('POST', '/api/notes-de-frais/' . $report->getId() . '/pieces-jointes', ['expenseId' => $expenseId], [
            'file' => new UploadedFile($path, 'ticket.png', 'image/png', null, true),
        ], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwt($as),
        ]);

        return $this->lastResponse();
    }

    /**
     * @return array{status: int, body: array}
     */
    protected function cloneReport(User $as, ExpenseReport $report, array $body = []): array
    {
        $this->client->request('POST', '/api/notes-de-frais/' . $report->getId() . '/clone', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwt($as),
        ], json_encode($body));

        return $this->lastResponse();
    }

    /**
     * @return array{status: int, body: array}
     */
    protected function listAttachments(User $as, ExpenseReport $report): array
    {
        $this->client->request('GET', '/api/notes-de-frais/' . $report->getId() . '/pieces-jointes', [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwt($as),
        ]);

        return $this->lastResponse();
    }

    protected function assertResponseStatus(int $expected, array $response): void
    {
        $this->assertSame($expected, $response['status'], json_encode($response['body'], \JSON_UNESCAPED_UNICODE));
    }

    /**
     * Refus de validation (422) portant exactement sur ce champ, et sur aucun autre.
     */
    protected function assertRefused(array $response, string $propertyPath): void
    {
        $this->assertResponseStatus(422, $response);
        $paths = array_column($response['body']['violations'] ?? [], 'propertyPath');
        $this->assertSame([$propertyPath], $paths, json_encode($response['body'], \JSON_UNESCAPED_UNICODE));
    }

    /**
     * Refus de validation (422) portant sur ce seul champ, avec ce message parmi ceux du champ.
     */
    protected function assertRefusAvec(array $response, string $propertyPath, string $message): void
    {
        $this->assertResponseStatus(422, $response);
        $violations = $response['body']['violations'] ?? [];
        $contexte = json_encode($response['body'], \JSON_UNESCAPED_UNICODE);
        $this->assertSame([$propertyPath], array_values(array_unique(array_column($violations, 'propertyPath'))), $contexte);
        $this->assertContains($message, array_column($violations, 'message'), $contexte);
    }

    protected function reload(ExpenseReport $report): ExpenseReport
    {
        $em = $this->em();
        $em->clear();
        $reloaded = $em->getRepository(ExpenseReport::class)->find($report->getId());
        $this->assertNotNull($reloaded);

        return $reloaded;
    }

    protected function em(): EntityManagerInterface
    {
        return $this->getContainer()->get(EntityManagerInterface::class);
    }

    protected function jwt(User $user): string
    {
        return $this->getContainer()->get(JWTTokenManagerInterface::class)->create($user);
    }

    /**
     * @return array{status: int, body: array}
     */
    private function lastResponse(): array
    {
        $response = $this->client->getResponse();

        return [
            'status' => $response->getStatusCode(),
            'body' => json_decode((string) $response->getContent(), true) ?? [],
        ];
    }
}
