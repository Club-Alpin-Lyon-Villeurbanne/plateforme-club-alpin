<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261011150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Notes de frais : message de la validation ou du renvoi dans l\'historique des statuts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expense_report_status_history ADD message LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expense_report_status_history DROP message');
    }
}
