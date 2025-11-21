<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121091507 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds new mapping field';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_commission ADD code_ffcam_groupe_competence VARCHAR(2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_commission DROP code_ffcam_groupe_competence');
    }
}
