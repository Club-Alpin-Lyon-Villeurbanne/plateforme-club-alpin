<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211226231353 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_article CHANGE evt_article evt_article INT DEFAULT NULL');
        $this->addSql('UPDATE caf_article SET evt_article = null WHERE evt_article = 0');
        $this->addSql('UPDATE caf_article SET evt_article = null WHERE evt_article NOT IN (SELECT id_evt FROM caf_evt)');
        $this->addSql('ALTER TABLE caf_article ADD CONSTRAINT FK_A0BDE6C7F4CDCE2 FOREIGN KEY (evt_article) REFERENCES caf_evt (id_evt)');
        $this->addSql('CREATE INDEX IDX_A0BDE6C7F4CDCE2 ON caf_article (evt_article)');

        $this->addSql('ALTER TABLE caf_comment CHANGE user_comment user_comment BIGINT NOT NULL');
        $this->addSql('DELETE FROM caf_comment WHERE user_comment NOT IN (SELECT id_user FROM caf_user)');
        $this->addSql('ALTER TABLE caf_comment ADD CONSTRAINT FK_36F3BACDCC794C66 FOREIGN KEY (user_comment) REFERENCES caf_user (id_user)');
        $this->addSql('CREATE INDEX IDX_36F3BACDCC794C66 ON caf_comment (user_comment)');

        $this->addSql('ALTER TABLE caf_evt_join CHANGE affiliant_user_join affiliant_user_join BIGINT DEFAULT NULL, CHANGE lastchange_who_evt_join lastchange_who_evt_join BIGINT NOT NULL');
        $this->addSql('UPDATE caf_evt_join SET affiliant_user_join = null WHERE affiliant_user_join = 0');
        $this->addSql('UPDATE caf_evt_join SET affiliant_user_join = null WHERE affiliant_user_join NOT IN (SELECT id_user FROM caf_user)');
        $this->addSql('ALTER TABLE caf_evt_join ADD CONSTRAINT FK_F03790373A719826 FOREIGN KEY (affiliant_user_join) REFERENCES caf_user (id_user)');
        $this->addSql('UPDATE caf_evt_join SET lastchange_who_evt_join = null WHERE lastchange_who_evt_join = 0');
        $this->addSql('UPDATE caf_evt_join SET lastchange_who_evt_join = null WHERE lastchange_who_evt_join NOT IN (SELECT id_user FROM caf_user)');
        $this->addSql('ALTER TABLE caf_evt_join ADD CONSTRAINT FK_F0379037DBB00F1F FOREIGN KEY (lastchange_who_evt_join) REFERENCES caf_user (id_user)');
        $this->addSql('CREATE INDEX IDX_F03790373A719826 ON caf_evt_join (affiliant_user_join)');
        $this->addSql('CREATE INDEX IDX_F0379037DBB00F1F ON caf_evt_join (lastchange_who_evt_join)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_article DROP FOREIGN KEY FK_A0BDE6C7F4CDCE2');
        $this->addSql('DROP INDEX IDX_A0BDE6C7F4CDCE2 ON caf_article');
        $this->addSql('ALTER TABLE caf_article CHANGE evt_article evt_article INT NOT NULL COMMENT \'ID sortie liée\'');

        $this->addSql('ALTER TABLE caf_comment DROP FOREIGN KEY FK_36F3BACDCC794C66');
        $this->addSql('DROP INDEX IDX_36F3BACDCC794C66 ON caf_comment');
        $this->addSql('ALTER TABLE caf_comment CHANGE user_comment user_comment INT NOT NULL');

        $this->addSql('ALTER TABLE caf_evt_join DROP FOREIGN KEY FK_F03790373A719826');
        $this->addSql('ALTER TABLE caf_evt_join DROP FOREIGN KEY FK_F0379037DBB00F1F');
        $this->addSql('DROP INDEX IDX_F03790373A719826 ON caf_evt_join');
        $this->addSql('DROP INDEX IDX_F0379037DBB00F1F ON caf_evt_join');
        $this->addSql('ALTER TABLE caf_evt_join CHANGE affiliant_user_join affiliant_user_join INT NOT NULL COMMENT \'Si non nulle, cette valeur cible l\'\'utilisateur qui a joint cet user via la fonction d\'\'affiliation. C\'\'est donc lui qui doit recevoir les emails informatifs.\', CHANGE lastchange_who_evt_join lastchange_who_evt_join INT NOT NULL COMMENT \'Qui a modifié cet élément\'');
    }
}
