<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221010202240 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt DROP repas_restaurant, DROP tarif_restaurant, DROP cb_evt');
        $this->addSql('ALTER TABLE caf_evt_join DROP id_bus_lieu_destination, DROP id_destination, DROP is_restaurant, DROP is_cb');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt ADD repas_restaurant TINYINT(1) DEFAULT \'0\' NOT NULL, ADD tarif_restaurant DOUBLE PRECISION DEFAULT NULL, ADD cb_evt TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE caf_evt_join ADD id_bus_lieu_destination INT UNSIGNED DEFAULT NULL, ADD id_destination INT UNSIGNED DEFAULT NULL, ADD is_restaurant TINYINT(1) DEFAULT NULL, ADD is_cb TINYINT(1) DEFAULT NULL');
    }
}
