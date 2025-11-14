<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251114075336 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Changes rights order';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE caf_usertype SET hierarchie_usertype = 30 WHERE code_usertype = \'redacteur\'');
        $this->addSql('UPDATE caf_usertype SET hierarchie_usertype = 40 WHERE code_usertype = \'benevole_encadrement\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE caf_usertype SET hierarchie_usertype = 40 WHERE code_usertype = \'redacteur\'');
        $this->addSql('UPDATE caf_usertype SET hierarchie_usertype = 30 WHERE code_usertype = \'benevole_encadrement\'');
    }
}
