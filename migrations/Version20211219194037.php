<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211219194037 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE caf_user SET email_user = NULL WHERE email_user = \'\'');
    }

    public function down(Schema $schema): void
    {
    }
}
