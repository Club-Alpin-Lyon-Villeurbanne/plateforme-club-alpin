<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251126092002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'cafnum_user should not be nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user CHANGE cafnum_user cafnum_user VARCHAR(20) NOT NULL COMMENT \'Numéro de licence\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user CHANGE cafnum_user cafnum_user VARCHAR(20) DEFAULT NULL COMMENT \'Numéro de licence\'');
    }
}
