<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230623153727 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ndf_demande (id INT AUTO_INCREMENT NOT NULL, sortie INT DEFAULT NULL, demandeur BIGINT DEFAULT NULL, remboursement TINYINT(1) DEFAULT 0 NOT NULL, statut VARCHAR(30) NOT NULL, statut_commentaire VARCHAR(100) NOT NULL COMMENT \'Commentaire du statut\', type_transport VARCHAR(20) DEFAULT NULL COMMENT \'Type de transport utilsé\', INDEX IDX_A9AEB9473C3FD3F2 (sortie), INDEX IDX_A9AEB947665DA613 (demandeur), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ndf_depense_autre (id INT AUTO_INCREMENT NOT NULL, ndf_demande_id INT NOT NULL, ordre INT NOT NULL, commentaire VARCHAR(100) DEFAULT NULL COMMENT \'Commentaire\', montant NUMERIC(10, 0) DEFAULT \'0\' NOT NULL, url_justif VARCHAR(100) DEFAULT NULL, INDEX IDX_A9E0121117FDC5C2 (ndf_demande_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ndf_depense_commun (id INT AUTO_INCREMENT NOT NULL, ndf_demande_id INT NOT NULL, ordre INT NOT NULL, commentaire VARCHAR(100) DEFAULT NULL COMMENT \'Commentaire\', montant NUMERIC(10, 0) DEFAULT \'0\' NOT NULL, url_justif VARCHAR(100) DEFAULT NULL, INDEX IDX_520A31317FDC5C2 (ndf_demande_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ndf_depense_hebergement (id INT AUTO_INCREMENT NOT NULL, ndf_demande_id INT NOT NULL, ordre INT NOT NULL, commentaire VARCHAR(100) DEFAULT NULL COMMENT \'Commentaire\', montant NUMERIC(10, 0) DEFAULT \'0\' NOT NULL, url_justif VARCHAR(100) DEFAULT NULL, INDEX IDX_5BE59F8A17FDC5C2 (ndf_demande_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ndf_depense_minibus_club (id INT AUTO_INCREMENT NOT NULL, ndf_demande_id INT NOT NULL, nbre_km INT NOT NULL, nbre_passager INT NOT NULL, cout_essence NUMERIC(10, 0) DEFAULT \'0\' NOT NULL, frais_peage NUMERIC(10, 0) DEFAULT \'0\' NOT NULL, url_justif_peage VARCHAR(100) DEFAULT NULL, INDEX IDX_AF599A4A17FDC5C2 (ndf_demande_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ndf_depense_minibus_loc (id INT AUTO_INCREMENT NOT NULL, ndf_demande_id INT NOT NULL, nbre_km INT NOT NULL, prix_loc_km NUMERIC(10, 0) DEFAULT \'0\' NOT NULL, url_justif_loc VARCHAR(100) DEFAULT NULL, nbre_passager INT NOT NULL, cout_essence NUMERIC(10, 0) DEFAULT \'0\' NOT NULL, frais_peage NUMERIC(10, 0) DEFAULT \'0\' NOT NULL, url_justif_peage VARCHAR(100) DEFAULT NULL, INDEX IDX_884ED74217FDC5C2 (ndf_demande_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ndf_depense_voiture (id INT AUTO_INCREMENT NOT NULL, ndf_demande_id INT NOT NULL, nbre_km INT NOT NULL, commentaire VARCHAR(100) DEFAULT NULL COMMENT \'Commentaire\', frais_peage NUMERIC(10, 0) DEFAULT \'0\' NOT NULL, url_justif_peage VARCHAR(100) DEFAULT NULL, INDEX IDX_60614BC617FDC5C2 (ndf_demande_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ndf_demande ADD CONSTRAINT FK_A9AEB9473C3FD3F2 FOREIGN KEY (sortie) REFERENCES caf_evt (id_evt)');
        $this->addSql('ALTER TABLE ndf_demande ADD CONSTRAINT FK_A9AEB947665DA613 FOREIGN KEY (demandeur) REFERENCES caf_user (id_user)');
        $this->addSql('ALTER TABLE ndf_depense_autre ADD CONSTRAINT FK_A9E0121117FDC5C2 FOREIGN KEY (ndf_demande_id) REFERENCES ndf_demande (id)');
        $this->addSql('ALTER TABLE ndf_depense_commun ADD CONSTRAINT FK_520A31317FDC5C2 FOREIGN KEY (ndf_demande_id) REFERENCES ndf_demande (id)');
        $this->addSql('ALTER TABLE ndf_depense_hebergement ADD CONSTRAINT FK_5BE59F8A17FDC5C2 FOREIGN KEY (ndf_demande_id) REFERENCES ndf_demande (id)');
        $this->addSql('ALTER TABLE ndf_depense_minibus_club ADD CONSTRAINT FK_AF599A4A17FDC5C2 FOREIGN KEY (ndf_demande_id) REFERENCES ndf_demande (id)');
        $this->addSql('ALTER TABLE ndf_depense_minibus_loc ADD CONSTRAINT FK_884ED74217FDC5C2 FOREIGN KEY (ndf_demande_id) REFERENCES ndf_demande (id)');
        $this->addSql('ALTER TABLE ndf_depense_voiture ADD CONSTRAINT FK_60614BC617FDC5C2 FOREIGN KEY (ndf_demande_id) REFERENCES ndf_demande (id)');
        $this->addSql('ALTER TABLE caf_evt ADD ndf_statut VARCHAR(30) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ndf_demande DROP FOREIGN KEY FK_A9AEB9473C3FD3F2');
        $this->addSql('ALTER TABLE ndf_demande DROP FOREIGN KEY FK_A9AEB947665DA613');
        $this->addSql('ALTER TABLE ndf_depense_autre DROP FOREIGN KEY FK_A9E0121117FDC5C2');
        $this->addSql('ALTER TABLE ndf_depense_commun DROP FOREIGN KEY FK_520A31317FDC5C2');
        $this->addSql('ALTER TABLE ndf_depense_hebergement DROP FOREIGN KEY FK_5BE59F8A17FDC5C2');
        $this->addSql('ALTER TABLE ndf_depense_minibus_club DROP FOREIGN KEY FK_AF599A4A17FDC5C2');
        $this->addSql('ALTER TABLE ndf_depense_minibus_loc DROP FOREIGN KEY FK_884ED74217FDC5C2');
        $this->addSql('ALTER TABLE ndf_depense_voiture DROP FOREIGN KEY FK_60614BC617FDC5C2');
        $this->addSql('DROP TABLE caf_api_user');
        $this->addSql('DROP TABLE ndf_demande');
        $this->addSql('DROP TABLE ndf_depense_autre');
        $this->addSql('DROP TABLE ndf_depense_commun');
        $this->addSql('DROP TABLE ndf_depense_hebergement');
        $this->addSql('DROP TABLE ndf_depense_minibus_club');
        $this->addSql('DROP TABLE ndf_depense_minibus_loc');
        $this->addSql('DROP TABLE ndf_depense_voiture');
        $this->addSql('ALTER TABLE caf_evt DROP ndf_statut');
    }
}
