<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250527093739 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Updates alerts for "actualités du club" articles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE caf_user SET alerts = JSON_REPLACE(alerts, \'$.actuclub.Article\', \'false\') WHERE alerts IS NOT NULL ');
    }

    public function down(Schema $schema): void
    {
    }
}
