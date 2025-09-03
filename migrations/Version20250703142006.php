<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250703142006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates table for INSEE zip codes and cities';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt CHANGE place_evt place_evt VARCHAR(100) NOT NULL COMMENT \'Lieu de départ activité\', CHANGE rdv_evt rdv_evt VARCHAR(200) NOT NULL COMMENT \'Lieu de RDV covoiturage\'');
        $this->addSql('CREATE TABLE communes (id_commune_insee INT AUTO_INCREMENT NOT NULL, code_commune_insee VARCHAR(255) NOT NULL, nom_commune VARCHAR(255) NOT NULL, code_postal VARCHAR(255) NOT NULL, libelle_acheminement VARCHAR(255) NOT NULL, ligne5 VARCHAR(255) DEFAULT NULL, INDEX code_postal (code_postal), PRIMARY KEY(id_commune_insee)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE communes');
        $this->addSql('ALTER TABLE caf_evt CHANGE place_evt place_evt VARCHAR(100) NOT NULL COMMENT \'Lieu de RDV covoiturage\', CHANGE rdv_evt rdv_evt VARCHAR(200) NOT NULL COMMENT \'Lieu détaillé du rdv\'');
    }
}
