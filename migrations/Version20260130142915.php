<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260130142915 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds comments to tables and fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt CHANGE id_groupe id_groupe INT UNSIGNED DEFAULT NULL COMMENT \'plus utilisé\', CHANGE massif_evt massif_evt VARCHAR(100) DEFAULT NULL COMMENT \'plus utilisé\', CHANGE need_benevoles_evt need_benevoles_evt TINYINT(1) NOT NULL COMMENT \'plus utilisé\'');
        $this->addSql('ALTER TABLE caf_evt_join CHANGE evt_evt_join evt_evt_join INT NOT NULL COMMENT \'ID sortie\', CHANGE user_evt_join user_evt_join BIGINT NOT NULL COMMENT \'ID utilisateur\', CHANGE affiliant_user_join affiliant_user_join BIGINT DEFAULT NULL COMMENT \'plus utilisé ?\', CHANGE lastchange_who_evt_join lastchange_who_evt_join BIGINT DEFAULT NULL COMMENT \'ID de l\'\'utilisateur qui a accepté ou refusé la participation\', CHANGE role_evt_join role_evt_join VARCHAR(20) NOT NULL COMMENT \'rôle utilisateur\', CHANGE is_covoiturage is_covoiturage TINYINT(1) DEFAULT NULL COMMENT \'plus utilisé\', CHANGE has_paid has_paid TINYINT(1) DEFAULT 0 NOT NULL COMMENT \'a payé sur HelloAsso (si applicable et que l\'\'email était OK)\'');
        $this->addSql('ALTER TABLE caf_user CHANGE media_upload_id media_upload_id INT DEFAULT NULL COMMENT \'Photo de profil\', CHANGE manuel_user manuel_user TINYINT(1) NOT NULL COMMENT \'plus utilisé\', CHANGE nomade_user nomade_user TINYINT(1) NOT NULL COMMENT \'plus utilisé\', CHANGE nomade_parent_user nomade_parent_user INT DEFAULT NULL COMMENT \'Dans le cas d\'\'un user ajouté manuellement (profils 3 et 4), l\'\'ID de son créateur\', CHANGE doit_renouveler_user doit_renouveler_user TINYINT(1) NOT NULL COMMENT \'vaut 1 si licence expirée\', CHANGE alerte_renouveler_user alerte_renouveler_user TINYINT(1) NOT NULL COMMENT \'Si vaut 1 : un message d\'\'alerte s\'\'affiche dans le menu pour prévenir l\'\'adhérent qu\'\'il doit renouveler sa licence\', CHANGE alerts alerts JSON DEFAULT NULL COMMENT \'liste des alertes email activées\', CHANGE join_date join_date DATETIME DEFAULT NULL COMMENT \'Date de dernière prise de licence annuelle ou date de début de validité carte découverte(DC2Type:datetime_immutable)\', CHANGE discovery_end_datetime discovery_end_datetime DATETIME DEFAULT NULL COMMENT \'Date de fin de validité carte découverte(DC2Type:datetime_immutable)\', CHANGE profile_type profile_type SMALLINT DEFAULT 0 NOT NULL COMMENT \'1 licencié annuel du club, 2 carte découverte du club, 3 licencié autre club, 4 personne extérieure (ex formateur)\'');
        $this->addSql('ALTER TABLE caf_user_attr CHANGE user_user_attr user_user_attr BIGINT NOT NULL COMMENT \'ID utilisateur\', CHANGE usertype_user_attr usertype_user_attr INT DEFAULT NULL COMMENT \'ID niveau de droit\', CHANGE params_user_attr params_user_attr VARCHAR(200) DEFAULT NULL COMMENT \'commission sur laquelle ça s\'\'applique\', CHANGE details_user_attr details_user_attr VARCHAR(100) NOT NULL COMMENT \'timestamp ?\', CHANGE description_user_attr description_user_attr VARCHAR(255) DEFAULT NULL COMMENT \'commentaire\'');
        $this->addSql('ALTER TABLE caf_userright CHANGE parent_userright parent_userright VARCHAR(40) NOT NULL COMMENT \'regroupement dans la matrice des droits\'');
        $this->addSql('ALTER TABLE caf_usertype CHANGE hierarchie_usertype hierarchie_usertype INT NOT NULL COMMENT \'Ordre d\'\'apparition des niveaux\', CHANGE limited_to_comm_usertype limited_to_comm_usertype TINYINT(1) NOT NULL COMMENT \'booléen : ce niveau est (ou non) limité à une commission donnée\'');
    }

    public function down(Schema $schema): void
    {
        // pas de down() : on n'enlève pas les commentaires
    }
}
