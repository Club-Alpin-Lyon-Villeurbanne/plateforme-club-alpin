<?php

namespace App\Tests\Messenger\MessageHandler;

use App\Entity\AlertType;
use App\Messenger\Message\SortiePubliee;
use App\Messenger\MessageHandler\SortiePublieeHandler;
use App\Repository\EvtRepository;
use App\Repository\UserRepository;
use App\Tests\VarDumperTestTrait;
use App\Tests\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class SortiePublieeHandlerTest extends WebTestCase
{
    use VarDumperTestTrait;

    public function testNotFound()
    {
        $handler = new SortiePublieeHandler(
            self::getContainer()->get(EvtRepository::class),
            self::getContainer()->get(UserRepository::class),
            self::getContainer()->get(MessageBusInterface::class),
        );

        // this id should not exist
        $handler(new SortiePubliee(2500000));

        $this->assertEmpty(self::getContainer()->get(MessageBusInterface::class)->getDispatchedMessages());
    }

    public function testItDispatchMessages()
    {
        $userOwner = $this->signup();
        $otherUserSubscribed = $this->signup();
        $otherUserNotSubscribed = $this->signup();

        $evt = $this->createEvent($userOwner);

        $userOwner->setAlertStatus(AlertType::Sortie, $evt->getCommission()->getCode(), true);
        $otherUserSubscribed->setAlertStatus(AlertType::Sortie, $evt->getCommission()->getCode(), true);
        $otherUserNotSubscribed->setAlertStatus(AlertType::Sortie, $evt->getCommission()->getCode(), false);

        self::getContainer()->get(EntityManagerInterface::class)->flush();

        $handler = new SortiePublieeHandler(
            self::getContainer()->get(EvtRepository::class),
            self::getContainer()->get(UserRepository::class),
            self::getContainer()->get(MessageBusInterface::class),
        );

        // this id should not exist
        $handler(new SortiePubliee($evt->getId()));

        $messages = self::getContainer()->get(MessageBusInterface::class)->getDispatchedMessages();
        $this->assertCount(2, $messages);

        // it sends a UserNotification to both two subscribed users
        $expected = <<<EOEXPECTED
[
  App\Messenger\Message\UserNotification {
    +alertType: App\Entity\AlertType {#1
      +name: "Sortie"
    }
    +id: "{$evt->getId()}"
    +userId: "{$userOwner->getId()}"
  }
  App\Messenger\Message\UserNotification {
    +alertType: App\Entity\AlertType {#1}
    +id: "{$evt->getId()}"
    +userId: "{$otherUserSubscribed->getId()}"
  }
]
EOEXPECTED;

        $this->assertDumpMatchesFormat($expected, array_map(static fn ($d) => $d['message'], $messages));
    }
}
