<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250513120836 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt ADD created_at DATETIME DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL');
        $this->addSql('UPDATE caf_evt SET created_at = FROM_UNIXTIME(tsp_crea_evt) WHERE tsp_crea_evt IS NOT NULL');
        $this->addSql('UPDATE caf_evt SET updated_at = created_at WHERE tsp_edit_evt IS NULL');
        $this->addSql('UPDATE caf_evt SET updated_at = FROM_UNIXTIME(tsp_edit_evt) WHERE tsp_edit_evt IS NOT NULL');
        $this->addSql('ALTER TABLE caf_evt CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt DROP created_at, DROP updated_at');
    }
}
