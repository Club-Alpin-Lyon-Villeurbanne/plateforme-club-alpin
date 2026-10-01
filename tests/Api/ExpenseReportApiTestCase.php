<?php

namespace App\Tests\Api;

use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Tests\WebTestCase;
use App\Utils\Enums\ExpenseReportStatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Socle des tests API des notes de frais : acteurs, appels authentifiés par JWT, assertions.
 *
 * Acteurs : owner = adhérent propriétaire, manager / otherManager = gestionnaires, stranger = tiers.
 */
abstract class ExpenseReportApiTestCase extends WebTestCase
{
    protected const DETAILS = '{"transport":{"type":"PUBLIC_TRANSPORT","ticketPrice":0},"accommodations":[],"others":[]}';
    // Montant gonflé (5 000 km) sans justificatif requis : seul le contrôle de droits peut refuser.
    protected const INFLATED_DETAILS = '{"transport":{"type":"PERSONAL_VEHICLE","distance":5000,"tollFee":0},"accommodations":[],"others":[]}';

    private const MANAGERS_ENV = 'AUTHORIZED_IDS_FOR_EXPENSE_MANAGEMENT';

    protected User $owner;
    protected User $manager;
    protected User $otherManager;
    protected User $stranger;

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
    }

    protected function tearDown(): void
    {
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

    private function jwt(User $user): string
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
