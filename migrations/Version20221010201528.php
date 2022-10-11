<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221010201528 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO `caf_usertype` (`hierarchie_usertype`, `code_usertype`, `title_usertype`, `limited_to_comm_usertype`) VALUES (55, \'stagiaire\', \'Initiateur Stagiaire\', 1)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM caf_usertype WHERE code_usertype = \'stagiaire\'');
    }
}
