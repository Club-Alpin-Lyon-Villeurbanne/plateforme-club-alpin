<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251009084359 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Lengthen place_evt field in caf_evt table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt CHANGE place_evt place_evt VARCHAR(255) NOT NULL COMMENT \'Lieu de départ activité\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt CHANGE place_evt place_evt VARCHAR(100) NOT NULL COMMENT \'Lieu de départ activité\'');
    }
}
