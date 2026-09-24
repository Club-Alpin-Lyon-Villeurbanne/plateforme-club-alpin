<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260504100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute user_log_admin (FK vers caf_user) dans caf_log_admin';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_log_admin ADD COLUMN user_log_admin BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE caf_log_admin ADD CONSTRAINT FK_log_admin_user FOREIGN KEY (user_log_admin) REFERENCES caf_user (id_user) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_log_admin DROP FOREIGN KEY FK_log_admin_user');
        $this->addSql('ALTER TABLE caf_log_admin DROP COLUMN user_log_admin');
    }
}
