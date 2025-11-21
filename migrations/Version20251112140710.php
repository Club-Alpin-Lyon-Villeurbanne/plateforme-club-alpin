<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251112140710 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée la table de mapping entre les activités FFCAM (génériques) et les commissions du club (spécifiques)';
    }

    public function up(Schema $schema): void
    {
        // Table formation_activite_commission - Mapping activités FFCAM ↔ commissions du club
        $this->addSql(<<<SQL
            CREATE TABLE formation_activite_commission (
                id INT AUTO_INCREMENT NOT NULL,
                code_activite VARCHAR(10) NOT NULL COMMENT 'Code activité FFCAM (ex: SN pour SPORTS DE NEIGE)',
                commission_id INT NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY(id),
                UNIQUE INDEX UNIQ_FORM_ACT_COMM (code_activite, commission_id),
                INDEX IDX_FORM_ACT_COMM_ACTIVITE (code_activite),
                INDEX IDX_FORM_ACT_COMM_COMMISSION (commission_id),
                CONSTRAINT FK_FORM_ACT_COMM_COMMISSION FOREIGN KEY (commission_id) REFERENCES caf_commission (id_commission) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            COMMENT 'Lie les activités FFCAM génériques (ex: SPORTS DE NEIGE) aux commissions spécifiques du club (ex: ski de rando, snowboard, raquettes)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        // Supprimer la table
        $this->addSql('DROP TABLE formation_activite_commission');
    }
}
