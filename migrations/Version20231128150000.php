<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231128150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add fields to expense_type_expense_field_type';
    }

    public function up(Schema $schema): void
    {
        // add "display_order" "is_mandatory" and "is_used_for_total" column
        // on relation between expense type and field type
        $this->addSql(
            'ALTER TABLE `expense_type_expense_field_type`
            ADD COLUMN `display_order` INT(2) NOT NULL DEFAULT 0 AFTER `needs_justification`,
            ADD COLUMN `is_mandatory` TINYINT NOT NULL DEFAULT 0 AFTER `needs_justification`,
            ADD COLUMN `is_used_for_total` TINYINT NOT NULL DEFAULT 0 AFTER `needs_justification`;'
        );

        // add "input_type" column on field type
        $this->addSql(
            "ALTER TABLE `expense_field_type`
            ADD COLUMN `input_type` ENUM('string', 'text', 'numeric') NOT NULL DEFAULT 'string' AFTER `name`;"
        );

        // update all in expense_field_type to be a numeric input
        $this->addSql(
            "UPDATE `expense_field_type`
            SET `input_type` = 'numeric';"
        );

        // update expense_field_type with id 3 (description) to be a text input
        $this->addSql(
            "UPDATE `expense_field_type`
            SET `input_type` = 'text'
            WHERE `id` = 3;"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `expense_type_expense_field_type` DROP COLUMN `display_order`');
        $this->addSql('ALTER TABLE `expense_type_expense_field_type` DROP COLUMN `is_mandatory`');
        $this->addSql('ALTER TABLE `expense_type_expense_field_type` DROP COLUMN `is_used_for_total`');
        $this->addSql('ALTER TABLE `expense_field_type` DROP COLUMN `input_type`');
    }
}
