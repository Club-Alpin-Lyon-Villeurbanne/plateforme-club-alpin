<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240420182620 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        if (!\in_array($_ENV['APP_ENV'], ['dev', 'test'], true)) {
            $this->addSql('DROP TABLE caf_ftp_allowedext');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        if (!\in_array($_ENV['APP_ENV'], ['dev', 'test'], true)) {
            $this->addSql('CREATE TABLE caf_ftp_allowedext (id_ftp_allowedext INT AUTO_INCREMENT NOT NULL, ext_ftp_allowedext VARCHAR(6) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, PRIMARY KEY(id_ftp_allowedext)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        }
    }
}
