<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211229214030 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt CHANGE cycle_parent_evt cycle_parent_evt INT DEFAULT NULL');
        $this->addSql('UPDATE caf_evt SET cycle_parent_evt = null WHERE cycle_parent_evt NOT IN (SELECT id_evt FROM caf_evt)');
        $this->addSql('ALTER TABLE caf_evt ADD CONSTRAINT FK_197AA7EF427F4D1 FOREIGN KEY (cycle_parent_evt) REFERENCES caf_evt (id_evt)');
        $this->addSql('CREATE INDEX IDX_197AA7EF427F4D1 ON caf_evt (cycle_parent_evt)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt DROP FOREIGN KEY FK_197AA7EF427F4D1');
        $this->addSql('DROP INDEX IDX_197AA7EF427F4D1 ON caf_evt');
        $this->addSql('ALTER TABLE caf_evt CHANGE cycle_parent_evt cycle_parent_evt INT NOT NULL COMMENT \'Si cette sortie est l\'\'enfant d\'\'un cycle, l\'\'id du parent est ici\'');
    }
}
