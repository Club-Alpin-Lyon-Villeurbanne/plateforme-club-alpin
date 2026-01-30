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
        $this->addSql('ALTER TABLE communes ADD geopoint VARCHAR(255) NOT NULL, ADD latitude VARCHAR(255) NOT NULL, ADD longitude VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE communes DROP geopoint, DROP latitude, DROP longitude');
    }
}
