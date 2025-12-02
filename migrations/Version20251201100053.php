<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251201100053 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Makes code_formation unique';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formation_referentiel_formation DROP INDEX code_formation, ADD UNIQUE INDEX UNIQ_CODE_FORMATION (code_formation)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formation_referentiel_formation DROP INDEX UNIQ_CODE_FORMATION, ADD INDEX code_formation (code_formation)');
    }
}
