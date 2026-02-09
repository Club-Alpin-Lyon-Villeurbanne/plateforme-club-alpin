<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260202143015 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds new fields to store carbon cost informations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt ADD nb_vehicle INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE caf_evt ADD start_lat NUMERIC(11, 8) NOT NULL, ADD start_long NUMERIC(11, 8) NOT NULL');
        $this->addSql('UPDATE caf_evt SET start_lat = 0, start_long = 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt DROP start_lat, DROP start_long');
        $this->addSql('ALTER TABLE caf_evt DROP nb_vehicle');
    }
}
