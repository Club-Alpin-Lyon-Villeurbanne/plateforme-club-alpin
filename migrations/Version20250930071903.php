<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250930071903 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds new field to store last login date';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user ADD last_login_date DATETIME DEFAULT NULL COMMENT \'Date de dernière connexion\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user DROP last_login_date');
    }
}
