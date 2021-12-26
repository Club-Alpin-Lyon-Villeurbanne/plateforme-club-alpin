<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211226225119 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_article CHANGE status_who_article status_who_article BIGINT DEFAULT NULL, CHANGE user_article user_article BIGINT NOT NULL');
        $this->addSql('UPDATE caf_article SET status_who_article = null WHERE status_who_article = 0');
        $this->addSql('ALTER TABLE caf_article ADD CONSTRAINT FK_A0BDE6C7EAFEB9EE FOREIGN KEY (status_who_article) REFERENCES caf_user (id_user)');
        $this->addSql('ALTER TABLE caf_article ADD CONSTRAINT FK_A0BDE6C75A37106C FOREIGN KEY (user_article) REFERENCES caf_user (id_user)');
        $this->addSql('CREATE INDEX IDX_A0BDE6C7EAFEB9EE ON caf_article (status_who_article)');
        $this->addSql('CREATE INDEX IDX_A0BDE6C75A37106C ON caf_article (user_article)');

        $this->addSql('ALTER TABLE caf_destination CHANGE id_user_who_create id_user_who_create BIGINT NOT NULL, CHANGE id_user_responsable id_user_responsable BIGINT NOT NULL, CHANGE id_user_adjoint id_user_adjoint BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE caf_destination ADD CONSTRAINT FK_20FA0D9ABCDFBA5E FOREIGN KEY (id_user_who_create) REFERENCES caf_user (id_user)');
        $this->addSql('ALTER TABLE caf_destination ADD CONSTRAINT FK_20FA0D9A18C39143 FOREIGN KEY (id_user_responsable) REFERENCES caf_user (id_user)');
        $this->addSql('UPDATE caf_destination SET id_user_adjoint = null WHERE id_user_adjoint = 0');
        $this->addSql('ALTER TABLE caf_destination ADD CONSTRAINT FK_20FA0D9A3D33501 FOREIGN KEY (id_user_adjoint) REFERENCES caf_user (id_user)');
        $this->addSql('CREATE INDEX IDX_20FA0D9ABCDFBA5E ON caf_destination (id_user_who_create)');
        $this->addSql('CREATE INDEX IDX_20FA0D9A18C39143 ON caf_destination (id_user_responsable)');
        $this->addSql('CREATE INDEX IDX_20FA0D9A3D33501 ON caf_destination (id_user_adjoint)');

        $this->addSql('ALTER TABLE caf_evt CHANGE status_legal_who_evt status_legal_who_evt BIGINT DEFAULT NULL');
        $this->addSql('UPDATE caf_evt SET status_legal_who_evt = null WHERE status_legal_who_evt = 0');
        $this->addSql('ALTER TABLE caf_evt ADD CONSTRAINT FK_197AA7E52EAEEE1 FOREIGN KEY (status_legal_who_evt) REFERENCES caf_user (id_user)');
        $this->addSql('CREATE INDEX IDX_197AA7E52EAEEE1 ON caf_evt (status_legal_who_evt)');

        $this->addSql('ALTER TABLE caf_user_niveau CHANGE id_user id_user BIGINT NOT NULL');
        $this->addSql('ALTER TABLE caf_user_niveau ADD CONSTRAINT FK_30B1DB666B3CA4B FOREIGN KEY (id_user) REFERENCES caf_user (id_user)');
        $this->addSql('CREATE INDEX IDX_30B1DB666B3CA4B ON caf_user_niveau (id_user)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_article DROP FOREIGN KEY FK_A0BDE6C7EAFEB9EE');
        $this->addSql('ALTER TABLE caf_article DROP FOREIGN KEY FK_A0BDE6C75A37106C');
        $this->addSql('DROP INDEX IDX_A0BDE6C7EAFEB9EE ON caf_article');
        $this->addSql('DROP INDEX IDX_A0BDE6C75A37106C ON caf_article');
        $this->addSql('ALTER TABLE caf_article CHANGE status_who_article status_who_article INT DEFAULT NULL COMMENT \'ID du membre qui change le statut\', CHANGE user_article user_article INT NOT NULL COMMENT \'ID du créateur\'');

        $this->addSql('ALTER TABLE caf_destination DROP FOREIGN KEY FK_20FA0D9ABCDFBA5E');
        $this->addSql('ALTER TABLE caf_destination DROP FOREIGN KEY FK_20FA0D9A18C39143');
        $this->addSql('ALTER TABLE caf_destination DROP FOREIGN KEY FK_20FA0D9A3D33501');
        $this->addSql('DROP INDEX IDX_20FA0D9ABCDFBA5E ON caf_destination');
        $this->addSql('DROP INDEX IDX_20FA0D9A18C39143 ON caf_destination');
        $this->addSql('DROP INDEX IDX_20FA0D9A3D33501 ON caf_destination');
        $this->addSql('ALTER TABLE caf_destination CHANGE id_user_who_create id_user_who_create INT UNSIGNED NOT NULL, CHANGE id_user_responsable id_user_responsable INT UNSIGNED NOT NULL, CHANGE id_user_adjoint id_user_adjoint INT UNSIGNED DEFAULT NULL');

        $this->addSql('ALTER TABLE caf_evt DROP FOREIGN KEY FK_197AA7E52EAEEE1');
        $this->addSql('DROP INDEX IDX_197AA7E52EAEEE1 ON caf_evt');
        $this->addSql('ALTER TABLE caf_evt CHANGE status_legal_who_evt status_legal_who_evt INT DEFAULT NULL COMMENT \'ID du validateur légal\'');

        $this->addSql('ALTER TABLE caf_user_niveau DROP FOREIGN KEY FK_30B1DB666B3CA4B');
        $this->addSql('DROP INDEX IDX_30B1DB666B3CA4B ON caf_user_niveau');
        $this->addSql('ALTER TABLE caf_user_niveau CHANGE id_user id_user INT UNSIGNED NOT NULL');
    }
}
