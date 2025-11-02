<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251102131649 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée les tables pour les compétences (groupes de compétences) des adhérents';
    }

    public function up(Schema $schema): void
    {
        // Table formation_competence_referentiel - Référentiel des compétences
        $this->addSql(<<<SQL
            CREATE TABLE formation_competence_referentiel (
                id INT AUTO_INCREMENT NOT NULL,
                intitule VARCHAR(255) NOT NULL,
                code_activite VARCHAR(10) DEFAULT NULL,
                activite VARCHAR(100) DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY(id),
                UNIQUE INDEX UNIQ_COMP_REF_INTITULE_ACT (intitule, code_activite),
                INDEX IDX_COMP_REF_ACTIVITE (code_activite)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        // Table formation_competence_validation - Validations des compétences par adhérent
        $this->addSql(<<<SQL
            CREATE TABLE formation_competence_validation (
                id INT AUTO_INCREMENT NOT NULL,
                user_id BIGINT NOT NULL,
                competence_id INT NOT NULL,
                niveau_associe VARCHAR(255) DEFAULT NULL,
                date_validation DATETIME DEFAULT NULL,
                est_valide TINYINT(1) DEFAULT 0 NOT NULL,
                valide_par VARCHAR(255) DEFAULT NULL,
                commentaire LONGTEXT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY(id),
                UNIQUE INDEX UNIQ_COMP_VAL_USER_COMP (user_id, competence_id),
                INDEX IDX_FORM_COMP_VAL_USER (user_id),
                INDEX IDX_FORM_COMP_VAL_COMP (competence_id),
                INDEX IDX_FORM_COMP_VAL_DATE (date_validation),
                INDEX IDX_FORM_COMP_VAL_VALID (est_valide),
                CONSTRAINT FK_FORM_COMP_VAL_USER FOREIGN KEY (user_id) REFERENCES caf_user (id_user) ON DELETE CASCADE,
                CONSTRAINT FK_FORM_COMP_VAL_REF FOREIGN KEY (competence_id) REFERENCES formation_competence_referentiel (id) ON DELETE RESTRICT
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // Supprimer les tables dans l'ordre inverse (contraintes de clés étrangères)
        $this->addSql('DROP TABLE formation_competence_validation');
        $this->addSql('DROP TABLE formation_competence_referentiel');
    }
}
