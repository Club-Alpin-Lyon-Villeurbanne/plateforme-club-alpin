<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211223231417 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt_join ADD CONSTRAINT FK_F0379037BB9CB54A FOREIGN KEY (evt_evt_join) REFERENCES caf_evt (id_evt) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_F0379037BB9CB54A ON caf_evt_join (evt_evt_join)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt_join DROP FOREIGN KEY FK_F0379037BB9CB54A');
        $this->addSql('DROP INDEX IDX_F0379037BB9CB54A ON caf_evt_join');
    }
}
