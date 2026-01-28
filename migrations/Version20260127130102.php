<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260127130102 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Lengthen up short text field';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user_attr CHANGE description_user_attr description_user_attr VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user_attr CHANGE description_user_attr description_user_attr VARCHAR(100) DEFAULT NULL');
    }
}
