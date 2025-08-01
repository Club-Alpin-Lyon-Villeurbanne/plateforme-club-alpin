<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250730091603 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt ADD has_hello_asso_form TINYINT(1) NOT NULL, ADD hello_asso_form_slug VARCHAR(100) DEFAULT NULL, ADD hello_asso_form_url VARCHAR(255) DEFAULT NULL, ADD hello_asso_form_amount DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt DROP has_hello_asso_form, DROP hello_asso_form_slug, DROP hello_asso_form_url, DROP hello_asso_form_amount');
    }
}
