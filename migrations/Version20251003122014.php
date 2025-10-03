<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251003122014 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds new field validation_date to caf_evt table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt ADD legal_status_change_date DATETIME DEFAULT NULL COMMENT \'date de validation ou refus légal(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt DROP legal_status_change_date');
    }
}
