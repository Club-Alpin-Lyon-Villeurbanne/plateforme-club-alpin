<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260128144118 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds new field for waiting list';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt ADD waiting_seat INT UNSIGNED DEFAULT NULL COMMENT \'Nombre de place en liste d\'\'attente\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt DROP waiting_seat');
    }
}
