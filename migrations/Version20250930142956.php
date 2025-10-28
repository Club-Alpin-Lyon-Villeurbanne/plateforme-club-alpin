<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250930142956 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds datetime fields in several tables';
    }

    public function up(Schema $schema): void
    {
        // création les champs datetime nullable
        $this->addSql('ALTER TABLE caf_article ADD validation_date DATETIME DEFAULT NULL COMMENT \'date de publication de l\'\'article(DC2Type:datetime_immutable)\', ADD created_at DATETIME DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE caf_comment ADD created_at DATETIME DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE caf_evt ADD start_date DATETIME DEFAULT NULL COMMENT \'date et heure de début(DC2Type:datetime_immutable)\', ADD end_date DATETIME DEFAULT NULL COMMENT \'date et heure de fin(DC2Type:datetime_immutable)\', ADD join_start_date DATETIME DEFAULT NULL COMMENT \'date du début des inscriptions(DC2Type:datetime_immutable)\', ADD cancellation_date DATETIME DEFAULT NULL COMMENT \'date d\'\'annulation(DC2Type:datetime_immutable)\', ADD created_at DATETIME DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE caf_evt_join ADD created_at DATETIME DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE caf_user ADD birthdate DATE DEFAULT NULL COMMENT \'Date de naissance(DC2Type:date_immutable)\', ADD join_date DATE DEFAULT NULL COMMENT \'Date adhésion(DC2Type:date_immutable)\', ADD created_at DATETIME DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL');

        // mettre des données dans les champs à partir des anciens timestamps
        $this->addSql('SET time_zone=\'Europe/Paris\'');
        $this->addSql('UPDATE caf_article SET created_at = FROM_UNIXTIME(tsp_crea_article), updated_at = (CASE WHEN tsp_lastedit IS NOT NULL THEN tsp_lastedit ELSE NOW() END)');
        $this->addSql('UPDATE caf_article SET validation_date = (CASE WHEN tsp_validate_article IS NOT NULL THEN FROM_UNIXTIME(tsp_validate_article) ELSE NULL END)');
        $this->addSql('UPDATE caf_comment SET created_at = FROM_UNIXTIME(tsp_comment), updated_at = (CASE WHEN tsp_comment IS NOT NULL THEN FROM_UNIXTIME(tsp_comment) ELSE NOW() END)');
        $this->addSql('UPDATE caf_evt SET created_at = FROM_UNIXTIME(tsp_crea_evt), updated_at = (CASE WHEN tsp_edit_evt IS NOT NULL THEN FROM_UNIXTIME(tsp_edit_evt) ELSE NOW() END)');
        $this->addSql('UPDATE caf_evt SET start_date = (CASE WHEN tsp_evt IS NOT NULL THEN FROM_UNIXTIME(tsp_evt) ELSE NULL END), end_date = (CASE WHEN tsp_end_evt IS NOT NULL THEN FROM_UNIXTIME(tsp_end_evt) ELSE NULL END)');
        $this->addSql('UPDATE caf_evt SET join_start_date = (CASE WHEN join_start_evt IS NOT NULL THEN FROM_UNIXTIME(join_start_evt) ELSE NULL END), cancellation_date = (CASE WHEN cancelled_when_evt IS NOT NULL THEN FROM_UNIXTIME(cancelled_when_evt) ELSE NULL END)');
        $this->addSql('UPDATE caf_evt_join SET created_at = FROM_UNIXTIME(tsp_evt_join), updated_at = (CASE WHEN lastchange_when_evt_join IS NOT NULL THEN FROM_UNIXTIME(lastchange_when_evt_join) ELSE NOW() END)');
        $this->addSql('UPDATE caf_user SET created_at = FROM_UNIXTIME(ts_insert_user), updated_at = (CASE WHEN ts_update_user IS NOT NULL THEN FROM_UNIXTIME(ts_update_user) ELSE NOW() END)');
        $this->addSql('UPDATE caf_user SET birthdate = FROM_UNIXTIME(birthday_user), join_date = (CASE WHEN date_adhesion_user IS NOT NULL THEN FROM_UNIXTIME(date_adhesion_user) ELSE NULL END)');
        $this->addSql('UPDATE caf_user SET created_at = updated_at WHERE created_at IS NULL');

        // mettre à jour les champs non nullables
        $this->addSql('ALTER TABLE caf_article CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE caf_comment CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE caf_evt CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE caf_evt_join CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE caf_user CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');

//        // suppression anciens champs
//        $this->addSql('ALTER TABLE caf_evt_join DROP tsp_evt_join, DROP lastchange_when_evt_join');
//        $this->addSql('ALTER TABLE caf_article DROP tsp_crea_article, DROP tsp_validate_article, DROP tsp_article, DROP tsp_lastedit');
//        $this->addSql('ALTER TABLE caf_comment DROP tsp_comment');
//        $this->addSql('ALTER TABLE caf_evt DROP cancelled_when_evt, DROP tsp_evt, DROP tsp_end_evt, DROP tsp_crea_evt, DROP tsp_edit_evt, DROP join_start_evt');
//        $this->addSql('ALTER TABLE caf_user DROP date_adhesion_user, DROP birthday_user');
//        $this->addSql('ALTER TABLE caf_user DROP created_user, DROP ts_insert_user, DROP ts_update_user');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt_join ADD tsp_evt_join INT NOT NULL, ADD lastchange_when_evt_join INT DEFAULT NULL COMMENT \'Quand a été modifié cet élément\'');
        $this->addSql('ALTER TABLE caf_article ADD tsp_crea_article INT NOT NULL COMMENT \'Timestamp de création de l\'\'article\', ADD tsp_validate_article INT DEFAULT NULL, ADD tsp_article INT NOT NULL COMMENT \'Timestamp affiché de l\'\'article\', ADD tsp_lastedit DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'Date de dernière modif\'');
        $this->addSql('ALTER TABLE caf_comment ADD tsp_comment BIGINT NOT NULL');
        $this->addSql('ALTER TABLE caf_user ADD date_adhesion_user BIGINT DEFAULT NULL, ADD birthday_user BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE caf_user ADD created_user BIGINT NOT NULL, ADD ts_insert_user BIGINT DEFAULT NULL COMMENT \'timestamp 1ere insertion\', ADD ts_update_user BIGINT DEFAULT NULL COMMENT \'timestamp derniere maj\'');
        $this->addSql('ALTER TABLE caf_evt ADD cancelled_when_evt BIGINT DEFAULT NULL COMMENT \'Timestamp annulation\', ADD tsp_evt BIGINT DEFAULT NULL COMMENT \'timestamp du début du event\', ADD tsp_end_evt BIGINT DEFAULT NULL, ADD tsp_crea_evt BIGINT NOT NULL COMMENT \'Création de l\'\'entrée\', ADD tsp_edit_evt BIGINT DEFAULT NULL, ADD join_start_evt INT DEFAULT NULL COMMENT \'Timestamp de départ des inscriptions\'');

        $this->addSql('ALTER TABLE caf_article DROP validation_date, DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE caf_comment DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE caf_evt DROP start_date, DROP end_date, DROP join_start_date, DROP cancellation_date, DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE caf_evt_join DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE caf_user DROP birthdate, DROP join_date, DROP created_at, DROP updated_at');
    }
}
