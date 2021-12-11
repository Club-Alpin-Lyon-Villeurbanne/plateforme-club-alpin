<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211211133846 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO caf_usertype (hierarchie_usertype, code_usertype, title_usertype, limited_to_comm_usertype) VALUES (120, \'developpeur\', \'Développeur\', 0)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM caf_usertype WHERE code_usertype = \'developpeur\'');
    }
}
