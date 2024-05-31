<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240531164340 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user ADD is_deleted TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DEBE82686A22D67B ON caf_user (cafnum_user)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_DEBE82686A22D67B ON caf_user');
        $this->addSql('ALTER TABLE caf_user DROP is_deleted');
    }
}
