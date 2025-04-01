<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250401065312 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify to your needs
        $this->addSql('ALTER TABLE expense_report CHANGE status status ENUM(\'submitted\', \'approved\', \'rejected\', \'draft\', \'accounted\') NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify to your needs
        $this->addSql('ALTER TABLE expense_report CHANGE status status ENUM(\'submitted\', \'approved\', \'rejected\', \'draft\') NOT NULL');
    }
}
