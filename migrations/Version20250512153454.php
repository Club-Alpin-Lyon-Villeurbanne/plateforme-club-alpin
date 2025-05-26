<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250512153454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt ADD event_start_date DATETIME DEFAULT NULL COMMENT \'date et heure du début du event\', ADD event_end_date DATETIME DEFAULT NULL COMMENT \'date et heure de fin du event\', ADD join_start_date DATETIME DEFAULT NULL COMMENT \'date du début des inscriptions\'');
        $this->addSql('UPDATE caf_evt SET event_start_date = FROM_UNIXTIME(tsp_evt) WHERE tsp_evt IS NOT NULL');
        $this->addSql('UPDATE caf_evt SET event_end_date = FROM_UNIXTIME(tsp_end_evt) WHERE tsp_end_evt IS NOT NULL');
        $this->addSql('UPDATE caf_evt SET join_start_date = FROM_UNIXTIME(join_start_evt) WHERE join_start_evt IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt DROP event_start_date, DROP event_end_date, DROP join_start_date');
    }
}
