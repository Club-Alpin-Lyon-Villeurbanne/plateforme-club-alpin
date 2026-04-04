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
        // nb_vehicules has a DEFAULT, can be added directly as NOT NULL
        $this->addSql('ALTER TABLE caf_evt ADD nb_vehicules INT DEFAULT 1 NOT NULL');

        // Step 1: add columns as nullable
        $this->addSql('ALTER TABLE caf_evt ADD lat_depart NUMERIC(11, 8) DEFAULT NULL, ADD long_depart NUMERIC(11, 8) DEFAULT NULL');

        // Step 2: populate existing rows
        $this->addSql('UPDATE caf_evt SET lat_depart = 0, long_depart = 0 WHERE lat_depart IS NULL');

        // Step 3: set NOT NULL
        $this->addSql('ALTER TABLE caf_evt CHANGE lat_depart lat_depart NUMERIC(11, 8) NOT NULL, CHANGE long_depart long_depart NUMERIC(11, 8) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt DROP lat_depart, DROP long_depart');
        $this->addSql('ALTER TABLE caf_evt DROP nb_vehicules');
    }
}
