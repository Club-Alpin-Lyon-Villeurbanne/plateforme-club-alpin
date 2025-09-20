<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250911151601 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds new field to store commission configuration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_commission ADD mandatory_fields VARCHAR(30) DEFAULT \'difficulte,distance,denivele\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_commission DROP mandatory_fields');
    }
}
