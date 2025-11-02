<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251102123236 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute des contraintes UNIQUE pour éviter les doublons dans les tables de formations';
    }

    public function up(Schema $schema): void
    {
        // Supprimer les doublons existants dans formation_brevet
        // Garder l'enregistrement avec l'id le plus élevé pour chaque combinaison (user_id, brevet_id)
        $this->addSql(<<<SQL
            DELETE fb1 FROM formation_brevet fb1
            INNER JOIN formation_brevet fb2
                ON fb1.user_id = fb2.user_id
                AND fb1.brevet_id = fb2.brevet_id
                AND fb1.id < fb2.id
        SQL);

        // Ajouter la contrainte UNIQUE sur formation_brevet (user_id, brevet_id)
        $this->addSql('ALTER TABLE formation_brevet ADD CONSTRAINT UNIQ_BREVET_USER_BREVET UNIQUE (user_id, brevet_id)');

        // Supprimer les doublons existants dans formation_validation
        // Garder l'enregistrement avec l'id le plus élevé pour chaque combinaison (user_id, id_interne)
        $this->addSql(<<<SQL
            DELETE fv1 FROM formation_validation fv1
            INNER JOIN formation_validation fv2
                ON fv1.user_id = fv2.user_id
                AND (fv1.id_interne = fv2.id_interne OR (fv1.id_interne IS NULL AND fv2.id_interne IS NULL))
                AND fv1.id < fv2.id
        SQL);

        // Ajouter la contrainte UNIQUE sur formation_validation (user_id, id_interne)
        $this->addSql('ALTER TABLE formation_validation ADD CONSTRAINT UNIQ_FORM_VAL_USER_ID_INTERNE UNIQUE (user_id, id_interne)');
    }

    public function down(Schema $schema): void
    {
        // Supprimer les contraintes UNIQUE
        $this->addSql('ALTER TABLE formation_brevet DROP INDEX UNIQ_BREVET_USER_BREVET');
        $this->addSql('ALTER TABLE formation_validation DROP INDEX UNIQ_FORM_VAL_USER_ID_INTERNE');
    }
}
