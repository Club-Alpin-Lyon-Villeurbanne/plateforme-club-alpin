<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211229215657 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt_join CHANGE lastchange_when_evt_join lastchange_when_evt_join BIGINT DEFAULT NULL COMMENT \'Quand a été modifié cet élément\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt_join CHANGE lastchange_when_evt_join lastchange_when_evt_join BIGINT NOT NULL COMMENT \'Quand a été modifié cet élément\'');
    }
}
