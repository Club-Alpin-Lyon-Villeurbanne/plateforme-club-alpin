<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250610124238 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_DEBE82686A22D67B ON caf_user (cafnum_user)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE caf_user_attr CHANGE description_user_attr description_user_attr VARCHAR(100) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_DEBE82686A22D67B ON caf_user
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE caf_user_attr CHANGE description_user_attr description_user_attr VARCHAR(200) DEFAULT NULL
        SQL);
    }
}
