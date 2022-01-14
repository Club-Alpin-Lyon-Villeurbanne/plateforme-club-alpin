<?php

use App\Entity\Evt;
use App\Entity\EvtJoin;
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
            EvtJoin::ROLE_ENCADRANT,
            EvtJoin::ROLE_BENEVOLE,
            EvtJoin::ROLE_COENCADRANT,
            EvtJoin::ROLE_MANUEL,
            EvtJoin::ROLE_BENEVOLE,
        ];
        $status = [
            EvtJoin::STATUS_VALIDE,
            EvtJoin::STATUS_NON_CONFIRME,
            EvtJoin::STATUS_REFUSE,
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
                    $participant = new EvtJoin($object, $user, $roles[array_rand($roles)], $status[array_rand($status)]);
                    if (in_array($participant->getRole(), [EvtJoin::ROLE_ENCADRANT, EvtJoin::ROLE_BENEVOLE, EvtJoin::ROLE_COENCADRANT], true)) {
                        $participant->setStatus(EvtJoin::STATUS_VALIDE);
                    }
                    $manager->persist($participant);
                    if (++$i > $limit) {
                        break;
                    }
                }

                if ($owner = $object->getParticipant($object->getUser())) {
                    $owner->setRole(EvtJoin::ROLE_ENCADRANT);
                } else {
                    $participant = new EvtJoin($object, $object->getUser(), EvtJoin::ROLE_ENCADRANT, EvtJoin::STATUS_VALIDE);
                    $manager->persist($participant);
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
