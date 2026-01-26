<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260122093944 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cleans unwanted duplicated events with no date';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM caf_evt WHERE is_draft = 1 AND status_evt = 0 AND start_date IS NULL AND end_date IS NULL');
    }

    public function down(Schema $schema): void
    {
        // pas de retour arrière sur du nettoyage de bdd
    }
}
