<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211229213757 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt_destination CHANGE id_evt id_evt INT NOT NULL');
        $this->addSql('ALTER TABLE caf_evt_destination ADD CONSTRAINT FK_8ADEBBA11F453D6 FOREIGN KEY (id_evt) REFERENCES caf_evt (id_evt)');
        $this->addSql('ALTER TABLE caf_evt_destination ADD CONSTRAINT FK_8ADEBBA126D4F35D FOREIGN KEY (id_destination) REFERENCES caf_destination (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8ADEBBA11F453D6 ON caf_evt_destination (id_evt)');
        $this->addSql('CREATE INDEX IDX_8ADEBBA126D4F35D ON caf_evt_destination (id_destination)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt_destination DROP FOREIGN KEY FK_8ADEBBA11F453D6');
        $this->addSql('ALTER TABLE caf_evt_destination DROP FOREIGN KEY FK_8ADEBBA126D4F35D');
        $this->addSql('DROP INDEX UNIQ_8ADEBBA11F453D6 ON caf_evt_destination');
        $this->addSql('DROP INDEX IDX_8ADEBBA126D4F35D ON caf_evt_destination');
        $this->addSql('ALTER TABLE caf_evt_destination CHANGE id_evt id_evt INT UNSIGNED NOT NULL');
    }
}
