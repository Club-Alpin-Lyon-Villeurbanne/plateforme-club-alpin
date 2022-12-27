<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221007104351 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `caf_evt` CHANGE repas_restaurant repas_restaurant TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `caf_evt` CHANGE repas_restaurant repas_restaurant TINYINT(1) UNSIGNED NOT NULL');
    }
}
