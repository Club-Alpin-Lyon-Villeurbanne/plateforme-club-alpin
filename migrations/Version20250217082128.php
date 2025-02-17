<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250217082128 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE caf_evt DROP FOREIGN KEY FK_197AA7EF427F4D1');
        $this->addSql('DROP INDEX IDX_197AA7EF427F4D1 ON caf_evt');
        $this->addSql('ALTER TABLE caf_evt DROP cycle_parent_evt, DROP cycle_master_evt, DROP child_version_from_evt, DROP child_version_tosubmit');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE caf_evt ADD cycle_parent_evt INT DEFAULT NULL, ADD cycle_master_evt TINYINT(1) NOT NULL COMMENT \'Est-ce la première sortie d\'\'un cycle de sorties liées ?\', ADD child_version_from_evt INT NOT NULL COMMENT \'Versionning : chaque modification d-evt crée une entrée "enfant" de l-originale. Ce champ prend l-ID de l-original\', ADD child_version_tosubmit TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE caf_evt ADD CONSTRAINT FK_197AA7EF427F4D1 FOREIGN KEY (cycle_parent_evt) REFERENCES caf_evt (id_evt) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_197AA7EF427F4D1 ON caf_evt (cycle_parent_evt)');
    }
}
