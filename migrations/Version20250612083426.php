<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add composite index to optimize admin members display query.
 */
final class Version20250612083426 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add composite index to optimize slow admin members display query';
    }

    public function up(Schema $schema): void
    {
        // Single composite index that covers all query patterns in admin-users.php
        // Column order matches the query WHERE clause order for optimal performance
        $this->addSql('CREATE INDEX idx_user_admin_listing ON caf_user (is_deleted, valid_user, doit_renouveler_user, nomade_user, lastname_user)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_user_admin_listing ON caf_user');
    }
}
