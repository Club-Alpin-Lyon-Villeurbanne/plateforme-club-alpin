<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230504100401 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE caf_article CHANGE titre_article titre_article VARCHAR(200) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE code_article code_article VARCHAR(50) NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'Pour affichage dans les URL\', CHANGE cont_article cont_article TEXT NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE caf_comment CHANGE name_comment name_comment VARCHAR(50) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE cont_comment cont_comment TEXT NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE parent_type_comment parent_type_comment VARCHAR(20) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE caf_commission CHANGE code_commission code_commission VARCHAR(50) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE title_commission title_commission VARCHAR(30) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE caf_evt CHANGE place_evt place_evt VARCHAR(100) NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'Lieu de RDV covoiturage\', CHANGE titre_evt titre_evt VARCHAR(100) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE code_evt code_evt VARCHAR(30) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE massif_evt massif_evt VARCHAR(100) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE rdv_evt rdv_evt VARCHAR(200) NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'Lieu détaillé du rdv\', CHANGE tarif_detail tarif_detail TEXT DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE matos_evt matos_evt TEXT DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE difficulte_evt difficulte_evt VARCHAR(50) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE itineraire itineraire TEXT DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE description_evt description_evt TEXT NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE caf_message CHANGE to_message to_message VARCHAR(100) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE from_message from_message VARCHAR(100) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE headers_message headers_message VARCHAR(500) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE code_message code_message VARCHAR(30) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE cont_message cont_message TEXT NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE caf_user CHANGE mdp_user mdp_user VARCHAR(1024) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE firstname_user firstname_user VARCHAR(50) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE lastname_user lastname_user VARCHAR(50) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE nickname_user nickname_user VARCHAR(20) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE moreinfo_user moreinfo_user VARCHAR(500) DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'FORMATIONS ?\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE caf_user CHANGE mdp_user mdp_user VARCHAR(1024) DEFAULT NULL, CHANGE firstname_user firstname_user VARCHAR(50) NOT NULL, CHANGE lastname_user lastname_user VARCHAR(50) NOT NULL, CHANGE nickname_user nickname_user VARCHAR(20) NOT NULL, CHANGE moreinfo_user moreinfo_user VARCHAR(500) DEFAULT NULL COMMENT \'FORMATIONS ?\'');
        $this->addSql('ALTER TABLE caf_evt CHANGE place_evt place_evt VARCHAR(100) NOT NULL COMMENT \'Lieu de RDV covoiturage\', CHANGE titre_evt titre_evt VARCHAR(100) NOT NULL, CHANGE code_evt code_evt VARCHAR(30) NOT NULL, CHANGE massif_evt massif_evt VARCHAR(100) DEFAULT NULL, CHANGE rdv_evt rdv_evt VARCHAR(200) NOT NULL COMMENT \'Lieu détaillé du rdv\', CHANGE tarif_detail tarif_detail TEXT DEFAULT NULL, CHANGE matos_evt matos_evt TEXT DEFAULT NULL, CHANGE difficulte_evt difficulte_evt VARCHAR(50) DEFAULT NULL, CHANGE itineraire itineraire TEXT DEFAULT NULL, CHANGE description_evt description_evt TEXT NOT NULL');
        $this->addSql('ALTER TABLE caf_message CHANGE to_message to_message VARCHAR(100) NOT NULL, CHANGE from_message from_message VARCHAR(100) NOT NULL, CHANGE headers_message headers_message VARCHAR(500) NOT NULL, CHANGE code_message code_message VARCHAR(30) NOT NULL, CHANGE cont_message cont_message TEXT NOT NULL');
        $this->addSql('ALTER TABLE caf_commission CHANGE code_commission code_commission VARCHAR(50) NOT NULL, CHANGE title_commission title_commission VARCHAR(30) NOT NULL');
        $this->addSql('ALTER TABLE caf_comment CHANGE name_comment name_comment VARCHAR(50) NOT NULL, CHANGE cont_comment cont_comment TEXT NOT NULL, CHANGE parent_type_comment parent_type_comment VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE caf_article CHANGE titre_article titre_article VARCHAR(200) NOT NULL, CHANGE code_article code_article VARCHAR(50) NOT NULL COMMENT \'Pour affichage dans les URL\', CHANGE cont_article cont_article TEXT NOT NULL');
    }
}
