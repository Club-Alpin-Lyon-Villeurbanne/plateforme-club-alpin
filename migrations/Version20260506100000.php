<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute pending_email_changes sur caf_evt pour mémoriser le diff des champs critiques à la dépublication';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt ADD pending_email_changes JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt DROP COLUMN pending_email_changes');
    }
}
