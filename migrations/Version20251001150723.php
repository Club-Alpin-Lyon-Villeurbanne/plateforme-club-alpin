<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251001150723 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Removes no longer used fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user DROP created_user, DROP ts_insert_user, DROP ts_update_user');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user ADD created_user BIGINT NOT NULL, ADD ts_insert_user BIGINT DEFAULT NULL COMMENT \'timestamp 1ere insertion\', ADD ts_update_user BIGINT DEFAULT NULL COMMENT \'timestamp derniere maj\'');
    }
}
