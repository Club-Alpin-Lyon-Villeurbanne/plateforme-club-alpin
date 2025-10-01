<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250930154602 extends AbstractMigration
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
    }

    public function down(Schema $schema): void
    {

        $this->addSql('ALTER TABLE caf_evt_join ADD tsp_evt_join INT NOT NULL, ADD lastchange_when_evt_join INT DEFAULT NULL COMMENT \'Quand a été modifié cet élément\'');
        $this->addSql('ALTER TABLE caf_article ADD tsp_crea_article INT NOT NULL COMMENT \'Timestamp de création de l\'\'article\', ADD tsp_validate_article INT DEFAULT NULL, ADD tsp_article INT NOT NULL COMMENT \'Timestamp affiché de l\'\'article\', ADD tsp_lastedit DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'Date de dernière modif\'');
        $this->addSql('ALTER TABLE caf_comment ADD tsp_comment BIGINT NOT NULL');
    }
}
