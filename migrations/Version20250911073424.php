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
        $this->addSql('ALTER TABLE caf_evt ADD main_transport_mode VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE caf_evt ADD carbon_cost DOUBLE PRECISION DEFAULT NULL, ADD nb_km DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt DROP main_transport_mode');
        $this->addSql('ALTER TABLE caf_evt DROP nb_km, DROP carbon_cost');
    }
}
