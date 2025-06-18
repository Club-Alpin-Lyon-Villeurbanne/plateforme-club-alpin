<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250618133030 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE caf_article ADD lastedit_who BIGINT DEFAULT NULL COMMENT 'User de la dernière modif'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE caf_article ADD CONSTRAINT FK_A0BDE6C714420AF FOREIGN KEY (lastedit_who) REFERENCES caf_user (id_user)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_A0BDE6C714420AF ON caf_article (lastedit_who)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE caf_article DROP FOREIGN KEY FK_A0BDE6C714420AF
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_A0BDE6C714420AF ON caf_article
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE caf_article DROP lastedit_who
        SQL);
    }
}
