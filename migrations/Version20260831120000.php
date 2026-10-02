<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260831120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Adherents : memorise la saison du dernier circuit d'accueil MailerLite envoye";
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE caf_user ADD accueil_season SMALLINT DEFAULT 0 NOT NULL COMMENT 'Saison du dernier envoi de circuit d''accueil MailerLite (0 = jamais)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user DROP accueil_season');
    }
}
