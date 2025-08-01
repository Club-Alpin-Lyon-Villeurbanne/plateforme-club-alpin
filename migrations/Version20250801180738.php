<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250801180738 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création des tables pour la gestion des compétences FFCAM';
    }

    public function up(Schema $schema): void
    {
        // 1. Table caf_validation_competence
        $this->addSql('CREATE TABLE caf_validation_competence (
            id INT AUTO_INCREMENT NOT NULL,
            user_id BIGINT NOT NULL,
            cafnum_user VARCHAR(20) NOT NULL,
            code_competence VARCHAR(15) NOT NULL,
            date_validation DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            source_formation VARCHAR(50) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_CAF_VAL_COMP_USER (user_id),
            INDEX idx_cafnum_competence (cafnum_user),
            INDEX idx_code_competence (code_competence),
            INDEX idx_date_validation_comp (date_validation),
            UNIQUE KEY unique_user_competence (user_id, code_competence),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 2. Table caf_formation_competence
        $this->addSql('CREATE TABLE caf_formation_competence (
            id INT AUTO_INCREMENT NOT NULL,
            code_formation VARCHAR(50) NOT NULL,
            code_competence VARCHAR(15) NOT NULL,
            INDEX idx_formation_comp (code_formation),
            INDEX idx_competence_form (code_competence),
            UNIQUE KEY unique_formation_competence (code_formation, code_competence),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 3. Table caf_niveau_pratique_referentiel
        $this->addSql('CREATE TABLE caf_niveau_pratique_referentiel (
            id INT AUTO_INCREMENT NOT NULL,
            cursus_niveau_id INT NOT NULL,
            code_activite VARCHAR(10) NOT NULL,
            activite VARCHAR(100) NOT NULL,
            niveau VARCHAR(255) NOT NULL,
            libelle VARCHAR(255) NOT NULL,
            niveau_court VARCHAR(50) DEFAULT NULL,
            discipline VARCHAR(100) DEFAULT NULL,
            INDEX idx_cursus_niveau (cursus_niveau_id),
            INDEX idx_code_activite_ref (code_activite),
            UNIQUE KEY unique_cursus_niveau (cursus_niveau_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 4. Table caf_niveau_competence
        $this->addSql('CREATE TABLE caf_niveau_competence (
            id INT AUTO_INCREMENT NOT NULL,
            cursus_niveau_id INT NOT NULL,
            code_competence VARCHAR(15) NOT NULL,
            INDEX idx_cursus_niveau_comp (cursus_niveau_id),
            INDEX idx_competence_niveau (code_competence),
            UNIQUE KEY unique_niveau_competence (cursus_niveau_id, code_competence),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 5. Table caf_theme (optionnelle)
        $this->addSql('CREATE TABLE caf_theme (
            id INT AUTO_INCREMENT NOT NULL,
            code_theme VARCHAR(20) NOT NULL,
            libelle VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            ordre INT DEFAULT 0,
            actif BOOLEAN DEFAULT TRUE,
            UNIQUE KEY unique_code_theme (code_theme),
            INDEX idx_ordre_theme (ordre),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Ajout des clés étrangères
        $this->addSql('ALTER TABLE caf_validation_competence ADD CONSTRAINT FK_CAF_VAL_COMP_USER FOREIGN KEY (user_id) REFERENCES caf_user (id_user) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Suppression des clés étrangères
        $this->addSql('ALTER TABLE caf_validation_competence DROP FOREIGN KEY FK_CAF_VAL_COMP_USER');

        // Suppression des tables
        $this->addSql('DROP TABLE caf_validation_competence');
        $this->addSql('DROP TABLE caf_formation_competence');
        $this->addSql('DROP TABLE caf_niveau_pratique_referentiel');
        $this->addSql('DROP TABLE caf_niveau_competence');
        $this->addSql('DROP TABLE caf_theme');
    }
}