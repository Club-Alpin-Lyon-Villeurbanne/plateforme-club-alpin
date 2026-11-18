<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261118120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Journal admin : ajoute l\'utilisateur à l\'origine de l\'action';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_log_admin ADD user_log_admin BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE caf_log_admin ADD CONSTRAINT FK_B1BDA34EA8906BCF FOREIGN KEY (user_log_admin) REFERENCES caf_user (id_user) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_B1BDA34EA8906BCF ON caf_log_admin (user_log_admin)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_log_admin DROP FOREIGN KEY FK_B1BDA34EA8906BCF');
        $this->addSql('DROP INDEX IDX_B1BDA34EA8906BCF ON caf_log_admin');
        $this->addSql('ALTER TABLE caf_log_admin DROP user_log_admin');
    }
}
