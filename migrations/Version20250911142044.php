<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250911142044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds new right in rights matrix';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            INSERT INTO `caf_userright` (`code_userright`, `title_userright`, `ordre_userright`, `parent_userright`) VALUES ('commission_list', 'Visualiser la liste des commissions dont on est responsable', '495', 'COMMISSIONS');
            INSERT INTO `caf_userright` (`code_userright`, `title_userright`, `ordre_userright`, `parent_userright`) VALUES ('commission_config', 'Paramétrer une commission dont on est responsable', '505', 'COMMISSIONS');
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM caf_userright WHERE `code_userright` = \'commission_list\'');
        $this->addSql('DELETE FROM caf_userright WHERE `code_userright` = \'commission_config\'');
    }
}
