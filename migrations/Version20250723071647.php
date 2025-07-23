<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250723071647 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE caf_formation_validee (id INT AUTO_INCREMENT NOT NULL, user_id BIGINT NOT NULL, cafnum_user VARCHAR(20) NOT NULL, nom_complet VARCHAR(255) NOT NULL, code_formation VARCHAR(50) NOT NULL, intitule_formation LONGTEXT NOT NULL, date_validation DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', numero_formation VARCHAR(50) DEFAULT NULL, formateur VARCHAR(255) DEFAULT NULL, id_interne VARCHAR(20) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_AA5DE47FA76ED395 (user_id), INDEX idx_cafnum (cafnum_user), INDEX idx_code_formation (code_formation), INDEX idx_date_validation (date_validation), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE caf_last_sync (type VARCHAR(50) NOT NULL, last_sync DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', records_count INT DEFAULT 0 NOT NULL, PRIMARY KEY(type)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE caf_niveau_pratique (id INT AUTO_INCREMENT NOT NULL, user_id BIGINT NOT NULL, cafnum_user VARCHAR(20) NOT NULL, nom_complet VARCHAR(255) NOT NULL, club VARCHAR(20) DEFAULT NULL, code_activite VARCHAR(10) NOT NULL, activite VARCHAR(100) NOT NULL, niveau VARCHAR(255) NOT NULL, date_validation DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', validation_par VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_E70E90C6A76ED395 (user_id), INDEX idx_cafnum (cafnum_user), INDEX idx_code_activite (code_activite), INDEX idx_date_validation (date_validation), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE caf_formation_validee ADD CONSTRAINT FK_AA5DE47FA76ED395 FOREIGN KEY (user_id) REFERENCES caf_user (id_user)');
        $this->addSql('ALTER TABLE caf_niveau_pratique ADD CONSTRAINT FK_E70E90C6A76ED395 FOREIGN KEY (user_id) REFERENCES caf_user (id_user)');

        $this->addSql('INSERT INTO caf_last_sync (type) VALUES (\'formations\'), (\'niveaux_pratique\');');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_formation_validee DROP FOREIGN KEY FK_AA5DE47FA76ED395');
        $this->addSql('ALTER TABLE caf_niveau_pratique DROP FOREIGN KEY FK_E70E90C6A76ED395');
        $this->addSql('DROP TABLE caf_formation_validee');
        $this->addSql('DROP TABLE caf_last_sync');
        $this->addSql('DROP TABLE caf_niveau_pratique');
    }
}
