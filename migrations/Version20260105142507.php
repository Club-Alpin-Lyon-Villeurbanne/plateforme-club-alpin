<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260105142507 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Changes type to store more text';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_article CHANGE cont_article cont_article LONGTEXT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // pas de down car risque de perte de données si on réduit la longueur du champ
    }
}
