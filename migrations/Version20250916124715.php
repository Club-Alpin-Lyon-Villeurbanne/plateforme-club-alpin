<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250916124715 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds new field to store if payment is done';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt_join ADD has_paid TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt_join DROP has_paid');
    }
}
