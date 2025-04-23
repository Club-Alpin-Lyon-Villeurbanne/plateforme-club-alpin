<?php

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

return new class extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add details_caches_evt column to caf_evt table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt ADD details_caches_evt TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt DROP details_caches_evt');
    }
}; 