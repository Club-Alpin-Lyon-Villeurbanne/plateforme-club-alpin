<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261119120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Mes alertes : choix de recevoir un e-mail pour chaque demande d\'inscription aux sorties que l\'on organise ou encadre, ou seulement pour celles accompagnées d\'un message';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user ADD alert_toutes_demandes_inscription TINYINT(1) DEFAULT 1 NOT NULL COMMENT \'Si vaut 0 : l\'\'organisateur ou l\'\'encadrant d\'\'une sortie ne reçoit un e-mail que pour les demandes d\'\'inscription accompagnées d\'\'un message\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user DROP alert_toutes_demandes_inscription');
    }
}
