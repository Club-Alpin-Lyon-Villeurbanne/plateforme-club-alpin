<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240126142533 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('TRUNCATE TABLE expense_type_expense_field_type;');
        $this->addSql('
        INSERT INTO `expense_type_expense_field_type` (`expense_field_type_id`, `expense_type_id`, `needs_justification`, `is_used_for_total`, `is_mandatory`, `display_order`) VALUES
            (1, 2, 0, 0, 0, 0),
            (2, 3, 1, 1, 1, 0),
            (2, 4, 1, 1, 0, 0),
            (7, 1, 1, 1, 0, 0),
            (4, 1, 1, 1, 0, 0),
            (1, 1, 0, 0, 0, 0),
            (5, 1, 0, 0, 0, 0),
            (4, 2, 1, 1, 0, 0),
            (3, 2, 0, 0, 0, 0),
            (1, 6, 0, 0, 0, 0),
            (4, 6, 1, 1, 0, 0),
            (5, 6, 0, 0, 0, 0),
            (6, 6, 1, 0, 0, 0),
            (7, 6, 1, 1, 0, 0),
            (2, 5, 1, 1, 0, 0),
            (3, 5, 0, 0, 0, 0),
            (3, 3, 0, 0, 0, 0),
            (3, 4, 0, 0, 0, 0),
            (5, 2, 0, 0, 1, 0);
        ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
