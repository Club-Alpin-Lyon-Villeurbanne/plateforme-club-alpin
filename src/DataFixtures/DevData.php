<?php

namespace App\DataFixtures;

use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Entity\User;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Nelmio\Alice\Loader\NativeLoader;
use Nelmio\Alice\Throwable\LoadingThrowable;

class DevData implements FixtureInterface
{
    /**
     * @throws LoadingThrowable
     */
    public function load(ObjectManager $manager): void
    {
        $filesLoader = new NativeLoader();

        $set = $filesLoader->loadFiles(glob(__DIR__ . '/alice/dev/*.yaml'));

        $users = [];

        foreach ($set->getObjects() as $object) {
            if ($object instanceof User) {
                $users[] = $object;
            }

            $manager->persist($object);
        }

        $roles = [
            EventParticipation::ROLE_ENCADRANT,
            EventParticipation::ROLE_BENEVOLE,
            EventParticipation::ROLE_COENCADRANT,
            EventParticipation::ROLE_STAGIAIRE,
            EventParticipation::ROLE_MANUEL,
            EventParticipation::ROLE_INSCRIT,
            EventParticipation::BENEVOLE,
        ];
        $status = [
            EventParticipation::STATUS_VALIDE,
            EventParticipation::STATUS_NON_CONFIRME,
            EventParticipation::STATUS_REFUSE,
        ];

        foreach ($set->getObjects() as $object) {
            if ($object instanceof Evt) {
                shuffle($users);
                $i = 0;
                $limit = mt_rand(4, 8);

                foreach ($users as $user) {
                    // Owner of an event should not be added as participant
                    if ($user === $object->getUser()) {
                        continue;
                    }

                    $participation = new EventParticipation($object, $user, $roles[array_rand($roles)], $status[array_rand($status)]);
                    if (\in_array($participation->getRole(), EventParticipation::ROLES_ENCADREMENT_ETENDU, true)) {
                        $participation->setStatus(EventParticipation::STATUS_VALIDE);
                    }
                    $manager->persist($participation);
                    if (++$i > $limit) {
                        break;
                    }
                }

                if ($owner = $object->getParticipation($object->getUser())) {
                    $owner->setRole(EventParticipation::ROLE_ENCADRANT);
                } else {
                    $participation = new EventParticipation($object, $object->getUser(), EventParticipation::ROLE_ENCADRANT, EventParticipation::STATUS_VALIDE);
                    $manager->persist($participation);
                }
            }
        }

        $manager->flush();
    }
}
