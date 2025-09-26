<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250926085344 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds new field to store commission configuration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_commission ADD mandatory_fields JSON DEFAULT NULL');
        $this->addSql('UPDATE caf_commission SET mandatory_fields = \'["difficulte", "distance", "denivele"]\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_commission DROP mandatory_fields');
    }
}
