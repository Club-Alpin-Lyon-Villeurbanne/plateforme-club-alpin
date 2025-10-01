<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250930154602 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Removes no longer used fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt_join DROP tsp_evt_join, DROP lastchange_when_evt_join');
        $this->addSql('ALTER TABLE caf_article DROP tsp_crea_article, DROP tsp_article');
    }

    public function down(Schema $schema): void
    {
    }
}
