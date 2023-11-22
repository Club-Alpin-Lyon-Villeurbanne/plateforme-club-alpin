<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231030093924 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'generates the tables to handle expenses reports';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "CREATE TABLE `expense` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `spent_amount` int(10) NOT NULL COMMENT 'en centimes !',
                `refund_amount` int(10) NOT NULL COMMENT 'en centimes !',
                `expense_report_id` int(14) NOT NULL,
                `expense_type_id` int(14) NOT NULL,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            )"
        );

        $this->addSql(
            "CREATE TABLE `expense_field` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `justification_document` varchar(255) NOT NULL,
                `expense_field_type_id` int(11) NOT NULL,
                `value` varchar(11) NOT NULL,
                `expense_id` int(11) NOT NULL,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            )"
        );

        $this->addSql(
            "CREATE TABLE `expense_type_expense_field_type` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `expense_field_type_id` int(14) NOT NULL,
                `expense_type_id` int(14) NOT NULL,
                `needs_justification` tinyint(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`)
            )"
        );

        $this->addSql(
            "CREATE TABLE `expense_field_type` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `slug` varchar(255) NOT NULL,
                `name` varchar(255) NOT NULL,
                PRIMARY KEY (`id`)
            )"
        );

        $this->addSql(
            "CREATE TABLE `expense_report` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `status` tinyint(1) NOT NULL DEFAULT 0,
                `refund_required` tinyint(1) NOT NULL DEFAULT 0,
                `user_id` int(14) NOT NULL,
                `event_id` int(14) NOT NULL,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            )"
        );

        $this->addSql(
            "CREATE TABLE `expense_type` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `slug` varchar(255) NOT NULL,
                `expense_group_id` int(11) NOT NULL,
                PRIMARY KEY (`id`)
            )"
        );
        
        $this->addSql(
            "CREATE TABLE `expense_group` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `slug` varchar(255) NOT NULL,
                `type` ENUM('unique', 'multiple', 'raw') NOT NULL DEFAULT 'raw',
                PRIMARY KEY (`id`)
            )"
        );  
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('expense_type_expense_field_type');
        $schema->dropTable('expense_field');
        $schema->dropTable('expense_type');
        $schema->dropTable('expense_field_type');
        $schema->dropTable('expense');
        $schema->dropTable('expense_report');
        $schema->dropTable('expense_group');
    }
}
