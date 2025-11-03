<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251102123236 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute des contraintes UNIQUE pour éviter les doublons dans les tables de formations et supprime la colonne redondante cafnum_user';
    }

    public function up(Schema $schema): void
    {
        // Supprimer l'index redondant idx_cafnum sur formation_brevet
        $this->addSql('DROP INDEX idx_cafnum ON formation_brevet');

        // Supprimer la colonne redondante cafnum_user (l'info est déjà disponible via user_id)
        $this->addSql('ALTER TABLE formation_brevet DROP COLUMN cafnum_user');

        // Ajouter la contrainte UNIQUE sur formation_brevet (user_id, brevet_id)
        $this->addSql('ALTER TABLE formation_brevet ADD CONSTRAINT UNIQ_BREVET_USER_BREVET UNIQUE (user_id, brevet_id)');

        // Ajouter la contrainte UNIQUE sur formation_validation (user_id, id_interne)
        $this->addSql('ALTER TABLE formation_validation ADD CONSTRAINT UNIQ_FORM_VAL_USER_ID_INTERNE UNIQUE (user_id, id_interne)');
    }

    public function down(Schema $schema): void
    {
        // Supprimer les contraintes UNIQUE
        $this->addSql('ALTER TABLE formation_brevet DROP INDEX UNIQ_BREVET_USER_BREVET');
        $this->addSql('ALTER TABLE formation_validation DROP INDEX UNIQ_FORM_VAL_USER_ID_INTERNE');

        // Restaurer la colonne cafnum_user (pour rollback complet)
        $this->addSql('ALTER TABLE formation_brevet ADD cafnum_user VARCHAR(20) NOT NULL');

        // Restaurer l'index idx_cafnum
        $this->addSql('CREATE INDEX idx_cafnum ON formation_brevet (cafnum_user)');
    }
}
