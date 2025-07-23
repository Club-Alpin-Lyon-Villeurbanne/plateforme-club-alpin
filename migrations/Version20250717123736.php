<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250717123736 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user CHANGE tel_user tel_user VARCHAR(100) DEFAULT NULL, CHANGE tel2_user tel2_user VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user CHANGE tel_user tel_user VARCHAR(30) DEFAULT NULL, CHANGE tel2_user tel2_user VARCHAR(30) DEFAULT NULL');
    }
}
