<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251128133218 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds new fields to store gps coordinates';
    }

    public function up(Schema $schema): void
    {
        // Step 1: add columns as nullable
        $this->addSql('ALTER TABLE communes ADD geopoint VARCHAR(255) DEFAULT NULL, ADD latitude NUMERIC(11, 8) DEFAULT NULL, ADD longitude NUMERIC(11, 8) DEFAULT NULL');

        // Step 2: populate existing rows
        $this->addSql('UPDATE communes SET geopoint = \'\', latitude = 0, longitude = 0 WHERE latitude IS NULL');

        // Step 3: set NOT NULL on latitude and longitude (geopoint stays nullable)
        $this->addSql('ALTER TABLE communes CHANGE latitude latitude NUMERIC(11, 8) NOT NULL, CHANGE longitude longitude NUMERIC(11, 8) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE communes DROP geopoint, DROP latitude, DROP longitude');
    }
}
