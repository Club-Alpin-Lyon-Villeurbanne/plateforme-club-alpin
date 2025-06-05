<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250511160816 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE media_upload (id INT AUTO_INCREMENT NOT NULL, uploaded_by_id BIGINT NOT NULL, filename VARCHAR(255) NOT NULL, original_filename VARCHAR(255) DEFAULT NULL, mime_type VARCHAR(50) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', used TINYINT(1) DEFAULT NULL, INDEX IDX_ABC47CC1A2B28FE8 (uploaded_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE media_upload ADD CONSTRAINT FK_ABC47CC1A2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES caf_user (id_user)');
        $this->addSql('ALTER TABLE caf_article ADD media_upload_id INT DEFAULT NULL, CHANGE cont_article cont_article LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE caf_article ADD CONSTRAINT FK_A0BDE6C7E9AF09BF FOREIGN KEY (media_upload_id) REFERENCES media_upload (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_A0BDE6C7E9AF09BF ON caf_article (media_upload_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE caf_article DROP FOREIGN KEY FK_A0BDE6C7E9AF09BF');
        $this->addSql('ALTER TABLE media_upload DROP FOREIGN KEY FK_ABC47CC1A2B28FE8');
        $this->addSql('DROP TABLE media_upload');
        $this->addSql('DROP INDEX IDX_A0BDE6C7E9AF09BF ON caf_article');
        $this->addSql('ALTER TABLE caf_article DROP media_upload_id, CHANGE cont_article cont_article MEDIUMTEXT NOT NULL');
    }
}
