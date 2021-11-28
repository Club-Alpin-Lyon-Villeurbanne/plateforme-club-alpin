<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211127134619 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_article CHANGE status_who_article status_who_article INT DEFAULT NULL COMMENT \'ID du membre qui change le statut\', CHANGE tsp_validate_article tsp_validate_article INT DEFAULT NULL, CHANGE commission_article commission_article INT DEFAULT NULL, CHANGE nb_vues_article nb_vues_article INT DEFAULT 0 NOT NULL');
        $this->addSql('UPDATE caf_article SET commission_article = NULL WHERE commission_article NOT IN (SELECT id_commission FROM caf_commission)');
        $this->addSql('ALTER TABLE caf_article ADD CONSTRAINT FK_A0BDE6C7ABEFE8B6 FOREIGN KEY (commission_article) REFERENCES caf_commission (id_commission)');
        $this->addSql('CREATE INDEX IDX_A0BDE6C7ABEFE8B6 ON caf_article (commission_article)');
        $this->addSql('ALTER TABLE caf_evt CHANGE user_evt user_evt BIGINT NOT NULL');
        $this->addSql('ALTER TABLE caf_evt ADD CONSTRAINT FK_197AA7E7446DA07 FOREIGN KEY (user_evt) REFERENCES caf_user (id_user)');
        $this->addSql('ALTER TABLE caf_evt ADD CONSTRAINT FK_197AA7ED1CB2CA1 FOREIGN KEY (commission_evt) REFERENCES caf_commission (id_commission)');
        $this->addSql('CREATE INDEX IDX_197AA7E7446DA07 ON caf_evt (user_evt)');
        $this->addSql('CREATE INDEX IDX_197AA7ED1CB2CA1 ON caf_evt (commission_evt)');
        $this->addSql('ALTER TABLE caf_token CHANGE id_token id_token VARCHAR(32) NOT NULL');
        $this->addSql('ALTER TABLE caf_user_attr CHANGE user_user_attr user_user_attr BIGINT NOT NULL, CHANGE usertype_user_attr usertype_user_attr INT DEFAULT NULL');
        $this->addSql('DELETE FROM caf_user_attr WHERE user_user_attr NOT IN (SELECT id_user FROM caf_user)');
        $this->addSql('ALTER TABLE caf_user_attr ADD CONSTRAINT FK_67322AB87E1FE239 FOREIGN KEY (user_user_attr) REFERENCES caf_user (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE caf_user_attr ADD CONSTRAINT FK_67322AB88BE7C3B3 FOREIGN KEY (usertype_user_attr) REFERENCES caf_usertype (id_usertype)');
        $this->addSql('CREATE INDEX IDX_67322AB87E1FE239 ON caf_user_attr (user_user_attr)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_article DROP FOREIGN KEY FK_A0BDE6C7ABEFE8B6');
        $this->addSql('DROP INDEX IDX_A0BDE6C7ABEFE8B6 ON caf_article');
        $this->addSql('ALTER TABLE caf_article CHANGE commission_article commission_article INT NOT NULL COMMENT \'ID Commission liée (facultativ)\', CHANGE status_who_article status_who_article INT NOT NULL COMMENT \'ID du membre qui change le statut\', CHANGE tsp_validate_article tsp_validate_article INT NOT NULL, CHANGE nb_vues_article nb_vues_article INT NOT NULL');
        $this->addSql('ALTER TABLE caf_evt DROP FOREIGN KEY FK_197AA7E7446DA07');
        $this->addSql('ALTER TABLE caf_evt DROP FOREIGN KEY FK_197AA7ED1CB2CA1');
        $this->addSql('DROP INDEX IDX_197AA7E7446DA07 ON caf_evt');
        $this->addSql('DROP INDEX IDX_197AA7ED1CB2CA1 ON caf_evt');
        $this->addSql('ALTER TABLE caf_evt CHANGE user_evt user_evt INT NOT NULL COMMENT \'id user createur\'');
        $this->addSql('ALTER TABLE caf_token CHANGE id_token id_token VARCHAR(32) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`');
        $this->addSql('ALTER TABLE caf_user_attr DROP FOREIGN KEY FK_67322AB87E1FE239');
        $this->addSql('ALTER TABLE caf_user_attr DROP FOREIGN KEY FK_67322AB88BE7C3B3');
        $this->addSql('DROP INDEX IDX_67322AB87E1FE239 ON caf_user_attr');
        $this->addSql('ALTER TABLE caf_user_attr CHANGE user_user_attr user_user_attr INT NOT NULL COMMENT \'ID user possédant le type \', CHANGE usertype_user_attr usertype_user_attr INT NOT NULL COMMENT \'ID du type (admin, modero etc...)\'');
    }
}
