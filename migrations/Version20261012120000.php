<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261012120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Notes de frais : colonne version (verrou optimiste), pour que deux écritures simultanées sur une note ne s\'écrasent plus';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expense_report ADD version INT DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expense_report DROP version');
    }
}
