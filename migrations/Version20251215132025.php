<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251215132025 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'join_date is now a datetime';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user CHANGE join_date join_date DATETIME DEFAULT NULL COMMENT \'Date adhésion(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user CHANGE join_date join_date DATE DEFAULT NULL COMMENT \'Date adhésion(DC2Type:date_immutable)\'');
    }
}
