<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230504095730 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE caf_chron_operation CHANGE code_chron_operation code_chron_operation VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE caf_evt CHANGE tarif_detail tarif_detail TEXT DEFAULT NULL, CHANGE itineraire itineraire TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE caf_partenaires CHANGE part_name part_name VARCHAR(50) NOT NULL, CHANGE part_url part_url VARCHAR(256) NOT NULL, CHANGE part_desc part_desc VARCHAR(500) NOT NULL, CHANGE part_image part_image VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE caf_usertype CHANGE hierarchie_usertype hierarchie_usertype INT NOT NULL COMMENT \'Ordre d\'\'apparition des types\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE caf_evt CHANGE tarif_detail tarif_detail TEXT DEFAULT NULL COLLATE `utf8_general_ci`, CHANGE itineraire itineraire TEXT DEFAULT NULL COLLATE `utf8_general_ci`');
        $this->addSql('ALTER TABLE caf_usertype CHANGE hierarchie_usertype hierarchie_usertype TINYINT(1) NOT NULL COMMENT \'Ordre d\'\'apparition des types\'');
        $this->addSql('ALTER TABLE caf_partenaires CHANGE part_name part_name VARCHAR(50) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, CHANGE part_url part_url VARCHAR(256) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, CHANGE part_desc part_desc VARCHAR(500) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, CHANGE part_image part_image VARCHAR(100) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`');
        $this->addSql('ALTER TABLE caf_chron_operation CHANGE code_chron_operation code_chron_operation VARCHAR(100) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`');
    }
}
