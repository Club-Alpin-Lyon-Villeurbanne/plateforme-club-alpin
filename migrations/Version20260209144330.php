<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260209144330 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Updates profil types for exisiting users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE caf_user SET nomade_user = 0 WHERE profile_type = 2');
        $this->addSql('UPDATE caf_user SET profile_type = 3, validity_duration = NULL, discovery_end_datetime = NULL WHERE is_deleted = 0 AND profile_type = 2 AND cafnum_user NOT LIKE \'d%\';');
    }

    public function down(Schema $schema): void
    {
        // pas de down car pas de raison de revenir en arrière
    }
}
