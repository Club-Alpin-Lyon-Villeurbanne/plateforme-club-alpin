<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250729124224 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE config (id INT AUTO_INCREMENT NOT NULL, config_code VARCHAR(50) NOT NULL, config_value LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE caf_evt ADD has_hello_asso_form TINYINT(1) DEFAULT 0 NOT NULL, ADD hello_asso_form_slug VARCHAR(100) DEFAULT NULL, ADD hello_asso_form_url VARCHAR(255) DEFAULT NULL, ADD hello_asso_form_amount DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt DROP has_hello_asso_form, DROP hello_asso_form_slug, DROP hello_asso_form_url, DROP hello_asso_form_amount');
        $this->addSql('DROP TABLE config');
    }
}
