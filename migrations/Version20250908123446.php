<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250908123446 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds new fields to store payment information';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt ADD has_payment_form TINYINT(1) DEFAULT 0 NOT NULL, ADD hello_asso_form_slug VARCHAR(100) DEFAULT NULL, ADD payment_url VARCHAR(255) DEFAULT NULL, ADD payment_amount DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE caf_evt ADD has_payment_send_mail TINYINT(1) DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt DROP has_payment_send_mail');
        $this->addSql('ALTER TABLE caf_evt DROP has_payment_form, DROP hello_asso_form_slug, DROP payment_url, DROP payment_amount');
    }
}
