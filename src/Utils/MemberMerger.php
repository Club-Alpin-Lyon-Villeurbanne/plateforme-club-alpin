<?php

namespace App\Utils;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * This class is used to swap members IDs to keep user history
 * in the application. We have to do that because the FFCAM sometimes attributes
 * a totally new CAF number even if the user is a known user in our DB.
 *
 * We temporarily disable the foreign key checks during the swapping process
 * to avoid constraint on children tables.
 */
class MemberMerger
{
    private $userRepository;

    private $entityManager;

    public function __construct(UserRepository $userRepository, EntityManagerInterface $entityManager)
    {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    public function mergeMembers(string $oldCafNum, string $newCafNum)
    {
        try {
            // Démarrer la transaction
            $this->entityManager->beginTransaction();

            $oldCafUser = $this->userRepository->findOneByLicenseNumber($oldCafNum);
            $newCafUser = $this->userRepository->findOneByLicenseNumber($newCafNum);

            if (!$oldCafUser) {
                throw new \Exception('Unable to find an user with this cafnum ' . $oldCafNum);
            }

            if (!$newCafUser) {
                throw new \Exception('Unable to find an user with this cafnum ' . $newCafNum);
            }

            $cafnum = $newCafUser->getCafnum();

            $newCafUser->setCafnum('obs_' . $cafnum);
            $newCafUser->setEmail('obs_' . time());
            $newCafUser->setIsDeleted(true);

            $this->entityManager->flush();

            $oldCafUser->setFirstname($newCafUser->getFirstname());
            $oldCafUser->setLastname($newCafUser->getLastname());
            $oldCafUser->setCafnum($cafnum);
            $oldCafUser->setTel($newCafUser->getTel() ?? '');
            $oldCafUser->setTel2($newCafUser->getTel2() ?? '');
            $oldCafUser->setAdresse($newCafUser->getAdresse() ?? '');
            $oldCafUser->setCp($newCafUser->getCp() ?? '');
            $oldCafUser->setVille($newCafUser->getVille() ?? '');
            $oldCafUser->setCafnumParent($newCafUser->getCafnumParent());
            $oldCafUser->setDoitRenouveler($newCafUser->getDoitRenouveler());
            $oldCafUser->setAlerteRenouveler($newCafUser->getAlerteRenouveler());

            $this->entityManager->flush();

            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }
}
