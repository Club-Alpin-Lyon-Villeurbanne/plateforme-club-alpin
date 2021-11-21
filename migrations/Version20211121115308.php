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
        $this->addSql('ALTER TABLE caf_user CHANGE email_user email_user VARCHAR(200) DEFAULT NULL');
        $this->addSql('UPDATE caf_user SET email_user = NULL WHERE email_user = \'\'');
        $this->addSql('ALTER TABLE caf_user CHANGE mdp_user mdp_user VARCHAR(1024) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user CHANGE mdp_user mdp_user VARCHAR(32) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`');
        $this->addSql('ALTER TABLE caf_user CHANGE email_user email_user VARCHAR(200) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`');
    }
}
