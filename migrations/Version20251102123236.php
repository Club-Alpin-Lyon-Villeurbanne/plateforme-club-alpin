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
    }
}
