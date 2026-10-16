<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261012120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Notes de frais : colonne version (verrou optimiste), pour que deux écritures simultanées sur une note ne s\'écrasent plus';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expense_report ADD version INT DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Premier down() joué au retour arrière : s'arrêter ici, avant tout changement, plutôt qu'à
        // Version20261011120000 avec une base à mi-chemin.
        $this->abortIf(
            (int) $this->connection->fetchOne("SELECT COUNT(*) FROM expense_report WHERE type <> 'sortie'") > 0,
            'Des notes de frais hors sortie (formation, achat) existent : les supprimer avant de revenir en arrière.',
        );
        $this->addSql('ALTER TABLE expense_report DROP version');
    }
}
