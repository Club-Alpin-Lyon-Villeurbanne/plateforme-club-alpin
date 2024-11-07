<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241025100018 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE caf_user_notification (id INT AUTO_INCREMENT NOT NULL, user_id BIGINT NOT NULL, type VARCHAR(10) NOT NULL, entity_id VARCHAR(64) NOT NULL, signature VARCHAR(200) NOT NULL, UNIQUE INDEX UNIQ_71260650AE880141 (signature), INDEX IDX_71260650A76ED395 (user_id), INDEX user_notif_signature (signature), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE caf_user_notification ADD CONSTRAINT FK_71260650A76ED395 FOREIGN KEY (user_id) REFERENCES caf_user (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE caf_user ADD alerts JSON DEFAULT NULL, ADD alert_sortie_prefix VARCHAR(255) DEFAULT \'[CAF-Lyon-Sortie]\' NOT NULL, ADD alert_article_prefix VARCHAR(255) DEFAULT \'[CAF-Lyon-Article]\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user_notification DROP FOREIGN KEY FK_71260650A76ED395');
        $this->addSql('DROP TABLE caf_user_notification');
        $this->addSql('ALTER TABLE caf_user DROP alerts, DROP alert_sortie_prefix, DROP alert_article_prefix');
    }
}
