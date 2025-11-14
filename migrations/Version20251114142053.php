<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251114142053 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds new field to store info for discovery-type members';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user ADD validity_duration INT DEFAULT NULL COMMENT \'Durée de validité (en h) de licence découverte\'');
        $this->addSql('UPDATE caf_user SET validity_duration = 24 WHERE nomade_user = 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user DROP validity_duration');
    }
}
