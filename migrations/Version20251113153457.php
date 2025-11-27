<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251113153457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Removes field';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_user_admin_listing ON caf_user');
        $this->addSql('ALTER TABLE caf_user DROP valid_user');
        $this->addSql('CREATE INDEX idx_user_admin_listing ON caf_user (is_deleted, doit_renouveler_user, nomade_user, lastname_user)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_user_admin_listing ON caf_user');
        $this->addSql('ALTER TABLE caf_user ADD valid_user TINYINT(1) NOT NULL COMMENT \'0=l\'\'user n\'\'a pas activé son compte   1=activé\'');
        $this->addSql('CREATE INDEX idx_user_admin_listing ON caf_user (is_deleted, valid_user, doit_renouveler_user, nomade_user, lastname_user)');
    }
}
