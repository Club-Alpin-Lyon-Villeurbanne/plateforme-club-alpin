<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240314085443 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE caf_content_inline CHANGE contenu_content_inline contenu_content_inline TEXT NOT NULL');
        $this->addSql('ALTER TABLE caf_groupe CHANGE description description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE caf_lieu CHANGE description description TEXT DEFAULT NULL, CHANGE ign ign TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE caf_user_niveau CHANGE commentaire commentaire TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE caf_content_inline CHANGE contenu_content_inline contenu_content_inline MEDIUMTEXT NOT NULL');
        $this->addSql('ALTER TABLE caf_groupe CHANGE description description MEDIUMTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE caf_lieu CHANGE description description MEDIUMTEXT DEFAULT NULL, CHANGE ign ign MEDIUMTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE caf_user_niveau CHANGE commentaire commentaire MEDIUMTEXT DEFAULT NULL');
    }
}
