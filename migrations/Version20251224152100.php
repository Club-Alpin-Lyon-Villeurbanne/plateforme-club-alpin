<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251224152100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add allow_comments column to caf_article table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_article ADD allow_comments TINYINT(1) DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_article DROP allow_comments');
    }
}
