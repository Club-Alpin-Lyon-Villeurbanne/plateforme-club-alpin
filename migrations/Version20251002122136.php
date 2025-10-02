<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251002122136 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Removes no longer used fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user DROP date_adhesion_user');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user ADD date_adhesion_user BIGINT DEFAULT NULL');
    }
}
