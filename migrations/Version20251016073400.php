<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251016073400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Removes no longer used field';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user DROP auth_contact_user');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user ADD auth_contact_user VARCHAR(10) DEFAULT \'users\' NOT NULL COMMENT \'QUI peut me contacter via formulaire\'');
    }
}
