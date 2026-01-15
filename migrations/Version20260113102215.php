<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260113102215 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds field to store profile type';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user ADD profile_type SMALLINT DEFAULT 0 NOT NULL');
        $this->addSql('UPDATE caf_user SET profile_type = 4 WHERE manuel_user = 1');
        $this->addSql('UPDATE caf_user SET profile_type = 3 WHERE nomade_user = 1');
        $this->addSql('UPDATE caf_user SET profile_type = 2 WHERE validity_duration IS NOT NULL');
        $this->addSql('UPDATE caf_user SET profile_type = 1 WHERE profile_type = 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user DROP profile_type');
    }
}
