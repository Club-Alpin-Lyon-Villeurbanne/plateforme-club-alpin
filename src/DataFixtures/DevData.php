<?php

namespace App\DataFixtures;

use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Entity\User;
use App\Entity\UserAttr;
use App\Repository\CommissionRepository;
use App\Repository\UserRepository;
use App\Repository\UsertypeRepository;
use App\Utils\NicknameGenerator;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Nelmio\Alice\Loader\SimpleFilesLoader;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AutoconfigureTag('dev.data_fixtures')]
#[When(env: 'dev')]
#[When(env: 'test')]
class DevData implements FixtureInterface
{
    public function __construct(
        #[Autowire('@nelmio_alice.files_loader')] private readonly SimpleFilesLoader $filesLoader,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
        private readonly CommissionRepository $commissionRepository,
        private readonly UserRepository $userRepository,
        private readonly UsertypeRepository $usertypeRepository,
    ) {
    }

    public function load(ObjectManager $manager)
    {
        $set = $this->filesLoader->loadFiles(glob($this->projectDir . '/resources/fixtures/dev/alice/*.yaml'));

        $licenceNum = 749999999990;

        $users = [];

        foreach ($set->getObjects() as $object) {
            if ($object instanceof User) {
                $object->setCafnum($licenceNum--);
                $object->setNickname(NicknameGenerator::generateNickname($object->getFirstname(), $object->getLastname()));
                $users[] = $object;
            }

            $manager->persist($object);
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

        $n = 0;
        foreach ($set->getObjects() as $object) {
            $start = time() + 86400 * $n;
            $n += 2;
            if ($object instanceof Evt) {
                $object->setTsp($start);
                $object->setTspEnd($start + 86400 * 3);
                $object->setJoinStart(time());

                shuffle($users);
                $i = 0;
                $limit = 4;

                foreach ($users as $user) {
                    // Owner of an event should not be added as participant
                    if ($user === $object->getUser()) {
                        continue;
                    }

                    $participation = new EventParticipation($object, $user, $roles[array_rand($roles)], $status[array_rand($status)]);
                    if (\in_array($participation->getRole(), [EventParticipation::ROLE_ENCADRANT, EventParticipation::ROLE_BENEVOLE, EventParticipation::ROLE_COENCADRANT], true)) {
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

        $admin = $this->userRepository->findUserByEmail('test@clubalpinlyon.fr');
        $this->addAttribute($admin, UserAttr::PRESIDENT);
        $this->addAttribute($admin, UserAttr::DEVELOPPEUR);
        $this->addAttribute($admin, UserAttr::ADMINISTRATEUR);
        $this->addAttribute($admin, UserAttr::RESPONSABLE_COMMISSION, 'commission:alpinisme');
        $this->addAttribute($admin, UserAttr::RESPONSABLE_COMMISSION, 'commission:sorties-famille');
        $this->addAttribute($admin, UserAttr::ENCADRANT, 'commission:alpinisme');
        $this->addAttribute($admin, UserAttr::ENCADRANT, 'commission:sorties-famille');

        $manager->flush();
    }

    protected function addAttribute(User $user, string $attribute, ?string $param = null)
    {
        $user->addAttribute($this->usertypeRepository->getByCode($attribute), $param);
    }
}
