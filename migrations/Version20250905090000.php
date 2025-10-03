<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour convertir les colonnes BIGINT timestamp vers DATETIME pour l'entité User.
 */
final class Version20250905090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les nouvelles colonnes DATETIME pour remplacer les BIGINT timestamps dans la table caf_user';
    }

    public function up(Schema $schema): void
    {
        // Ajouter les nouvelles colonnes DATETIME (temporairement avec un suffixe _new)
        $this->addSql('ALTER TABLE caf_user
            ADD COLUMN created_at DATETIME DEFAULT NULL COMMENT \'Date de création du compte\',
            ADD COLUMN date_naissance DATETIME DEFAULT NULL COMMENT \'Date de naissance\',
            ADD COLUMN date_adhesion_at DATETIME DEFAULT NULL COMMENT \'Date d\'\'adhésion\',
            ADD COLUMN first_inserted_at DATETIME DEFAULT NULL COMMENT \'Date de première insertion\',
            ADD COLUMN last_updated_at DATETIME DEFAULT NULL COMMENT \'Date de dernière mise à jour\'
        ');

        // Copier les données existantes en convertissant les timestamps
        $this->addSql('UPDATE caf_user SET
            created_at = CASE
                WHEN created_user IS NOT NULL AND created_user > 0
                THEN FROM_UNIXTIME(created_user)
                ELSE NULL
            END,
            date_naissance = CASE
                WHEN birthday_user IS NOT NULL AND birthday_user > 0
                THEN FROM_UNIXTIME(birthday_user)
                ELSE NULL
            END,
            date_adhesion_at = CASE
                WHEN date_adhesion_user IS NOT NULL AND date_adhesion_user > 0
                THEN FROM_UNIXTIME(date_adhesion_user)
                ELSE NULL
            END,
            first_inserted_at = CASE
                WHEN ts_insert_user IS NOT NULL AND ts_insert_user > 0
                THEN FROM_UNIXTIME(ts_insert_user)
                ELSE NULL
            END,
            last_updated_at = CASE
                WHEN ts_update_user IS NOT NULL AND ts_update_user > 0
                THEN FROM_UNIXTIME(ts_update_user)
                ELSE NULL
            END
        ');

        // Créer des triggers pour maintenir la synchronisation pendant la transition
        $this->addSql('
            CREATE TRIGGER sync_user_dates_insert
            AFTER INSERT ON caf_user
            FOR EACH ROW
            BEGIN
                IF NEW.created_user IS NOT NULL AND NEW.created_at IS NULL THEN
                    UPDATE caf_user SET created_at = FROM_UNIXTIME(NEW.created_user) WHERE id_user = NEW.id_user;
                END IF;
                IF NEW.birthday_user IS NOT NULL AND NEW.date_naissance IS NULL THEN
                    UPDATE caf_user SET date_naissance = FROM_UNIXTIME(NEW.birthday_user) WHERE id_user = NEW.id_user;
                END IF;
                IF NEW.date_adhesion_user IS NOT NULL AND NEW.date_adhesion_at IS NULL THEN
                    UPDATE caf_user SET date_adhesion_at = FROM_UNIXTIME(NEW.date_adhesion_user) WHERE id_user = NEW.id_user;
                END IF;
                IF NEW.ts_insert_user IS NOT NULL AND NEW.first_inserted_at IS NULL THEN
                    UPDATE caf_user SET first_inserted_at = FROM_UNIXTIME(NEW.ts_insert_user) WHERE id_user = NEW.id_user;
                END IF;
                IF NEW.ts_update_user IS NOT NULL AND NEW.last_updated_at IS NULL THEN
                    UPDATE caf_user SET last_updated_at = FROM_UNIXTIME(NEW.ts_update_user) WHERE id_user = NEW.id_user;
                END IF;
            END
        ');

        $this->addSql('
            CREATE TRIGGER sync_user_dates_update
            AFTER UPDATE ON caf_user
            FOR EACH ROW
            BEGIN
                IF NEW.created_user != OLD.created_user OR (NEW.created_user IS NOT NULL AND OLD.created_user IS NULL) THEN
                    UPDATE caf_user SET created_at = FROM_UNIXTIME(NEW.created_user) WHERE id_user = NEW.id_user;
                END IF;
                IF NEW.birthday_user != OLD.birthday_user OR (NEW.birthday_user IS NOT NULL AND OLD.birthday_user IS NULL) THEN
                    UPDATE caf_user SET date_naissance = FROM_UNIXTIME(NEW.birthday_user) WHERE id_user = NEW.id_user;
                END IF;
                IF NEW.date_adhesion_user != OLD.date_adhesion_user OR (NEW.date_adhesion_user IS NOT NULL AND OLD.date_adhesion_user IS NULL) THEN
                    UPDATE caf_user SET date_adhesion_at = FROM_UNIXTIME(NEW.date_adhesion_user) WHERE id_user = NEW.id_user;
                END IF;
                IF NEW.ts_insert_user != OLD.ts_insert_user OR (NEW.ts_insert_user IS NOT NULL AND OLD.ts_insert_user IS NULL) THEN
                    UPDATE caf_user SET first_inserted_at = FROM_UNIXTIME(NEW.ts_insert_user) WHERE id_user = NEW.id_user;
                END IF;
                IF NEW.ts_update_user != OLD.ts_update_user OR (NEW.ts_update_user IS NOT NULL AND OLD.ts_update_user IS NULL) THEN
                    UPDATE caf_user SET last_updated_at = FROM_UNIXTIME(NEW.ts_update_user) WHERE id_user = NEW.id_user;
                END IF;
            END
        ');

        // Ajouter des index sur les nouvelles colonnes pour les performances
        $this->addSql('CREATE INDEX idx_user_created_at ON caf_user (created_at)');
        $this->addSql('CREATE INDEX idx_user_date_adhesion_at ON caf_user (date_adhesion_at)');
        $this->addSql('CREATE INDEX idx_user_last_updated_at ON caf_user (last_updated_at)');
    }

    public function down(Schema $schema): void
    {
        // Supprimer les triggers
        $this->addSql('DROP TRIGGER IF EXISTS sync_user_dates_insert');
        $this->addSql('DROP TRIGGER IF EXISTS sync_user_dates_update');

        // Supprimer les index
        $this->addSql('DROP INDEX idx_user_created_at ON caf_user');
        $this->addSql('DROP INDEX idx_user_date_adhesion_at ON caf_user');
        $this->addSql('DROP INDEX idx_user_last_updated_at ON caf_user');

        // Supprimer les nouvelles colonnes
        $this->addSql('ALTER TABLE caf_user
            DROP COLUMN created_at,
            DROP COLUMN date_naissance,
            DROP COLUMN date_adhesion_at,
            DROP COLUMN first_inserted_at,
            DROP COLUMN last_updated_at
        ');
    }
}
