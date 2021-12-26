<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211226223303 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt_join CHANGE user_evt_join user_evt_join BIGINT NOT NULL');
        $this->addSql('DELETE FROM caf_evt_join WHERE user_evt_join NOT IN (SELECT id_user FROM caf_user)');
        $this->addSql('ALTER TABLE caf_evt_join ADD CONSTRAINT FK_F0379037B1C960A1 FOREIGN KEY (user_evt_join) REFERENCES caf_user (id_user) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_F0379037B1C960A1 ON caf_evt_join (user_evt_join)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt_join DROP FOREIGN KEY FK_F0379037B1C960A1');
        $this->addSql('DROP INDEX IDX_F0379037B1C960A1 ON caf_evt_join');
        $this->addSql('ALTER TABLE caf_evt_join CHANGE user_evt_join user_evt_join INT NOT NULL');
    }
}
