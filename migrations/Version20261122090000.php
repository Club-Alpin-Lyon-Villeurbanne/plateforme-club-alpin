<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261122090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sorties : option pour ne pas envoyer l\'alerte « Nouvelle sortie » aux abonnés (groupe déjà constitué, cycle…)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE caf_evt ADD skip_new_sortie_alert TINYINT(1) DEFAULT 0 NOT NULL COMMENT 'Ne pas envoyer l''alerte « Nouvelle sortie » (mail et push) à la publication'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt DROP skip_new_sortie_alert');
    }
}
