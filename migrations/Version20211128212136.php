<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Crée la table de sessions utilisée par PdoSessionHandler.
 *
 * La migration passait à l'origine par le conteneur
 * (`$this->container->get(PdoSessionHandler::class)->createTable()`) via
 * ContainerAwareInterface, supprimée en Symfony 7. Le CREATE TABLE est désormais
 * écrit ici, selon le schéma MySQL de la documentation Symfony (avec l'index sur
 * sess_lifetime). Les migrations suivantes font évoluer sess_data.
 */
final class Version20211128212136 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée la table `sessions` pour PdoSessionHandler';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS sessions (
            sess_id VARBINARY(128) NOT NULL PRIMARY KEY,
            sess_data BLOB NOT NULL,
            sess_lifetime INTEGER UNSIGNED NOT NULL,
            sess_time INTEGER UNSIGNED NOT NULL,
            INDEX sess_lifetime_idx (sess_lifetime)
        ) COLLATE utf8mb4_bin, ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // Volontairement vide, comme avant : annuler cette migration ne doit pas
        // supprimer les sessions en cours.
    }
}
