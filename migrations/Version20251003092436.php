<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251003092436 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Removes no longer used fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt_join DROP tsp_evt_join, DROP lastchange_when_evt_join');
        $this->addSql('ALTER TABLE caf_article DROP tsp_crea_article, DROP tsp_validate_article, DROP tsp_article, DROP tsp_lastedit');
        $this->addSql('ALTER TABLE caf_comment DROP tsp_comment');
        $this->addSql('ALTER TABLE caf_evt DROP cancelled_when_evt, DROP tsp_evt, DROP tsp_end_evt, DROP tsp_crea_evt, DROP tsp_edit_evt, DROP join_start_evt');
        $this->addSql('ALTER TABLE caf_user DROP date_adhesion_user, DROP birthday_user');
        $this->addSql('ALTER TABLE caf_user DROP created_user, DROP ts_insert_user, DROP ts_update_user');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt_join ADD tsp_evt_join INT NOT NULL, ADD lastchange_when_evt_join INT DEFAULT NULL COMMENT \'Quand a été modifié cet élément\'');
        $this->addSql('ALTER TABLE caf_article ADD tsp_crea_article INT NOT NULL COMMENT \'Timestamp de création de l\'\'article\', ADD tsp_validate_article INT DEFAULT NULL, ADD tsp_article INT NOT NULL COMMENT \'Timestamp affiché de l\'\'article\', ADD tsp_lastedit DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'Date de dernière modif\'');
        $this->addSql('ALTER TABLE caf_comment ADD tsp_comment BIGINT NOT NULL');
        $this->addSql('ALTER TABLE caf_user ADD date_adhesion_user BIGINT DEFAULT NULL, ADD birthday_user BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE caf_user ADD created_user BIGINT NOT NULL, ADD ts_insert_user BIGINT DEFAULT NULL COMMENT \'timestamp 1ere insertion\', ADD ts_update_user BIGINT DEFAULT NULL COMMENT \'timestamp derniere maj\'');
        $this->addSql('ALTER TABLE caf_evt ADD cancelled_when_evt BIGINT DEFAULT NULL COMMENT \'Timestamp annulation\', ADD tsp_evt BIGINT DEFAULT NULL COMMENT \'timestamp du début du event\', ADD tsp_end_evt BIGINT DEFAULT NULL, ADD tsp_crea_evt BIGINT NOT NULL COMMENT \'Création de l\'\'entrée\', ADD tsp_edit_evt BIGINT DEFAULT NULL, ADD join_start_evt INT DEFAULT NULL COMMENT \'Timestamp de départ des inscriptions\'');
    }
}
