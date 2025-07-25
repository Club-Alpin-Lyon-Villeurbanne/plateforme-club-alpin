<?php

namespace App\Tests\Messenger\MessageHandler;

use App\Entity\AlertType;
use App\Messenger\Message\ArticlePublie;
use App\Messenger\MessageHandler\ArticlePublieHandler;
use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
use App\Tests\VarDumperTestTrait;
use App\Tests\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class ArticlePublieHandlerTest extends WebTestCase
{
    use VarDumperTestTrait;

    public function testNotFound()
    {
        $handler = new ArticlePublieHandler(
            self::getContainer()->get(ArticleRepository::class),
            self::getContainer()->get(UserRepository::class),
            self::getContainer()->get(MessageBusInterface::class),
        );

        // this id should not exist
        $handler(new ArticlePublie(2500000));

        $this->assertEmpty(self::getContainer()->get(MessageBusInterface::class)->getDispatchedMessages());
    }

    public function testItDispatchMessages()
    {
        $userOwner = $this->signup();
        $otherUserSubscribed = $this->signup();
        $otherUserNotSubscribed = $this->signup();

        $article = $this->createArticle($userOwner);

        $userOwner->setAlertStatus(AlertType::Article, $article->getCommission()->getCode(), true);
        $otherUserSubscribed->setAlertStatus(AlertType::Article, $article->getCommission()->getCode(), true);
        $otherUserNotSubscribed->setAlertStatus(AlertType::Article, $article->getCommission()->getCode(), false);

        self::getContainer()->get(EntityManagerInterface::class)->flush();

        $handler = new ArticlePublieHandler(
            self::getContainer()->get(ArticleRepository::class),
            self::getContainer()->get(UserRepository::class),
            self::getContainer()->get(MessageBusInterface::class),
        );

        $handler(new ArticlePublie($article->getId()));

        $messages = self::getContainer()->get(MessageBusInterface::class)->getDispatchedMessages();
        $this->assertCount(2, $messages);

        // it sends a UserNotification to both two subscribed users
        $expected = <<<EOEXPECTED
[
  App\Messenger\Message\UserNotification {
    +alertType: App\Entity\AlertType {#1
      +name: "Article"
    }
    +id: "{$article->getId()}"
    +userId: "{$userOwner->getId()}"
  }
  App\Messenger\Message\UserNotification {
    +alertType: App\Entity\AlertType {#1}
    +id: "{$article->getId()}"
    +userId: "{$otherUserSubscribed->getId()}"
  }
]
EOEXPECTED;

        $this->assertDumpMatchesFormat($expected, array_map(static fn ($d) => $d['message'], $messages));
    }
}
