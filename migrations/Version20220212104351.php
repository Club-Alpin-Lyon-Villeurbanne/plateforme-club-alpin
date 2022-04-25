<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220212104351 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UNIQ_21A459CA34CBFCBE ON caf_commission (code_commission)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_21A459CA34CBFCBE ON caf_commission');
    }
}
