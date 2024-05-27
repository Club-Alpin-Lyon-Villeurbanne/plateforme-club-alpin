<?php

namespace App\Utils;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use App\Entity\User;

class UserIdSwapper
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function swapIds(int $id1, int $id2): void
    {
        $conn = $this->entityManager->getConnection();
        $conn->beginTransaction(); // Commence la transaction

        try {
            // Générer un ID temporaire aléatoire
            $tempId = $this->generateRandomBigint();

            // Mettre à jour l'ID1 avec l'ID temporaire
            $this->updateId($id1, $tempId);

            // Mettre à jour l'ID2 avec ID1
            $this->updateId($id2, $id1);

            // Mettre à jour l'ID temporaire avec ID2
            $this->updateId($tempId, $id2);

            $conn->commit(); // Valide la transaction
        } catch (Exception $e) {
            $conn->rollBack(); // Annule la transaction en cas d'échec
            throw $e; // Rethrow exception
        }
    }

    private function generateRandomBigint(): int
    {
        // Les valeurs limites pour un BIGINT
        $min = PHP_INT_MIN;
        $max = -1; 

        return random_int($min, $max);
    }

    private function updateId(int $currentId, int $newId): void
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->update(User::class, 'e')
            ->set('e.id', ':newId')
            ->where('e.id = :currentId')
            ->setParameter('newId', $newId)
            ->setParameter('currentId', $currentId);

        $qb->getQuery()->execute();
    }
}