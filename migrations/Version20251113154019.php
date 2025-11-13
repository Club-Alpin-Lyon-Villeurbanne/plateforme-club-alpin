<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251113154019 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Removes rights from rights matrix';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM caf_userright WHERE `code_userright` = \'user_reactivate\'');
        $this->addSql('DELETE FROM caf_userright WHERE `code_userright` = \'user_reset\'');
    }

    public function down(Schema $schema): void
    {
    }
}
