<?php

namespace App\Repository;

use App\Entity\UserRight;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRightRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserRight::class);
    }

    public function findRightsByUser(int $userId): array
    {
        $sql = '
            SELECT DISTINCT ur.code_userright AS code,
                            ua.params_user_attr AS params,
                            ut.limited_to_comm_usertype AS limitedToComm
            FROM caf_userright ur
            INNER JOIN caf_usertype_attr uta
                ON ur.id_userright = uta.right_usertype_attr
            INNER JOIN caf_usertype ut
                ON uta.type_usertype_attr = ut.id_usertype
            INNER JOIN caf_user_attr ua
                ON ua.usertype_user_attr = ut.id_usertype
            WHERE ua.user_user_attr = :userId
            ORDER BY ua.params_user_attr ASC, ur.code_userright ASC, ut.limited_to_comm_usertype ASC
        ';

        $statement = $this->getEntityManager()->getConnection()->prepare($sql);
        $statement->bindValue('userId', $userId);
        $results = $statement->executeQuery()->fetchAllAssociative();

        // Appel de la fonction de traitement des résultats
        return $this->processRightsResults($results);
    }

    public function getRightsByUserType(string $userType): array
    {
        $sql = '
            SELECT DISTINCT ur.code_userright
            FROM caf_userright ur
            INNER JOIN caf_usertype_attr uta ON ur.id_userright = uta.right_usertype_attr
            INNER JOIN caf_usertype ut ON uta.type_usertype_attr = ut.id_usertype
            WHERE ut.code_usertype = :userType
            ORDER BY ur.code_userright ASC
        ';
        $statement = $this->getEntityManager()->getConnection()->prepare($sql);
        $statement->bindValue('userType', $userType);

        return $statement->executeQuery()->fetchAllAssociative();
    }

    /**
     * @return UserRight[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.parent', 'ASC')
            ->addOrderBy('u.ordre', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    private function processRightsResults(array $results): array
    {
        $userRights = [];

        foreach ($results as $result) {
            $code = $result['code'];
            $params = $result['params'] ?? null;
            $limitedToComm = $result['limitedToComm'] ?? false;

            $value = ($params && $limitedToComm) ? $params : true;

            if (!isset($userRights[$code])) {
                $userRights[$code] = $value;
            } elseif (true !== $userRights[$code]) {
                if (true === $value) {
                    $userRights[$code] = true;
                } else {
                    $existingValue = $userRights[$code];
                    $separator = !empty($existingValue) ? '|' : '';
                    $userRights[$code] = $existingValue . $separator . $value;
                }
            }
        }

        return $userRights;
    }
}
