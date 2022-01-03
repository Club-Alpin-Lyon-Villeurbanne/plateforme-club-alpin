<?php

use App\Entity\User;
use App\Utils\NicknameGenerator;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Profiler\Tests\ReflectionHelper;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Bundle\BlackfireBundle\Plan\PlanManipulator;
use Bundle\BlackfireBundle\Entity\BuildNotificationConfiguration;
use Bundle\BlackfireBundle\Entity\NotificationChannel;
use Bundle\BlackfireBundle\Notification\ChannelConfiguration\EmailChannelConfiguration;
use Bundle\BlackfireBundle\Notification\ChannelConfiguration\SlackChannelConfiguration;

class DevData implements FixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    private string $userEmail;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $loader = $this->container->get('nelmio_alice.files_loader');
        $set = $loader->loadFiles(glob(__DIR__.'/alice/dev/*.yaml'), ['userEmail' => $this->userEmail]);

        $licenceNum =749999999990;

        foreach ($set->getObjects() as $object) {
            if ($object instanceof User) {
                $object->setCafnum($licenceNum--);
                $object->setNickname(NicknameGenerator::generateNickname($object->getFirstname(), $object->getLastname()));
            }
            $manager->persist($object);
        }

        $manager->flush();
    }

    public function setUserEmail(string $email): void
    {
        $this->userEmail = $email;
    }
}
