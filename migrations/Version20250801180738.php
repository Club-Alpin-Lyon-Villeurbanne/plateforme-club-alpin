<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250801180738 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création des tables pour la gestion des formations et niveaux de pratique FFCAM';
    }

    public function up(Schema $schema): void
    {
        // 1. Table formation_referentiel
        $this->addSql('CREATE TABLE formation_referentiel (
            code_formation VARCHAR(50) NOT NULL,
            intitule VARCHAR(255) NOT NULL,
            PRIMARY KEY(code_formation)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 2. Table formation_validation (anciennement caf_formation_validee)
        $this->addSql('CREATE TABLE formation_validation (
            id INT AUTO_INCREMENT NOT NULL,
            user_id BIGINT NOT NULL,
            code_formation VARCHAR(50) DEFAULT NULL,
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
            INDEX IDX_FORM_VAL_DATE (date_validation),
            INDEX IDX_FORM_VAL_DATES (date_debut_formation, date_fin_formation),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 3. Table formation_niveau_referentiel (anciennement caf_niveau_pratique_referentiel)
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

        // 4. Table formation_niveau_validation (anciennement caf_niveau_pratique)
        $this->addSql('CREATE TABLE formation_niveau_validation (
            id INT AUTO_INCREMENT NOT NULL,
            user_id BIGINT NOT NULL,
            cursus_niveau_id INT NOT NULL,
            date_validation DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_FORM_NIV_VAL_USER (user_id),
            INDEX IDX_FORM_NIV_VAL_CURSUS (cursus_niveau_id),
            INDEX IDX_FORM_NIV_VAL_DATE (date_validation),
            UNIQUE KEY UNIQ_FORM_USER_NIV (user_id, cursus_niveau_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Ajout des clés étrangères
        // formation_validation
        $this->addSql('ALTER TABLE formation_validation
            ADD CONSTRAINT FK_FORM_VAL_USER
            FOREIGN KEY (user_id) REFERENCES caf_user (id_user)
            ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formation_validation
            ADD CONSTRAINT FK_FORM_VAL_REF
            FOREIGN KEY (code_formation) REFERENCES formation_referentiel (code_formation)
            ON DELETE SET NULL');

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
        $this->addSql("INSERT INTO formation_last_sync (type) VALUES ('formations'), ('niveaux_pratique'), ('competences')");
    }

    public function down(Schema $schema): void
    {
        // Suppression des clés étrangères
        $this->addSql('ALTER TABLE formation_validation DROP FOREIGN KEY FK_FORM_VAL_USER');
        $this->addSql('ALTER TABLE formation_validation DROP FOREIGN KEY FK_FORM_VAL_REF');
        $this->addSql('ALTER TABLE formation_niveau_validation DROP FOREIGN KEY FK_FORM_NIV_VAL_USER');
        $this->addSql('ALTER TABLE formation_niveau_validation DROP FOREIGN KEY FK_FORM_NIV_VAL_REF');

        // Suppression des tables dans l'ordre inverse
        $this->addSql('DROP TABLE formation_last_sync');
        $this->addSql('DROP TABLE formation_niveau_validation');
        $this->addSql('DROP TABLE formation_niveau_referentiel');
        $this->addSql('DROP TABLE formation_validation');
        $this->addSql('DROP TABLE formation_referentiel');
    }
}
