<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251008092645 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds new fields to caf_user to store new data from FFCAM';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user ADD radiation_date DATE DEFAULT NULL COMMENT \'Date de radiation FFCAM(DC2Type:date_immutable)\', ADD radiation_reason VARCHAR(255) DEFAULT NULL COMMENT \'Motif de radiation FFCAM\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user DROP radiation_date, DROP radiation_reason');
    }
}
