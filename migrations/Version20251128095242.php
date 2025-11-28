<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251128095242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds table to map FFCAM entities with commissions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formation_referentiel ADD id INT AUTO_INCREMENT NOT NULL UNIQUE FIRST');
        $this->addSql('CREATE TABLE formation_commission (commission_id INT NOT NULL, formation_id INT NOT NULL, INDEX IDX_E4CDD261202D1EB2 (commission_id), INDEX IDX_E4CDD2615200282E (formation_id), PRIMARY KEY(commission_id, formation_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE groupe_competence_commission (commission_id INT NOT NULL, groupe_competence_id INT NOT NULL, INDEX IDX_7EFB817B202D1EB2 (commission_id), INDEX IDX_7EFB817B89034830 (groupe_competence_id), PRIMARY KEY(commission_id, groupe_competence_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE niveau_commission (commission_id INT NOT NULL, niveau_id INT NOT NULL, INDEX IDX_48E98795202D1EB2 (commission_id), INDEX IDX_48E98795B3E9C81 (niveau_id), PRIMARY KEY(commission_id, niveau_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE formation_commission ADD CONSTRAINT FK_E4CDD261202D1EB2 FOREIGN KEY (commission_id) REFERENCES caf_commission (id_commission) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formation_commission ADD CONSTRAINT FK_E4CDD2615200282E FOREIGN KEY (formation_id) REFERENCES formation_referentiel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE groupe_competence_commission ADD CONSTRAINT FK_7EFB817B202D1EB2 FOREIGN KEY (commission_id) REFERENCES caf_commission (id_commission) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE groupe_competence_commission ADD CONSTRAINT FK_7EFB817B89034830 FOREIGN KEY (groupe_competence_id) REFERENCES formation_competence_referentiel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE niveau_commission ADD CONSTRAINT FK_48E98795202D1EB2 FOREIGN KEY (commission_id) REFERENCES caf_commission (id_commission) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE niveau_commission ADD CONSTRAINT FK_48E98795B3E9C81 FOREIGN KEY (niveau_id) REFERENCES formation_niveau_referentiel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE caf_commission DROP code_ffcam_brevet, DROP code_ffcam_niveau, DROP code_ffcam_formation, DROP code_ffcam_groupe_competence');
        $this->addSql('ALTER TABLE formation_referentiel ADD INDEX(code_formation), DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formation_commission DROP FOREIGN KEY FK_E4CDD261202D1EB2');
        $this->addSql('ALTER TABLE formation_commission DROP FOREIGN KEY FK_E4CDD2615200282E');
        $this->addSql('ALTER TABLE groupe_competence_commission DROP FOREIGN KEY FK_7EFB817B202D1EB2');
        $this->addSql('ALTER TABLE groupe_competence_commission DROP FOREIGN KEY FK_7EFB817B89034830');
        $this->addSql('ALTER TABLE niveau_commission DROP FOREIGN KEY FK_48E98795202D1EB2');
        $this->addSql('ALTER TABLE niveau_commission DROP FOREIGN KEY FK_48E98795B3E9C81');
        $this->addSql('DROP TABLE formation_commission');
        $this->addSql('DROP TABLE groupe_competence_commission');
        $this->addSql('DROP TABLE niveau_commission');
        $this->addSql('ALTER TABLE caf_commission ADD code_ffcam_brevet VARCHAR(5) DEFAULT NULL, ADD code_ffcam_niveau VARCHAR(2) DEFAULT NULL, ADD code_ffcam_formation VARCHAR(2) DEFAULT NULL, ADD code_ffcam_groupe_competence VARCHAR(2) DEFAULT NULL');
        $this->addSql('ALTER TABLE formation_referentiel MODIFY id INT NOT NULL');
        $this->addSql('DROP INDEX `PRIMARY` ON formation_referentiel');
        $this->addSql('ALTER TABLE formation_referentiel DROP id');
        $this->addSql('ALTER TABLE formation_referentiel ADD PRIMARY KEY (code_formation)');
    }
}
