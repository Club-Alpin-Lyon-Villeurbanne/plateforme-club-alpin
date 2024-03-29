<?php

use App\Entity\Evt;
use App\Entity\EventParticipation;
use App\Entity\User;
use App\Repository\CommissionRepository;
use App\Utils\NicknameGenerator;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

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

        $commissionRepo = $this->container->get(CommissionRepository::class);
        $comm = $commissionRepo->findVisibleCommission('sorties-familles');

        $licenceNum = 749999999990;
        $start = time() + 86400 * 10;

        $users = [];

        foreach ($set->getObjects() as $object) {
            if ($object instanceof User) {
                $object->setCafnum($licenceNum--);
                $object->setNickname(NicknameGenerator::generateNickname($object->getFirstname(), $object->getLastname()));
                $users[] = $object;

                $manager->persist($object);
            }
        }

        $roles = [
            EventParticipation::ROLE_ENCADRANT,
            EventParticipation::ROLE_BENEVOLE,
            EventParticipation::ROLE_COENCADRANT,
            EventParticipation::ROLE_MANUEL,
            EventParticipation::ROLE_BENEVOLE,
        ];
        $status = [
            EventParticipation::STATUS_VALIDE,
            EventParticipation::STATUS_NON_CONFIRME,
            EventParticipation::STATUS_REFUSE,
        ];

        foreach ($set->getObjects() as $object) {
            if ($object instanceof Evt) {
                $object->setStatus(mt_rand(0, 2));
                $object->setTsp($start);
                $object->setTspEnd($start + mt_rand(1, 4) * 86400);
                $object->setJoinStart(time());
                $object->setJoinMax(10);
                $object->setNgensMax(10);
                $object->setCommission($comm);

                $manager->persist($object);

                $start += mt_rand(1, 5) * 86400;

                shuffle($users);
                $i = 0;
                $limit = mt_rand(4, 8);

                foreach ($users as $user) {
                    $participation = new EventParticipation($object, $user, $roles[array_rand($roles)], $status[array_rand($status)]);
                    if (in_array($participation->getRole(), [EventParticipation::ROLE_ENCADRANT, EventParticipation::ROLE_BENEVOLE, EventParticipation::ROLE_COENCADRANT], true)) {
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

    public function setUserEmail(string $email): void
    {
        $this->userEmail = $email;
    }
}
