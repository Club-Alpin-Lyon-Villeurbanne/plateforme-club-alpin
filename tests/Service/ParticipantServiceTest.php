<?php

namespace App\Tests\Service;

use App\Entity\Commission;
use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Entity\User;
use App\Entity\UserAttr;
use App\Repository\UserAttrRepository;
use App\Service\ParticipantService;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class ParticipantServiceTest extends TestCase
{
    private UserAttrRepository $userAttrRepository;
    private ParticipantService $service;

    protected function setUp(): void
    {
        $this->userAttrRepository = $this->createMock(UserAttrRepository::class);
        $this->service = new ParticipantService(
            $this->userAttrRepository,
        );
    }

    public function testGetEncadrants(): void
    {
        $this->assertIsArray($this->service->getEncadrants());
        $this->assertEmpty($this->service->getEncadrants());
    }

    public function testGetInitiateurs(): void
    {
        $this->assertIsArray($this->service->getInitiateurs());
        $this->assertEmpty($this->service->getInitiateurs());
    }

    public function testGetCoencadrants(): void
    {
        $this->assertIsArray($this->service->getCoencadrants());
        $this->assertEmpty($this->service->getCoencadrants());
    }

    public function testGetBenevoles(): void
    {
        $this->assertIsArray($this->service->getBenevoles());
        $this->assertEmpty($this->service->getBenevoles());
    }

    public function testGetCurrentEncadrants(): void
    {
        $this->assertIsArray($this->service->getCurrentEncadrants());
        $this->assertEmpty($this->service->getCurrentEncadrants());
    }

    public function testGetCurrentInitiateurs(): void
    {
        $this->assertIsArray($this->service->getCurrentInitiateurs());
        $this->assertEmpty($this->service->getCurrentInitiateurs());
    }

    public function testGetCurrentCoencadrants(): void
    {
        $this->assertIsArray($this->service->getCurrentCoencadrants());
        $this->assertEmpty($this->service->getCurrentCoencadrants());
    }

    public function testGetCurrentBenevoles(): void
    {
        $this->assertIsArray($this->service->getCurrentBenevoles());
        $this->assertEmpty($this->service->getCurrentBenevoles());
    }

    public function testBuildManagersListsWithoutCommission(): void
    {
        // When commission is null, should not call repository
        $this->userAttrRepository->expects($this->never())->method('listAllEncadrants');

        $this->service->buildManagersLists(null, null);

        $this->assertEmpty($this->service->getEncadrants());
        $this->assertEmpty($this->service->getInitiateurs());
    }

    public function testBuildManagersListsWithCommissionAndNoParticipants(): void
    {
        $commission = new Commission('Alpinisme', 'ALPI', 1);

        // Mock that no encadrants are found - use callback to create fresh generators
        $this->userAttrRepository
            ->method('listAllEncadrants')
            ->willReturnCallback(function($commission, $roles) {
                return $this->createGenerator([]);
            })
        ;

        $this->service->buildManagersLists($commission, null);

        $this->assertEmpty($this->service->getEncadrants());
        $this->assertEmpty($this->service->getInitiateurs());
        $this->assertEmpty($this->service->getCoencadrants());
        $this->assertEmpty($this->service->getBenevoles());
    }

    public function testBuildManagersListsWithCommissionAndEncadrants(): void
    {
        $commission = new Commission('Alpinisme', 'ALPI', 1);

        // Create mock users
        $user1 = new User(1);
        $user1->setFirstname('John');
        $user1->setLastname('Doe');

        $user2 = new User(2);
        $user2->setFirstname('Jane');
        $user2->setLastname('Smith');

        // Create mock user attributes
        $attr1 = $this->createMock(UserAttr::class);
        $attr1->method('getUser')->willReturn($user1);

        $attr2 = $this->createMock(UserAttr::class);
        $attr2->method('getUser')->willReturn($user2);

        // Mock the repository to return these attributes for ENCADRANT role
        $this->userAttrRepository
            ->method('listAllEncadrants')
            ->willReturnCallback(function($commission, $roles) use ($attr1, $attr2) {
                return match([$roles]) {
                    [[EventParticipation::ROLE_ENCADRANT]] => $this->createGenerator([$attr1, $attr2]),
                    [[EventParticipation::ROLE_STAGIAIRE]] => $this->createGenerator([]),
                    [[EventParticipation::ROLE_COENCADRANT]] => $this->createGenerator([]),
                    [[EventParticipation::ROLE_BENEVOLE]] => $this->createGenerator([]),
                };
            });

        $this->service->buildManagersLists($commission, null);

        $encadrants = $this->service->getEncadrants();
        $this->assertCount(2, $encadrants);
        $this->assertArrayHasKey(1, $encadrants);
        $this->assertArrayHasKey(2, $encadrants);
        $this->assertEquals('John DOE', $encadrants[1]);
        $this->assertEquals('Jane SMITH', $encadrants[2]);
    }

    public function testBuildManagersListsWithCommissionAndEvent(): void
    {
        $commission = new Commission('Alpinisme', 'ALPI', 1);
        $event = $this->createMock(Evt::class);

        // Create mock users
        $user1 = new User(1);
        $user1->setFirstname('John');
        $user1->setLastname('Doe');

        // Create mock user attributes
        $attr1 = $this->createMock(UserAttr::class);
        $attr1->method('getUser')->willReturn($user1);

        // Mock repository responses
        $this->userAttrRepository
            ->method('listAllEncadrants')
            ->willReturnCallback(function($commission, $roles) use ($attr1) {
                return match([$roles]) {
                    [[EventParticipation::ROLE_ENCADRANT]] => $this->createGenerator([$attr1]),
                    [[EventParticipation::ROLE_STAGIAIRE]] => $this->createGenerator([]),
                    [[EventParticipation::ROLE_COENCADRANT]] => $this->createGenerator([]),
                    [[EventParticipation::ROLE_BENEVOLE]] => $this->createGenerator([]),
                };
            });

        // Mock event participants
        $participation = $this->createMock(EventParticipation::class);
        $participation->method('getUser')->willReturn($user1);

        $event->method('getParticipations')
            ->willReturnMap([
                [[EventParticipation::ROLE_ENCADRANT], null, new ArrayCollection([$participation])],
                [[EventParticipation::ROLE_STAGIAIRE], null, new ArrayCollection([])],
                [[EventParticipation::ROLE_COENCADRANT], null, new ArrayCollection([])],
                [[EventParticipation::ROLE_BENEVOLE], null, new ArrayCollection([])],
            ]);

        $this->service->buildManagersLists($commission, $event);

        // Check global list
        $encadrants = $this->service->getEncadrants();
        $this->assertCount(1, $encadrants);
        $this->assertArrayHasKey(1, $encadrants);

        // Check current event participants
        $currentEncadrants = $this->service->getCurrentEncadrants();
        $this->assertCount(1, $currentEncadrants);
        $this->assertContains(1, $currentEncadrants);
    }

    public function testBuildManagersListsMultipleRoles(): void
    {
        $commission = new Commission('Alpinisme', 'ALPI', 1);

        // Create mock users
        $encadrant = new User(1);
        $encadrant->setFirstname('John');
        $encadrant->setLastname('Encadrant');

        $initiateur = new User(2);
        $initiateur->setFirstname('Jane');
        $initiateur->setLastname('Initiateur');

        $coencadrant = new User(3);
        $coencadrant->setFirstname('Bob');
        $coencadrant->setLastname('Coencadrant');

        $benevole = new User(4);
        $benevole->setFirstname('Alice');
        $benevole->setLastname('Benevole');

        // Create mock user attributes
        $attr1 = $this->createMock(UserAttr::class);
        $attr1->method('getUser')->willReturn($encadrant);

        $attr2 = $this->createMock(UserAttr::class);
        $attr2->method('getUser')->willReturn($initiateur);

        $attr3 = $this->createMock(UserAttr::class);
        $attr3->method('getUser')->willReturn($coencadrant);

        $attr4 = $this->createMock(UserAttr::class);
        $attr4->method('getUser')->willReturn($benevole);

        // Mock repository responses for each role
        $this->userAttrRepository
            ->method('listAllEncadrants')
            ->willReturnCallback(function($commission, $roles) use ($attr1, $attr2, $attr3, $attr4) {
                return match([$roles]) {
                    [[EventParticipation::ROLE_ENCADRANT]] => $this->createGenerator([$attr1]),
                    [[EventParticipation::ROLE_STAGIAIRE]] => $this->createGenerator([$attr2]),
                    [[EventParticipation::ROLE_COENCADRANT]] => $this->createGenerator([$attr3]),
                    [[EventParticipation::ROLE_BENEVOLE]] => $this->createGenerator([$attr4]),
                };
            });

        $this->service->buildManagersLists($commission, null);

        // Check all lists are populated
        $this->assertCount(1, $this->service->getEncadrants());
        $this->assertCount(1, $this->service->getInitiateurs());
        $this->assertCount(1, $this->service->getCoencadrants());
        $this->assertCount(1, $this->service->getBenevoles());

        $this->assertArrayHasKey(1, $this->service->getEncadrants());
        $this->assertArrayHasKey(2, $this->service->getInitiateurs());
        $this->assertArrayHasKey(3, $this->service->getCoencadrants());
        $this->assertArrayHasKey(4, $this->service->getBenevoles());
    }

    private function createGenerator(array $items): \Generator
    {
        foreach ($items as $item) {
            yield $item;
        }
    }
}
