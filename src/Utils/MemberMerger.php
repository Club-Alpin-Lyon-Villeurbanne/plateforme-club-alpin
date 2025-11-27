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

    public function mergeExistingMembers(string $oldCafNum, string $newCafNum): void
    {
        try {
            $this->entityManager->beginTransaction();

            $oldCafUser = $this->userRepository->findOneByLicenseNumber($oldCafNum);

            if (!$oldCafUser) {
                throw new \Exception('Unable to find an user with this cafnum ' . $oldCafNum);
            }

            $newCafUser = $this->userRepository->findOneByLicenseNumber($newCafNum);

            if (!$newCafUser) {
                throw new \Exception('Unable to find an user with this cafnum ' . $newCafNum);
            }

            $clone = clone $newCafUser;
            $cafnum = $newCafUser->getCafnum();

            $newCafUser->setCafnum('obs_' . $cafnum)
            ->setEmail('obs_' . time() . '_' . bin2hex(random_bytes(8)))
            ->setIsDeleted(true);
            $this->entityManager->flush();

            $this->mergeUser($oldCafUser, $clone);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    /**
     * @param string $oldCafNum  The CAF number of the existing user to keep
     * @param User   $newCafUser The new user to merge
     */
    public function mergeNewMember(string $oldCafNum, User $newCafUser): void
    {
        try {
            $this->entityManager->beginTransaction();

            $oldCafUser = $this->userRepository->findOneByLicenseNumber($oldCafNum);

            if (!$oldCafUser) {
                throw new \Exception('Unable to find an user with this cafnum ' . $oldCafNum);
            }

            $this->mergeUser($oldCafUser, $newCafUser);

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    private function mergeUser(User $oldCafUser, User $newCafUser)
    {
        $oldCafUser->setFirstname($newCafUser->getFirstname())
        ->setLastname($newCafUser->getLastname())
        ->setCafnum($newCafUser->getCafnum())
        ->setTel($newCafUser->getTel() ?? '')
        ->setTel2($newCafUser->getTel2() ?? '')
        ->setAdresse($newCafUser->getAdresse() ?? '')
        ->setCp($newCafUser->getCp() ?? '')
        ->setVille($newCafUser->getVille() ?? '')
        ->setCafnumParent($newCafUser->getCafnumParent())
        ->setDoitRenouveler($newCafUser->getDoitRenouveler())
        ->setAlerteRenouveler($newCafUser->getAlerteRenouveler())
        ->setRadiationDate($newCafUser->getRadiationDate())
        ->setRadiationReason($newCafUser->getRadiationReason())
            ->setEmail($newCafUser->getEmail())
        ;

        // Mettre à jour la date d'adhésion uniquement si elle est valide
        // Cela évite d'écraser une date existante avec NULL lors du renouvellement
        if (null !== $newCafUser->getJoinDate()) {
            $oldCafUser->setJoinDate($newCafUser->getJoinDate());
        }
    }
}
