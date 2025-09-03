<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250801075502 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            INSERT INTO `caf_userright` (`code_userright`, `title_userright`, `ordre_userright`, `parent_userright`) VALUES ('ha_mire_autorisation', 'Mire d''autorisation Hello Asso', '10', 'HELLO ASSO ');
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM caf_userright WHERE `code_userright` = \'ha_mire_autorisation\'');
    }
}
