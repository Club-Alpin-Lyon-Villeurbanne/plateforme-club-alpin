<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240607130202 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change le type de prix de location pour le minibus du club';
    }

    public function up(Schema $schema): void
    {
        // Ajout du type de champ "Prix de la location"
        $this->addSql(
            "INSERT INTO `expense_field_type` (`id`, `slug`, `name`, `input_type`) VALUES (8, 'prix_location', 'Prix de la location', 'numeric');"
        );
        // Suppression des relations entre la location de minibus et les types de champ "Distance (id 1)"
        // et "Prix de la location par km (id 6)
        $this->addSql(
            'DELETE FROM `expense_type_expense_field_type`
            WHERE
                `expense_type_id` = 6
                AND (`expense_field_type_id` = 1 OR `expense_field_type_id` = 6);'
        );
        // Ajout de la relation entre la location de minibus et le type de champ "Prix par location (id 8)"
        $this->addSql(
            'INSERT INTO `expense_type_expense_field_type` (`expense_field_type_id`, `expense_type_id`, `needs_justification`, `is_used_for_total`, `is_mandatory`, `display_order`)
            VALUES (8, 6, 1, 1, 0, 0);'
        );
    }

    public function down(Schema $schema): void
    {
        // Suppression du type de champ "Prix de la location"
        $this->addSql(
            "DELETE FROM `expense_field_type` WHERE `id` = 8 AND `slug` = 'prix_location' AND `name` = 'Prix de la location';"
        );
        // Ajout des relations entre minibus de location et les champs "Distance (id 1)" et "Prix de la location en km (id 6)"
        $this->addSql(
            'INSERT INTO `expense_type_expense_field_type` (`expense_field_type_id`, `expense_type_id`, `needs_justification`, `is_used_for_total`, `is_mandatory`, `display_order`) VALUES
                (1, 6, 1, 1, 0, 0),
                (6, 6, 1, 0, 0, 0);'
        );
        // Suppression de la relation entre minibus de location et le champ "Prix de la location (id 8)"
        $this->addSql(
            'DELETE FROM `expense_type_expense_field_type`
            WHERE `expense_type_id` = 6 AND `expense_field_type_id` = 8;'
        );
    }
}
