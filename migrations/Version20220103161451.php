<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220103161451 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt CHANGE status_who_evt status_who_evt BIGINT DEFAULT NULL, CHANGE cancelled_who_evt cancelled_who_evt BIGINT DEFAULT NULL');
        $this->addSql('UPDATE caf_evt SET status_who_evt = null WHERE status_who_evt NOT IN (SELECT id_user FROM caf_user)');
        $this->addSql('ALTER TABLE caf_evt ADD CONSTRAINT FK_197AA7EB0C9569F FOREIGN KEY (status_who_evt) REFERENCES caf_user (id_user)');
        $this->addSql('UPDATE caf_evt SET cancelled_who_evt = null WHERE cancelled_who_evt NOT IN (SELECT id_user FROM caf_user)');
        $this->addSql('ALTER TABLE caf_evt ADD CONSTRAINT FK_197AA7EDA305AEC FOREIGN KEY (cancelled_who_evt) REFERENCES caf_user (id_user)');
        $this->addSql('CREATE INDEX IDX_197AA7EB0C9569F ON caf_evt (status_who_evt)');
        $this->addSql('CREATE INDEX IDX_197AA7EDA305AEC ON caf_evt (cancelled_who_evt)');
        $this->addSql('ALTER TABLE caf_user
                        CHANGE cafnum_parent_user cafnum_parent_user VARCHAR(20) DEFAULT NULL COMMENT \'Filiation : numéro CAF du parent\',
                        CHANGE tel_user tel_user VARCHAR(30) DEFAULT NULL, CHANGE tel2_user tel2_user VARCHAR(30) DEFAULT NULL,
                        CHANGE adresse_user adresse_user VARCHAR(100) DEFAULT NULL, CHANGE cp_user cp_user VARCHAR(10) DEFAULT NULL,
                        CHANGE ville_user ville_user VARCHAR(30) DEFAULT NULL, CHANGE civ_user civ_user VARCHAR(10) DEFAULT NULL,
                        CHANGE moreinfo_user moreinfo_user VARCHAR(500) DEFAULT NULL COMMENT \'FORMATIONS ?\',
                        CHANGE cookietoken_user cookietoken_user VARCHAR(32) DEFAULT NULL,
                        CHANGE nomade_parent_user nomade_parent_user INT DEFAULT NULL COMMENT \'Dans le cas d\'\'un user NOMADE, l\'\'ID de son créateur\'');
        $this->addSql('ALTER TABLE caf_user_attr CHANGE params_user_attr params_user_attr VARCHAR(200) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt DROP FOREIGN KEY FK_197AA7EB0C9569F');
        $this->addSql('ALTER TABLE caf_evt DROP FOREIGN KEY FK_197AA7EDA305AEC');
        $this->addSql('DROP INDEX IDX_197AA7EB0C9569F ON caf_evt');
        $this->addSql('DROP INDEX IDX_197AA7EDA305AEC ON caf_evt');
        $this->addSql('ALTER TABLE caf_evt CHANGE status_who_evt status_who_evt INT DEFAULT NULL COMMENT \'ID de l\'\'user qui a changé le statut en dernier\', CHANGE cancelled_who_evt cancelled_who_evt INT DEFAULT NULL COMMENT \'ID user qui a  annulé l\'\'evt\'');
        $this->addSql('ALTER TABLE caf_user CHANGE cafnum_parent_user cafnum_parent_user VARCHAR(20) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci` COMMENT \'Filiation : numéro CAF du parent\', CHANGE tel_user tel_user VARCHAR(30) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, CHANGE tel2_user tel2_user VARCHAR(30) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, CHANGE adresse_user adresse_user VARCHAR(100) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, CHANGE cp_user cp_user VARCHAR(10) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, CHANGE ville_user ville_user VARCHAR(30) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, CHANGE civ_user civ_user VARCHAR(10) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, CHANGE moreinfo_user moreinfo_user VARCHAR(500) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci` COMMENT \'FORMATIONS ?\', CHANGE cookietoken_user cookietoken_user VARCHAR(32) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, CHANGE nomade_parent_user nomade_parent_user INT NOT NULL COMMENT \'Dans le cas d\'\'un user NOMADE, l\'\'ID de son créateur\'');
        $this->addSql('ALTER TABLE caf_user_attr CHANGE params_user_attr params_user_attr VARCHAR(200) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`');
    }
}
