<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251024080331 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Removes no longer used table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user_niveau DROP FOREIGN KEY FK_30B1DB666B3CA4B');
        $this->addSql('DROP TABLE caf_user_niveau');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE caf_user_niveau (id INT UNSIGNED AUTO_INCREMENT NOT NULL, id_user BIGINT NOT NULL, id_commission INT UNSIGNED NOT NULL, niveau_technique SMALLINT UNSIGNED DEFAULT NULL, niveau_physique SMALLINT UNSIGNED DEFAULT NULL, commentaire TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_30B1DB666B3CA4B (id_user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE caf_user_niveau ADD CONSTRAINT FK_30B1DB666B3CA4B FOREIGN KEY (id_user) REFERENCES caf_user (id_user) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
