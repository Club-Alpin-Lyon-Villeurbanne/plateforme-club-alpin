<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251127095926 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds field to store profile image in database';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user ADD media_upload_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE caf_user ADD CONSTRAINT FK_DEBE8268E9AF09BF FOREIGN KEY (media_upload_id) REFERENCES media_upload (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_DEBE8268E9AF09BF ON caf_user (media_upload_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user DROP FOREIGN KEY FK_DEBE8268E9AF09BF');
        $this->addSql('DROP INDEX IDX_DEBE8268E9AF09BF ON caf_user');
        $this->addSql('ALTER TABLE caf_user DROP media_upload_id');
    }
}
