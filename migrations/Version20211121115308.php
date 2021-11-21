<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211121115308 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user CHANGE mdp_user mdp_user VARCHAR(1024) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DEBE826812A5F6CC ON caf_user (email_user)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DEBE82686A22D67B ON caf_user (cafnum_user)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_DEBE826812A5F6CC ON caf_user');
        $this->addSql('DROP INDEX UNIQ_DEBE82686A22D67B ON caf_user');
        $this->addSql('ALTER TABLE caf_user CHANGE mdp_user mdp_user VARCHAR(32) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`');
    }
}
