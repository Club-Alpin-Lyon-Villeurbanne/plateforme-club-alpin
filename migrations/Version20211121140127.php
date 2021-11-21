<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211121140127 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user CHANGE email_user email_user VARCHAR(200) NOT NULL, CHANGE mdp_user mdp_user VARCHAR(1024) DEFAULT NULL, CHANGE cafnum_user cafnum_user VARCHAR(20) DEFAULT NULL COMMENT \'Numéro de licence\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DEBE826812A5F6CC ON caf_user (email_user)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_DEBE826812A5F6CC ON caf_user');
        $this->addSql('ALTER TABLE caf_user CHANGE email_user email_user VARCHAR(200) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, CHANGE mdp_user mdp_user VARCHAR(1024) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, CHANGE cafnum_user cafnum_user VARCHAR(20) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci` COMMENT \'Numéro de licence\'');
    }
}
