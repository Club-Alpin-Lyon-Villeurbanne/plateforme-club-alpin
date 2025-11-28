<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251126125646 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds secondary (google drive) email field';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user ADD gdrive_email VARCHAR(200) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DEBE8268AAB70E16 ON caf_user (gdrive_email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_DEBE8268AAB70E16 ON caf_user');
        $this->addSql('ALTER TABLE caf_user DROP gdrive_email');
    }
}
