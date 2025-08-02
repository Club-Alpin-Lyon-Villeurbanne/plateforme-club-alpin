<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250801180738 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création des tables pour la gestion des formations et compétences FFCAM';
    }

    public function up(Schema $schema): void
    {
        // 1. Table formation_competence_theme
        $this->addSql('CREATE TABLE formation_competence_theme (
            id INT AUTO_INCREMENT NOT NULL,
            code_theme VARCHAR(10) NOT NULL,
            nom VARCHAR(255) NOT NULL,
            UNIQUE KEY UNIQ_FORM_THEME_CODE (code_theme),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 2. Table formation_competence_referentiel (anciennement caf_competence)
        $this->addSql('CREATE TABLE formation_competence_referentiel (
            id INT AUTO_INCREMENT NOT NULL,
            code_competence VARCHAR(15) NOT NULL,
            intitule VARCHAR(255) NOT NULL,
            niveau VARCHAR(100) NOT NULL,
            theme_id INT DEFAULT NULL,
            UNIQUE KEY UNIQ_FORM_COMP_CODE (code_competence),
            INDEX IDX_FORM_COMP_THEME (theme_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 3. Table formation_referentiel
        $this->addSql('CREATE TABLE formation_referentiel (
            code_formation VARCHAR(50) NOT NULL,
            intitule VARCHAR(255) NOT NULL,
            PRIMARY KEY(code_formation)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 4. Table formation_validation (anciennement caf_formation_validee)
        $this->addSql('CREATE TABLE formation_validation (
            id INT AUTO_INCREMENT NOT NULL,
            user_id BIGINT NOT NULL,
            code_formation VARCHAR(50) DEFAULT NULL,
            cafnum_user VARCHAR(20) NOT NULL,
            lieu_formation VARCHAR(255) NOT NULL,
            date_debut_formation DATE NOT NULL,
            date_fin_formation DATE NOT NULL,
            valide TINYINT(1) NOT NULL,
            date_validation DATE DEFAULT NULL,
            numero_formation VARCHAR(50) DEFAULT NULL,
            formateur VARCHAR(255) DEFAULT NULL,
            id_interne VARCHAR(20) DEFAULT NULL,
            intitule_formation VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_FORM_VAL_USER (user_id),
            INDEX IDX_FORM_VAL_CODE (code_formation),
            INDEX IDX_FORM_VAL_CAFNUM (cafnum_user),
            INDEX IDX_FORM_VAL_DATE (date_validation),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 5. Table formation_competence_validation (anciennement caf_validation_competence)
        $this->addSql('CREATE TABLE formation_competence_validation (
            id INT AUTO_INCREMENT NOT NULL,
            user_id BIGINT NOT NULL,
            cafnum_user VARCHAR(20) NOT NULL,
            code_competence VARCHAR(15) NOT NULL,
            date_validation DATETIME DEFAULT NULL,
            source_formation VARCHAR(50) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_FORM_COMP_VAL_USER (user_id),
            INDEX IDX_FORM_COMP_VAL_CAFNUM (cafnum_user),
            INDEX IDX_FORM_COMP_VAL_CODE (code_competence),
            INDEX IDX_FORM_COMP_VAL_DATE (date_validation),
            UNIQUE KEY UNIQ_FORM_USER_COMP (user_id, code_competence),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 6. Table formation_competence (anciennement caf_formation_competence)
        $this->addSql('CREATE TABLE formation_competence (
            id INT AUTO_INCREMENT NOT NULL,
            code_formation VARCHAR(50) NOT NULL,
            code_competence VARCHAR(15) NOT NULL,
            INDEX IDX_FORM_COMP_FORMATION (code_formation),
            INDEX IDX_FORM_COMP_COMPETENCE (code_competence),
            UNIQUE KEY UNIQ_FORM_FORMATION_COMP (code_formation, code_competence),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 7. Table formation_niveau_referentiel (anciennement caf_niveau_pratique_referentiel)
        $this->addSql('CREATE TABLE formation_niveau_referentiel (
            id INT AUTO_INCREMENT NOT NULL,
            cursus_niveau_id INT NOT NULL,
            code_activite VARCHAR(10) NOT NULL,
            activite VARCHAR(100) NOT NULL,
            niveau VARCHAR(255) NOT NULL,
            libelle VARCHAR(255) NOT NULL,
            niveau_court VARCHAR(50) DEFAULT NULL,
            discipline VARCHAR(100) DEFAULT NULL,
            INDEX IDX_FORM_NIV_REF_CURSUS (cursus_niveau_id),
            INDEX IDX_FORM_NIV_REF_ACTIVITE (code_activite),
            UNIQUE KEY UNIQ_FORM_CURSUS_NIV (cursus_niveau_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 8. Table formation_niveau_competence (anciennement caf_niveau_competence)
        $this->addSql('CREATE TABLE formation_niveau_competence (
            id INT AUTO_INCREMENT NOT NULL,
            cursus_niveau_id INT NOT NULL,
            code_competence VARCHAR(15) NOT NULL,
            INDEX IDX_FORM_NIV_COMP_CURSUS (cursus_niveau_id),
            INDEX IDX_FORM_NIV_COMP_CODE (code_competence),
            UNIQUE KEY UNIQ_FORM_NIV_COMP (cursus_niveau_id, code_competence),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 9. Table formation_niveau_validation (anciennement caf_niveau_pratique)
        $this->addSql('CREATE TABLE formation_niveau_validation (
            id INT AUTO_INCREMENT NOT NULL,
            user_id BIGINT NOT NULL,
            cafnum_user VARCHAR(20) NOT NULL,
            cursus_niveau_id INT NOT NULL,
            date_validation DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_FORM_NIV_VAL_USER (user_id),
            INDEX IDX_FORM_NIV_VAL_CAFNUM (cafnum_user),
            INDEX IDX_FORM_NIV_VAL_CURSUS (cursus_niveau_id),
            INDEX IDX_FORM_NIV_VAL_DATE (date_validation),
            UNIQUE KEY UNIQ_FORM_USER_NIV (user_id, cursus_niveau_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Ajout des clés étrangères
        
        // formation_competence_referentiel
        $this->addSql('ALTER TABLE formation_competence_referentiel 
            ADD CONSTRAINT FK_FORM_COMP_REF_THEME 
            FOREIGN KEY (theme_id) REFERENCES formation_competence_theme (id) 
            ON DELETE SET NULL');

        // formation_validation
        $this->addSql('ALTER TABLE formation_validation 
            ADD CONSTRAINT FK_FORM_VAL_USER 
            FOREIGN KEY (user_id) REFERENCES caf_user (id_user) 
            ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formation_validation 
            ADD CONSTRAINT FK_FORM_VAL_REF 
            FOREIGN KEY (code_formation) REFERENCES formation_referentiel (code_formation) 
            ON DELETE SET NULL');

        // formation_competence_validation
        $this->addSql('ALTER TABLE formation_competence_validation 
            ADD CONSTRAINT FK_FORM_COMP_VAL_USER 
            FOREIGN KEY (user_id) REFERENCES caf_user (id_user) 
            ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formation_competence_validation 
            ADD CONSTRAINT FK_FORM_COMP_VAL_REF 
            FOREIGN KEY (code_competence) REFERENCES formation_competence_referentiel (code_competence) 
            ON DELETE RESTRICT');

        // formation_competence
        $this->addSql('ALTER TABLE formation_competence 
            ADD CONSTRAINT FK_FORM_COMP_FORMATION_REF 
            FOREIGN KEY (code_formation) REFERENCES formation_referentiel (code_formation) 
            ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formation_competence 
            ADD CONSTRAINT FK_FORM_COMP_COMPETENCE_REF 
            FOREIGN KEY (code_competence) REFERENCES formation_competence_referentiel (code_competence) 
            ON DELETE CASCADE');

        // formation_niveau_competence
        $this->addSql('ALTER TABLE formation_niveau_competence 
            ADD CONSTRAINT FK_FORM_NIV_COMP_CURSUS 
            FOREIGN KEY (cursus_niveau_id) REFERENCES formation_niveau_referentiel (id) 
            ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formation_niveau_competence 
            ADD CONSTRAINT FK_FORM_NIV_COMP_REF 
            FOREIGN KEY (code_competence) REFERENCES formation_competence_referentiel (code_competence) 
            ON DELETE CASCADE');

        // formation_niveau_validation
        $this->addSql('ALTER TABLE formation_niveau_validation 
            ADD CONSTRAINT FK_FORM_NIV_VAL_USER 
            FOREIGN KEY (user_id) REFERENCES caf_user (id_user) 
            ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formation_niveau_validation 
            ADD CONSTRAINT FK_FORM_NIV_VAL_REF 
            FOREIGN KEY (cursus_niveau_id) REFERENCES formation_niveau_referentiel (id) 
            ON DELETE RESTRICT');

        // Suppression des anciennes tables créées par la migration Version20250723071647
        $this->addSql('DROP TABLE IF EXISTS caf_formation_validee');
        $this->addSql('DROP TABLE IF EXISTS caf_niveau_pratique');
        $this->addSql('DROP TABLE IF EXISTS caf_last_sync');

        // Création de la nouvelle table de suivi de synchronisation
        $this->addSql('CREATE TABLE formation_last_sync (
            type VARCHAR(50) NOT NULL,
            last_sync DATETIME DEFAULT NULL,
            records_count INT DEFAULT 0 NOT NULL,
            PRIMARY KEY(type)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Insertion des valeurs initiales
        $this->addSql("INSERT INTO formation_last_sync (type) VALUES ('formations'), ('niveaux_pratique')");
    }

    public function down(Schema $schema): void
    {
        // Suppression des clés étrangères
        $this->addSql('ALTER TABLE formation_competence_referentiel DROP FOREIGN KEY FK_FORM_COMP_REF_THEME');
        $this->addSql('ALTER TABLE formation_validation DROP FOREIGN KEY FK_FORM_VAL_USER');
        $this->addSql('ALTER TABLE formation_validation DROP FOREIGN KEY FK_FORM_VAL_REF');
        $this->addSql('ALTER TABLE formation_competence_validation DROP FOREIGN KEY FK_FORM_COMP_VAL_USER');
        $this->addSql('ALTER TABLE formation_competence_validation DROP FOREIGN KEY FK_FORM_COMP_VAL_REF');
        $this->addSql('ALTER TABLE formation_competence DROP FOREIGN KEY FK_FORM_COMP_FORMATION_REF');
        $this->addSql('ALTER TABLE formation_competence DROP FOREIGN KEY FK_FORM_COMP_COMPETENCE_REF');
        $this->addSql('ALTER TABLE formation_niveau_competence DROP FOREIGN KEY FK_FORM_NIV_COMP_CURSUS');
        $this->addSql('ALTER TABLE formation_niveau_competence DROP FOREIGN KEY FK_FORM_NIV_COMP_REF');
        $this->addSql('ALTER TABLE formation_niveau_validation DROP FOREIGN KEY FK_FORM_NIV_VAL_USER');
        $this->addSql('ALTER TABLE formation_niveau_validation DROP FOREIGN KEY FK_FORM_NIV_VAL_REF');

        // Suppression des tables dans l'ordre inverse
        $this->addSql('DROP TABLE formation_last_sync');
        $this->addSql('DROP TABLE formation_niveau_validation');
        $this->addSql('DROP TABLE formation_niveau_competence');
        $this->addSql('DROP TABLE formation_niveau_referentiel');
        $this->addSql('DROP TABLE formation_competence');
        $this->addSql('DROP TABLE formation_competence_validation');
        $this->addSql('DROP TABLE formation_validation');
        $this->addSql('DROP TABLE formation_referentiel');
        $this->addSql('DROP TABLE formation_competence_referentiel');
        $this->addSql('DROP TABLE formation_competence_theme');
    }
}
