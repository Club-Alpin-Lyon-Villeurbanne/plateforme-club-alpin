<?php

namespace App\Tests\Controller;

use App\Service\MaterielApiService;
use App\Service\MaterielEmailService;
use App\Tests\WebTestCase;

class MaterielControllerTest extends WebTestCase
{
    public function testCreateAccountSendsCredentialsOnSuccess(): void
    {
        $user = $this->signup();
        $this->signin($user);

        $apiService = $this->createMock(MaterielApiService::class);
        $apiService->method('userExistsOnLoxya')->willReturn(false);
        $apiService->expects($this->once())
            ->method('createUser')
            ->willReturn([
                'email' => $user->getEmail(),
                'password' => 'motdepasse',
                'pseudo' => 'prenomnom',
            ]);

        $emailService = $this->createMock(MaterielEmailService::class);
        $emailService->expects($this->once())
            ->method('sendAccountCreationEmail')
            ->with($user->getEmail(), 'Prenom', 'NOM', $this->anything());

        $this->getContainer()->set(MaterielApiService::class, $apiService);
        $this->getContainer()->set(MaterielEmailService::class, $emailService);

        $this->client->request('POST', '/materiel/create-account');

        $this->assertResponseRedirects('/materiel');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.info-container', 'créé avec succès');
    }

    public function testCreateAccountRefusesWhenAlreadyOnLoxya(): void
    {
        $user = $this->signup();
        $this->signin($user);

        $apiService = $this->createMock(MaterielApiService::class);
        $apiService->method('userExistsOnLoxya')->willReturn(true);
        $apiService->expects($this->never())->method('createUser');

        $emailService = $this->createMock(MaterielEmailService::class);
        $emailService->expects($this->never())->method('sendAccountCreationEmail');

        $this->getContainer()->set(MaterielApiService::class, $apiService);
        $this->getContainer()->set(MaterielEmailService::class, $emailService);

        $this->client->request('POST', '/materiel/create-account');

        $this->assertResponseRedirects('/materiel');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.erreur', '409');
    }

    /**
     * Le cas de la panne de septembre 2026 : Loxya refuse la création, l'adhérent
     * doit voir un message d'erreur et non un compte annoncé comme créé.
     */
    public function testCreateAccountShowsErrorWhenLoxyaRejectsCreation(): void
    {
        $user = $this->signup();
        $this->signin($user);

        $apiService = $this->createMock(MaterielApiService::class);
        $apiService->method('userExistsOnLoxya')->willReturn(false);
        $apiService->method('createUser')
            ->willThrowException(new \RuntimeException('Failed to create beneficiary: Validation failed.'));

        $emailService = $this->createMock(MaterielEmailService::class);
        $emailService->expects($this->never())->method('sendAccountCreationEmail');

        $this->getContainer()->set(MaterielApiService::class, $apiService);
        $this->getContainer()->set(MaterielEmailService::class, $emailService);

        $this->client->request('POST', '/materiel/create-account');

        $this->assertResponseRedirects('/materiel');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.erreur', '400');
    }

    public function testIndexOffersAccountCreationToMemberInGoodStanding(): void
    {
        $user = $this->signup();
        $this->signin($user);

        $this->client->request('GET', '/materiel');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[action="/materiel/create-account"]');
    }

    /**
     * Le contrôle de licence conditionne l'accès au matériel : un adhérent à
     * renouveler ne doit pas se voir proposer la création de compte.
     */
    public function testIndexWarnsMemberWhoMustRenew(): void
    {
        $user = $this->signup();
        $user->setDoitRenouveler(true);
        $this->getContainer()->get('doctrine')->getManager()->flush();
        $this->signin($user);

        $this->client->request('GET', '/materiel');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('form[action="/materiel/create-account"]');
        $this->assertSelectorTextContains('body', "licence n'est pas valide");
    }

    public function testCreateAccountRequiresAuthentication(): void
    {
        $this->client->request('POST', '/materiel/create-account');

        $this->assertResponseRedirects('http://localhost/login');
    }
}
