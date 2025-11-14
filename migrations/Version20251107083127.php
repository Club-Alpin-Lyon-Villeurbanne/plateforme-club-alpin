<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251107083127 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds fields to store codes to match brevets & co';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_commission ADD code_ffcam_brevet VARCHAR(5) DEFAULT NULL, ADD code_ffcam_niveau VARCHAR(2) DEFAULT NULL, ADD code_ffcam_formation VARCHAR(2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_commission DROP code_ffcam_brevet, DROP code_ffcam_niveau, DROP code_ffcam_formation');
    }
}
