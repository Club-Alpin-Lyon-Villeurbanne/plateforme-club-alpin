<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220114210455 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt 
                CHANGE status_evt status_evt SMALLINT DEFAULT 0 NOT NULL COMMENT \'0-unseen 1-ok 2-refused\', 
                CHANGE status_legal_evt status_legal_evt SMALLINT DEFAULT 0 NOT NULL COMMENT \'0-unseen 1-ok 2-refused\', 
                CHANGE massif_evt massif_evt VARCHAR(100) DEFAULT NULL, 
                CHANGE matos_evt matos_evt TEXT DEFAULT NULL, 
                CHANGE difficulte_evt difficulte_evt VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt CHANGE status_evt status_evt SMALLINT NOT NULL COMMENT \'0-unseen 1-ok 2-refused\', CHANGE status_legal_evt status_legal_evt SMALLINT NOT NULL COMMENT \'0-unseen 1-ok 2-refused\', CHANGE massif_evt massif_evt VARCHAR(100) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, CHANGE matos_evt matos_evt TEXT CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, CHANGE difficulte_evt difficulte_evt VARCHAR(50) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`');
    }
}
