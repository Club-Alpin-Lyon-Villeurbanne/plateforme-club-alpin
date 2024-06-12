<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240610142444 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // A place de "Nuitée (demi-pension) 1", il faut mettre "1ere nuitée"
        $this->addSql(
            "UPDATE `expense_type` SET `name` = 'nuitée' WHERE `slug` = 'nuitee';"
        );
        // a la place de "Prix du carburant", il faut mettre "dépense carburant"
        $this->addSql(
            "UPDATE `expense_field_type` SET `name` = 'Dépense carburant' WHERE `slug` = 'prix_carburant';"
        );

    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "UPDATE `expense_type` SET `name` = 'Nuitée (demi-pension)' WHERE `slug` = 'nuitee';"
        );
        $this->addSql(
            "UPDATE `expense_field_type` SET `name` = 'Prix du carburant' WHERE `slug` = 'prix_carburant';"
        );
    }
}
