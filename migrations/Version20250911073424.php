<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250911073424 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds new field for carbon cost calculation to caf_evt table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt ADD main_transport_mode VARCHAR(50) DEFAULT NULL, ADD nb_vehicle INT DEFAULT NULL, ADD nb_km DOUBLE PRECISION DEFAULT NULL, ADD carbon_cost DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt DROP main_transport_mode, DROP nb_vehicle, DROP nb_km, DROP carbon_cost');
    }
}
