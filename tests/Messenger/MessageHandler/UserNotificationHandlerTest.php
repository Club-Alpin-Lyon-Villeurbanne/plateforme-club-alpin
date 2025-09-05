<?php

namespace App\Tests\Messenger\MessageHandler;

use App\Entity\AlertType;
use App\Mailer\Mailer;
use App\Messenger\Message\UserNotification;
use App\Messenger\MessageHandler\UserNotificationHandler;
use App\Repository\ArticleRepository;
use App\Repository\EvtRepository;
use App\Repository\UserNotificationRepository;
use App\Repository\UserRepository;
use App\Tests\VarDumperTestTrait;
use App\Tests\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class UserNotificationHandlerTest extends WebTestCase
{
    use VarDumperTestTrait;

    public function testNotFound()
    {
        $user = $this->signup();

        $handler = new UserNotificationHandler(
            self::getContainer()->get(ArticleRepository::class),
            self::getContainer()->get(EvtRepository::class),
            self::getContainer()->get(UserRepository::class),
            self::getContainer()->get(UserNotificationRepository::class),
            self::getContainer()->get(EntityManagerInterface::class),
            self::getContainer()->get(Mailer::class),
            '[CAF-Sortie]',
            '[CAF-Article]',
            'CAF de test',
        );

        $handler(new UserNotification(AlertType::Article, 25000000, $user->getId()));
        $handler(new UserNotification(AlertType::Sortie, 25000000, $user->getId()));

        $emails = $this->getMailerMessages();
        $this->assertEmpty($emails);

        $article = $this->createArticle($user);
        $evt = $this->createEvent($user);

        $handler(new UserNotification(AlertType::Article, $article->getId(), 250100000));
        $handler(new UserNotification(AlertType::Sortie, $evt->getId(), 250100000));

        $emails = $this->getMailerMessages();
        $this->assertEmpty($emails);
    }

    public function testItDispatchMessagesSortie()
    {
        $defaultAlertSortiePrefix = '[CAF-Sortie]';
        $defaultAlertArticlePrefix = '[CAF-Article]';
        $siteName = 'CAF de test';

        $userOwner = $this->signup();
        $otherUserSubscribed = $this->signup();
        $otherUserSubscribed->setAlertSortiePrefix('Test');

        $evt = $this->createEvent($userOwner);

        self::getContainer()->get(EntityManagerInterface::class)->flush();

        $handler = new UserNotificationHandler(
            self::getContainer()->get(ArticleRepository::class),
            self::getContainer()->get(EvtRepository::class),
            self::getContainer()->get(UserRepository::class),
            self::getContainer()->get(UserNotificationRepository::class),
            self::getContainer()->get(EntityManagerInterface::class),
            self::getContainer()->get(Mailer::class),
            $defaultAlertArticlePrefix,
            $defaultAlertSortiePrefix,
            $siteName,
        );

        $handler(new UserNotification(AlertType::Sortie, $evt->getId(), $userOwner->getId()));

        // message is sent using queue, for owner
        $messages = self::getContainer()->get(MessageBusInterface::class)->getDispatchedMessages();
        $this->assertCount(1, $messages);
        $emails = $this->getMailerMessages();
        $this->assertCount(1, $emails);

        $this->assertEmailHeaderSame($emails[0], 'To', sprintf('%s <%s>', $userOwner->getNickname(), $userOwner->getEmail()));
        $this->assertEmailSubjectContains($emails[0], $defaultAlertSortiePrefix);
        $this->assertEmailHtmlBodyContains($emails[0], 'Une nouvelle sortie');
        $this->assertEmailHtmlBodyContains($emails[0], 'vient d\'être publiée sur le site');

        // triggering the notification a second time for owner does not send a new mail
        $handler(new UserNotification(AlertType::Sortie, $evt->getId(), $userOwner->getId()));
        $messages = self::getContainer()->get(MessageBusInterface::class)->getDispatchedMessages();
        $this->assertCount(1, $messages);
        $emails = $this->getMailerMessages();
        $this->assertCount(1, $emails);

        // message is sent using queue, for other user
        $handler(new UserNotification(AlertType::Sortie, $evt->getId(), $otherUserSubscribed->getId()));
        $messages = self::getContainer()->get(MessageBusInterface::class)->getDispatchedMessages();
        $this->assertCount(2, $messages);
        $emails = $this->getMailerMessages();
        $this->assertCount(2, $emails);

        $this->assertEmailHeaderSame($emails[1], 'To', sprintf('%s <%s>', $otherUserSubscribed->getNickname(), $otherUserSubscribed->getEmail()));
        $this->assertEmailSubjectContains($emails[1], $otherUserSubscribed->getAlertSortiePrefix());
        $this->assertEmailHtmlBodyContains($emails[1], 'Une nouvelle sortie');
        $this->assertEmailHtmlBodyContains($emails[1], 'vient d\'être publiée sur le site');

        // triggering the notification a second time for owner does not send a new mail
        $handler(new UserNotification(AlertType::Sortie, $evt->getId(), $otherUserSubscribed->getId()));
        $messages = self::getContainer()->get(MessageBusInterface::class)->getDispatchedMessages();
        $this->assertCount(2, $messages);
        $emails = $this->getMailerMessages();
        $this->assertCount(2, $emails);
    }

    public function testItDispatchMessagesArticle()
    {
        $defaultAlertSortiePrefix = '[CAF-Sortie]';
        $defaultAlertArticlePrefix = '[CAF-Article]';
        $siteName = 'CAF de test';

        $userOwner = $this->signup();
        $otherUserSubscribed = $this->signup();

        $article = $this->createArticle($userOwner);

        self::getContainer()->get(EntityManagerInterface::class)->flush();

        $handler = new UserNotificationHandler(
            self::getContainer()->get(ArticleRepository::class),
            self::getContainer()->get(EvtRepository::class),
            self::getContainer()->get(UserRepository::class),
            self::getContainer()->get(UserNotificationRepository::class),
            self::getContainer()->get(EntityManagerInterface::class),
            self::getContainer()->get(Mailer::class),
            $defaultAlertArticlePrefix,
            $defaultAlertSortiePrefix,
            $siteName,
        );

        $handler(new UserNotification(AlertType::Article, $article->getId(), $userOwner->getId()));

        // message is sent using queue, for owner
        $messages = self::getContainer()->get(MessageBusInterface::class)->getDispatchedMessages();
        $this->assertCount(1, $messages);
        $emails = $this->getMailerMessages();
        $this->assertCount(1, $emails);

        $this->assertEmailHeaderSame($emails[0], 'To', sprintf('%s <%s>', $userOwner->getNickname(), $userOwner->getEmail()));
        $this->assertEmailSubjectContains($emails[0], $defaultAlertArticlePrefix);
        $this->assertEmailHtmlBodyContains($emails[0], 'Un nouvel article');
        $this->assertEmailHtmlBodyContains($emails[0], 'vient d\'être publié sur le site');

        // triggering the notification a second time for owner does not send a new mail
        $handler(new UserNotification(AlertType::Article, $article->getId(), $userOwner->getId()));
        $messages = self::getContainer()->get(MessageBusInterface::class)->getDispatchedMessages();
        $this->assertCount(1, $messages);
        $emails = $this->getMailerMessages();
        $this->assertCount(1, $emails);

        // message is sent using queue, for other user
        $handler(new UserNotification(AlertType::Article, $article->getId(), $otherUserSubscribed->getId()));
        $messages = self::getContainer()->get(MessageBusInterface::class)->getDispatchedMessages();
        $this->assertCount(2, $messages);
        $emails = $this->getMailerMessages();
        $this->assertCount(2, $emails);

        $this->assertEmailHeaderSame($emails[1], 'To', sprintf('%s <%s>', $otherUserSubscribed->getNickname(), $otherUserSubscribed->getEmail()));
        $this->assertEmailSubjectContains($emails[1], $defaultAlertArticlePrefix);
        $this->assertEmailHtmlBodyContains($emails[1], 'Un nouvel article');
        $this->assertEmailHtmlBodyContains($emails[1], 'vient d\'être publié sur le site');

        // triggering the notification a second time for owner does not send a new mail
        $handler(new UserNotification(AlertType::Article, $article->getId(), $otherUserSubscribed->getId()));
        $messages = self::getContainer()->get(MessageBusInterface::class)->getDispatchedMessages();
        $this->assertCount(2, $messages);
        $emails = $this->getMailerMessages();
        $this->assertCount(2, $emails);
    }
}
