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
            "INSERT INTO `expense_field_type` (`id`, `slug`, `name`) VALUES
            (1, 'distance', 'Distance (en kilomètres)'),
            (2, 'prix', 'Prix (en Euros)'),
            (3, 'description', 'Description'),
            (4, 'peage', 'Péage'),
            (5, 'nombre_voyageurs', 'Nombre de voyageurs'),
            (6, 'prix_loc_par_km', 'Prix de la location par Km'),
            (7, 'prix_carburant', 'Prix du carburant');"
        );

        $this->addSql(
            "INSERT INTO `expense_group` (`id`, `name`, `slug`, `type`) VALUES
            (1, 'Transport', 'transport', 'unique'),
            (2, 'Hébergement', 'hebergement', 'multiple'),
            (3, 'Autres dépenses', 'autres', 'multiple');"
        );

        $this->addSql(
            "INSERT INTO `expense_type` (`id`, `name`, `slug`, `expense_group_id`) VALUES
            (1, 'Minibus du club', 'minibus-club', 1),
            (2, 'Véhicule personnel', 'vehicule-personnel', 1),
            (3, 'Nuitée (demi-pension)', 'nuitee', 2),
            (4, 'Autre', 'autre-depense', 3),
            (5, 'Transport en commun', 'transport_commun', 1),
            (6, 'Minibus de location', 'minibus_location', 1);"
        );

        $this->addSql(
            "INSERT INTO `expense_type_expense_field_type` (`id`, `expense_field_type_id`, `expense_type_id`, `needs_justification`) VALUES
            (3, 1, 2, 0),
            (4, 2, 3, 1),
            (5, 2, 4, 1),
            (6, 7, 1, 1),
            (7, 4, 1, 1),
            (8, 1, 1, 0),
            (9, 5, 1, 0),
            (10, 7, 2, 1),
            (11, 3, 2, 0),
            (12, 1, 6, 0),
            (13, 4, 6, 1),
            (14, 5, 6, 0),
            (15, 6, 6, 1),
            (16, 7, 6, 1),
            (17, 2, 5, 1),
            (18, 3, 5, 0),
            (19, 3, 3, 0),
            (20, 3, 4, 0);"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql("TRUNCATE TABLE `expense_field_type`");
        $this->addSql("TRUNCATE TABLE `expense_group`");
        $this->addSql("TRUNCATE TABLE `expense_type`");
        $this->addSql("TRUNCATE TABLE `expense_type_expense_field_type`");
    }
}
