<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration finale pour supprimer les anciennes colonnes BIGINT après validation complète
 * À EXÉCUTER SEULEMENT APRÈS VALIDATION COMPLÈTE DE LA MIGRATION PRÉCÉDENTE.
 */
final class Version20250905091000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Supprime les anciennes colonnes BIGINT de la table caf_user après migration complète vers DATETIME';
    }

    public function up(Schema $schema): void
    {
        // Supprimer les triggers de synchronisation
        $this->addSql('DROP TRIGGER IF EXISTS sync_user_dates_insert');
        $this->addSql('DROP TRIGGER IF EXISTS sync_user_dates_update');

        // Supprimer les anciennes colonnes BIGINT
        $this->addSql('ALTER TABLE caf_user
            DROP COLUMN created_user,
            DROP COLUMN birthday_user,
            DROP COLUMN date_adhesion_user,
            DROP COLUMN ts_insert_user,
            DROP COLUMN ts_update_user
        ');
    }

    public function down(Schema $schema): void
    {
        // Recréer les anciennes colonnes BIGINT
        $this->addSql('ALTER TABLE caf_user
            ADD COLUMN created_user BIGINT NOT NULL DEFAULT 0,
            ADD COLUMN birthday_user BIGINT DEFAULT NULL,
            ADD COLUMN date_adhesion_user BIGINT DEFAULT NULL,
            ADD COLUMN ts_insert_user BIGINT DEFAULT NULL COMMENT \'timestamp 1ere insertion\',
            ADD COLUMN ts_update_user BIGINT DEFAULT NULL COMMENT \'timestamp derniere maj\'
        ');

        // Restaurer les données depuis les nouvelles colonnes
        $this->addSql('UPDATE caf_user SET
            created_user = CASE
                WHEN created_at IS NOT NULL
                THEN UNIX_TIMESTAMP(created_at)
                ELSE 0
            END,
            birthday_user = CASE
                WHEN date_naissance IS NOT NULL
                THEN UNIX_TIMESTAMP(date_naissance)
                ELSE NULL
            END,
            date_adhesion_user = CASE
                WHEN date_adhesion_at IS NOT NULL
                THEN UNIX_TIMESTAMP(date_adhesion_at)
                ELSE NULL
            END,
            ts_insert_user = CASE
                WHEN first_inserted_at IS NOT NULL
                THEN UNIX_TIMESTAMP(first_inserted_at)
                ELSE NULL
            END,
            ts_update_user = CASE
                WHEN last_updated_at IS NOT NULL
                THEN UNIX_TIMESTAMP(last_updated_at)
                ELSE NULL
            END
        ');
    }
}
