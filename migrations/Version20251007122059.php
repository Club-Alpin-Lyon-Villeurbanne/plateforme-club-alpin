<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251007122059 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renames role';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE `caf_usertype` SET `code_usertype` = \'benevole_encadrement\', `title_usertype` = \'Bénévole \'\'encadrement\' WHERE `code_usertype` = \'benevole\';');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE `caf_usertype` SET `code_usertype` = \'benevole\', `title_usertype` = \'Bénévole\' WHERE `code_usertype` = \'benevole_encadrement\';');
    }
}
