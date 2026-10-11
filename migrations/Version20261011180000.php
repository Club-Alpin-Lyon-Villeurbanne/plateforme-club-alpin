<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261011180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Notes de frais : statut « preapproved », validée par le responsable et chez la trésorerie';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expense_report CHANGE status status ENUM(\'submitted\', \'approved\', \'rejected\', \'draft\', \'accounted\', \'preapproved\') NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expense_report CHANGE status status ENUM(\'submitted\', \'approved\', \'rejected\', \'draft\', \'accounted\') NOT NULL');
    }
}
