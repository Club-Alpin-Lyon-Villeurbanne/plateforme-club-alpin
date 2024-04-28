-- phpMyAdmin SQL Dump
-- version 4.1.9
-- http://www.phpmyadmin.net
--
-- Client :  localhost
-- Généré le :  Mar 18 Mars 2014 à 15:55
-- Version du serveur :  5.5.35-0+wheezy1-log
-- Version de PHP :  5.4.4-14+deb7u7.5

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données :  `caf`
--

DELIMITER $$
--
-- Procédures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `caf_article_maj_une`()
    MODIFIES SQL DATA
BEGIN

-- compte le nombre d'article en une, enleve 5
SELECT @LimitRowsCount:=(SELECT COUNT(`id_article`)-5
FROM  `caf_article`
WHERE  `status_article` =1
AND  `une_article` =1)
;

-- retrograde les vieux articles de la une en article normaux
PREPARE STMT FROM "UPDATE `caf_article`
SET `une_article` =0
WHERE `status_article` =1
AND  `une_article` =1
ORDER BY `tsp_article` ASC
LIMIT ?";

EXECUTE STMT USING @LimitRowsCount; 

COMMIT;


END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `caf_article`
--

CREATE TABLE IF NOT EXISTS `caf_article` (
  `id_article` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `status_article` int(11) NOT NULL DEFAULT '0' COMMENT '0=pas vu, 1=valide, 2=refusé',
  `status_who_article` int(11) NOT NULL COMMENT 'ID du membre qui change le statut',
  `topubly_article` int(11) NOT NULL COMMENT 'Demander la publication ? Ou laisser en standby',
  `tsp_crea_article` int(11) NOT NULL COMMENT 'Timestamp de création de l''article',
  `tsp_validate_article` int(11) NOT NULL,
  `tsp_article` int(11) NOT NULL COMMENT 'Timestamp affiché de l''article',
  `tsp_lastedit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de dernière modif',
  `user_article` int(11) NOT NULL COMMENT 'ID du créateur',
  `titre_article` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `code_article` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Pour affichage dans les URL',
  `commission_article` int(11) NOT NULL COMMENT 'ID Commission liée (facultativ)',
  `evt_article` int(11) NOT NULL COMMENT 'ID sortie liée',
  `une_article` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'A la une ?',
  `cont_article` text COLLATE utf8_unicode_ci NOT NULL,
  `nb_vues_article` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_article`),
  KEY `id_article` (`id_article`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Structure de la table `caf_chron_launch`
--

CREATE TABLE IF NOT EXISTS `caf_chron_launch` (
  `id_chron_launch` int(11) NOT NULL AUTO_INCREMENT,
  `tsp_chron_launch` bigint(20) NOT NULL,
  PRIMARY KEY (`id_chron_launch`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ;

-- --------------------------------------------------------

--
-- Structure de la table `caf_chron_operation`
--

CREATE TABLE IF NOT EXISTS `caf_chron_operation` (
  `id_chron_operation` int(11) NOT NULL AUTO_INCREMENT,
  `tsp_chron_operation` bigint(20) NOT NULL,
  `code_chron_operation` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `parent_chron_operation` int(11) NOT NULL,
  PRIMARY KEY (`id_chron_operation`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ;

-- --------------------------------------------------------

--
-- Structure de la table `caf_comment`
--

CREATE TABLE IF NOT EXISTS `caf_comment` (
  `id_comment` int(11) NOT NULL AUTO_INCREMENT,
  `status_comment` int(11) NOT NULL DEFAULT '1',
  `tsp_comment` bigint(20) NOT NULL,
  `user_comment` int(11) NOT NULL,
  `name_comment` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `email_comment` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `cont_comment` text COLLATE utf8_unicode_ci NOT NULL,
  `parent_type_comment` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `parent_comment` int(11) NOT NULL,
  PRIMARY KEY (`id_comment`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

-- --------------------------------------------------------

--
-- Structure de la table `caf_commission`
--

CREATE TABLE IF NOT EXISTS `caf_commission` (
  `id_commission` int(11) NOT NULL AUTO_INCREMENT,
  `ordre_commission` int(11) NOT NULL,
  `vis_commission` tinyint(1) NOT NULL,
  `code_commission` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `title_commission` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id_commission`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

-- --------------------------------------------------------

--
-- Structure de la table `caf_content_html`
--

CREATE TABLE IF NOT EXISTS `caf_content_html` (
  `id_content_html` int(11) NOT NULL AUTO_INCREMENT,
  `code_content_html` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `lang_content_html` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  `contenu_content_html` text COLLATE utf8_unicode_ci NOT NULL,
  `date_content_html` bigint(20) NOT NULL,
  `linkedtopage_content_html` varchar(200) COLLATE utf8_unicode_ci NOT NULL COMMENT 'URL relative de la page liée par défaut à cet élément, pour coupler à un moteur de recherche',
  `current_content_html` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Définit le dernier élément en date, pour simplifier les requêtes de recherche',
  `vis_content_html` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_content_html`),
  FULLTEXT KEY `contenu_content_html` (`contenu_content_html`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=63 ;

--
-- Structure de la table `caf_content_inline`
--

CREATE TABLE IF NOT EXISTS `caf_content_inline` (
  `id_content_inline` int(11) NOT NULL AUTO_INCREMENT,
  `groupe_content_inline` int(11) NOT NULL COMMENT 'Le parent de ce contenu, dans l''organisation pour l''administrateur',
  `code_content_inline` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `lang_content_inline` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  `contenu_content_inline` text COLLATE utf8_unicode_ci NOT NULL,
  `date_content_inline` bigint(20) NOT NULL,
  `linkedtopage_content_inline` varchar(200) COLLATE utf8_unicode_ci NOT NULL COMMENT 'URL relative de la page liée par défaut à cet élément, pour coupler à un moteur de recherche',
  PRIMARY KEY (`id_content_inline`),
  FULLTEXT KEY `contenu_content_inline` (`contenu_content_inline`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=45 ;

--
-- Structure de la table `caf_content_inline_group`
--

CREATE TABLE IF NOT EXISTS `caf_content_inline_group` (
  `id_content_inline_group` int(11) NOT NULL AUTO_INCREMENT,
  `ordre_content_inline_group` int(11) NOT NULL,
  `nom_content_inline_group` varchar(300) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id_content_inline_group`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=5 ;

--
-- Structure de la table `caf_evt`
--

CREATE TABLE IF NOT EXISTS `caf_evt` (
  `id_evt` int(11) NOT NULL AUTO_INCREMENT,
  `status_evt` smallint(6) NOT NULL COMMENT '0-unseen 1-ok 2-refused',
  `status_who_evt` int(11) NOT NULL COMMENT 'ID de l''user qui a changé le statut en dernier',
  `status_legal_evt` smallint(6) NOT NULL COMMENT '0-unseen 1-ok 2-refused',
  `status_legal_who_evt` int(11) NOT NULL DEFAULT '0' COMMENT 'ID du validateur légal',
  `cancelled_evt` tinyint(4) NOT NULL DEFAULT '0',
  `cancelled_who_evt` int(11) NOT NULL COMMENT 'ID user qui a  annulé l''evt',
  `cancelled_when_evt` bigint(20) NOT NULL COMMENT 'Timestamp annulation',
  `user_evt` int(11) NOT NULL COMMENT 'id user createur',
  `commission_evt` int(11) NOT NULL,
  `tsp_evt` bigint(20) NOT NULL COMMENT 'timestamp du début du event',
  `tsp_end_evt` bigint(20) NOT NULL,
  `tsp_crea_evt` bigint(20) NOT NULL COMMENT 'Création de l''entrée',
  `tsp_edit_evt` bigint(20) NOT NULL,
  `place_evt` varchar(100) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Lieu de RDV covoiturage',
  `titre_evt` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `code_evt` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `massif_evt` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `rdv_evt` varchar(200) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Lieu détaillé du rdv',
  `tarif_evt` float(10,2) NOT NULL DEFAULT '0.00',
  `denivele_evt` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `distance_evt` int(11) NOT NULL,
  `lat_evt` decimal(11,8) NOT NULL,
  `long_evt` decimal(11,8) NOT NULL,
  `matos_evt` text COLLATE utf8_unicode_ci NOT NULL,
  `difficulte_evt` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `description_evt` text COLLATE utf8_unicode_ci NOT NULL,
  `need_benevoles_evt` tinyint(1) NOT NULL DEFAULT '0',
  `join_start_evt` int(11) NOT NULL COMMENT 'Timestamp de départ des inscriptions',
  `join_max_evt` int(11) NOT NULL COMMENT 'Nombre max d''inscriptions spontanées sur le site, ET PAS d''inscrits total',
  `ngens_max_evt` int(11) NOT NULL COMMENT 'Nombre de gens pouvant y aller au total. Donnée "visuelle" uniquement, pas de calcul.',
  `cycle_master_evt` tinyint(1) NOT NULL COMMENT 'Est-ce la première sortie d''un cycle de sorties liées ?',
  `cycle_parent_evt` int(11) NOT NULL COMMENT 'Si cette sortie est l''enfant d''un cycle, l''id du parent est ici',
  `child_version_from_evt` int(11) NOT NULL DEFAULT '0' COMMENT 'Versionning : chaque modification d-evt crée une entrée "enfant" de l-originale. Ce champ prend l-ID de l-original',
  `child_version_tosubmit` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_evt`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

-- --------------------------------------------------------

--
-- Structure de la table `caf_evt_join`
--

CREATE TABLE IF NOT EXISTS `caf_evt_join` (
  `id_evt_join` int(11) NOT NULL AUTO_INCREMENT,
  `status_evt_join` smallint(6) NOT NULL DEFAULT '0' COMMENT '0=non confirmé - 1=validé - 2=refusé',
  `evt_evt_join` int(11) NOT NULL,
  `user_evt_join` int(11) NOT NULL,
  `affiliant_user_join` int(11) NOT NULL COMMENT 'Si non nulle, cette valeur cible l''utilisateur qui a joint cet user via la fonction d''affiliation. C''est donc lui qui doit recevoir les emails informatifs.',
  `role_evt_join` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `tsp_evt_join` bigint(20) NOT NULL,
  `lastchange_when_evt_join` bigint(20) NOT NULL COMMENT 'Quand a été modifié cet élément',
  `lastchange_who_evt_join` int(11) NOT NULL COMMENT 'Qui a modifié cet élément',
  PRIMARY KEY (`id_evt_join`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

-- --------------------------------------------------------

--
-- Structure de la table `caf_galerie`
--

CREATE TABLE IF NOT EXISTS `caf_galerie` (
  `id_galerie` int(11) NOT NULL AUTO_INCREMENT,
  `ordre_galerie` int(11) NOT NULL,
  `titre_galerie` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `vis_galerie` tinyint(1) NOT NULL DEFAULT '1',
  `evt_galerie` int(11) NOT NULL COMMENT 'Sortie liée (facultatif)',
  `article_galerie` int(11) NOT NULL COMMENT 'Article lié (facultatif)',
  PRIMARY KEY (`id_galerie`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

-- --------------------------------------------------------

--
-- Structure de la table `caf_img`
--

CREATE TABLE IF NOT EXISTS `caf_img` (
  `id_img` int(11) NOT NULL AUTO_INCREMENT,
  `ordre_img` int(11) NOT NULL,
  `galerie_img` int(11) NOT NULL,
  `evt_img` int(11) NOT NULL COMMENT 'Une photo peut être directement liée à une sortie et non une galerie (ex : creéation d''evt)',
  `user_img` int(11) NOT NULL,
  `titre_img` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `legende_img` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `fichier_img` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `vis_img` tinyint(1) NOT NULL DEFAULT '1',
  `status_img` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_img`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

-- --------------------------------------------------------

--
-- Structure de la table `caf_log_admin`
--

CREATE TABLE IF NOT EXISTS `caf_log_admin` (
  `id_log_admin` int(11) NOT NULL AUTO_INCREMENT,
  `code_log_admin` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `desc_log_admin` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `ip_log_admin` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_log_admin` bigint(20) NOT NULL,
  PRIMARY KEY (`id_log_admin`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

-- --------------------------------------------------------

--
-- Structure de la table `caf_message`
--

CREATE TABLE IF NOT EXISTS `caf_message` (
  `id_message` int(11) NOT NULL AUTO_INCREMENT,
  `date_message` bigint(20) NOT NULL,
  `to_message` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `from_message` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `headers_message` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
  `code_message` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `cont_message` text COLLATE utf8_unicode_ci NOT NULL,
  `success_message` tinyint(1) NOT NULL,
  PRIMARY KEY (`id_message`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

-- --------------------------------------------------------

--
-- Structure de la table `caf_page`
--

CREATE TABLE IF NOT EXISTS `caf_page` (
  `id_page` int(11) NOT NULL AUTO_INCREMENT,
  `parent_page` int(11) NOT NULL,
  `admin_page` tinyint(1) NOT NULL COMMENT 'Protection et mise en page de page admin (!=public)',
  `superadmin_page` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Page réservée au super-administrateur. "Contenu" dans le niveau administrateur dans la hiérarchie des filtres sur le site : admin_page doit donc aussi etre activé',
  `vis_page` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'On / Off',
  `ordre_page` int(11) NOT NULL,
  `menu_page` tinyint(1) NOT NULL COMMENT 'Apparait dans le menu principal ?',
  `ordre_menu_page` int(11) NOT NULL COMMENT 'Position dans le menu ppal',
  `menuadmin_page` tinyint(1) NOT NULL COMMENT 'Apparait dans le menu admin ?',
  `ordre_menuadmin_page` int(11) NOT NULL COMMENT 'Position dans le menu admin',
  `code_page` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT 'ID lié au nom des fichiers et des variables',
  `default_name_page` varchar(100) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Pour les pages admin notamment',
  `meta_title_page` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Booléen : utiliser un titre sur mesure ou pas',
  `meta_description_page` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Booléen : utiliser une description sur mesure ou pas',
  `priority_page` decimal(1,1) NOT NULL COMMENT 'Priorité de sitemap',
  `add_css_page` varchar(200) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Liste de fichiers css à ajouter, séparés par ;',
  `add_js_page` varchar(200) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Liste de fichiers js à ajouter, séparés par ;',
  `lock_page` tinyint(1) NOT NULL COMMENT 'Bloquer l-édition même au superadmin',
  `pagelibre_page` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Pour le module de créatino de pages libres. Pour les pages standarts, comme des articles Wordpress',
  `created_page` bigint(20) NOT NULL,
  PRIMARY KEY (`id_page`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=43 ;

--
-- Structure de la table `caf_token`
--

CREATE TABLE IF NOT EXISTS `caf_token` (
  `id_token` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `time_token` bigint(20) NOT NULL,
  PRIMARY KEY (`id_token`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `caf_user`
--

CREATE TABLE IF NOT EXISTS `caf_user` (
  `id_user` bigint(20) NOT NULL AUTO_INCREMENT,
  `email_user` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `mdp_user` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `cafnum_user` varchar(20) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Numéro de licence',
  `cafnum_parent_user` varchar(20) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Filiation : numéro CAF du parent',
  `firstname_user` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `lastname_user` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `nickname_user` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `created_user` bigint(20) NOT NULL,
  `birthday_user` bigint(20) NOT NULL,
  `tel_user` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `tel2_user` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `adresse_user` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `cp_user` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `ville_user` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `pays_user` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `civ_user` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `moreinfo_user` varchar(500) COLLATE utf8_unicode_ci NOT NULL COMMENT 'FORMATIONS ?',
  `auth_contact_user` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'users' COMMENT 'QUI peut me contacter via formulaire',
  `valid_user` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=l''user n''a pas activé son compte   1=activé    2=bloqué',
  `cookietoken_user` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `manuel_user` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'User créé à la mano sur le site ?',
  `nomade_user` tinyint(1) NOT NULL DEFAULT '0',
  `nomade_parent_user` int(11) NOT NULL COMMENT 'Dans le cas d''un user NOMADE, l''ID de son créateur',
  `date_adhesion_user` bigint(20) DEFAULT NULL,
  `doit_renouveler_user` tinyint(1) NOT NULL DEFAULT '0',
  `alerte_renouveler_user` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Si sur 1 : une alerte s''affiche pour annoncer que l''adhérent doit renouveler sa licence',
  `ts_insert_user` bigint(20) DEFAULT NULL COMMENT 'timestamp 1ere insertion',
  `ts_update_user` bigint(20) DEFAULT NULL COMMENT 'timestamp derniere maj',
  PRIMARY KEY (`id_user`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;

--
-- Structure de la table `caf_userright`
--

CREATE TABLE IF NOT EXISTS `caf_userright` (
  `id_userright` int(11) NOT NULL AUTO_INCREMENT,
  `code_userright` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `title_userright` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `ordre_userright` int(11) NOT NULL,
  `parent_userright` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id_userright`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=73 ;

--
-- Structure de la table `caf_usertype`
--

CREATE TABLE IF NOT EXISTS `caf_usertype` (
  `id_usertype` int(11) NOT NULL AUTO_INCREMENT,
  `hierarchie_usertype` tinyint(4) NOT NULL COMMENT 'Ordre d''apparition des types',
  `code_usertype` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `title_usertype` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `limited_to_comm_usertype` tinyint(1) NOT NULL COMMENT 'bool : ce type est (ou non) limité à une commission donnée',
  PRIMARY KEY (`id_usertype`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=12 ;

--
-- Structure de la table `caf_usertype_attr`
--

CREATE TABLE IF NOT EXISTS `caf_usertype_attr` (
  `id_usertype_attr` int(11) NOT NULL AUTO_INCREMENT,
  `type_usertype_attr` int(11) NOT NULL COMMENT 'ID du type d''user (admin, modéro etc...)',
  `right_usertype_attr` int(11) NOT NULL COMMENT 'ID du droit dans la table userright',
  `details_usertype_attr` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id_usertype_attr`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=352 ;


--
-- Structure de la table `caf_user_attr`
--

CREATE TABLE IF NOT EXISTS `caf_user_attr` (
  `id_user_attr` int(11) NOT NULL AUTO_INCREMENT,
  `user_user_attr` int(11) NOT NULL COMMENT 'ID user possédant le type ',
  `usertype_user_attr` int(11) NOT NULL COMMENT 'ID du type (admin, modero etc...)',
  `params_user_attr` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `details_user_attr` varchar(100) COLLATE utf8_unicode_ci NOT NULL COMMENT 'date - de qui... ?',
  PRIMARY KEY (`id_user_attr`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=4 ;

--
-- Structure de la table `caf_user_mailchange`
--

CREATE TABLE IF NOT EXISTS `caf_user_mailchange` (
  `id_user_mailchange` int(11) NOT NULL AUTO_INCREMENT,
  `user_user_mailchange` int(11) NOT NULL,
  `token_user_mailchange` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `email_user_mailchange` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `time_user_mailchange` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user_mailchange`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

-- --------------------------------------------------------

--
-- Structure de la table `caf_user_mdpchange`
--

CREATE TABLE IF NOT EXISTS `caf_user_mdpchange` (
  `id_user_mdpchange` int(11) NOT NULL AUTO_INCREMENT,
  `user_user_mdpchange` int(11) NOT NULL,
  `token_user_mdpchange` varchar(32) COLLATE latin1_general_ci NOT NULL,
  `pwd_user_mdpchange` varchar(32) COLLATE latin1_general_ci NOT NULL,
  `time_user_mdpchange` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user_mdpchange`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ;

-- 
-- On autorise les NULL sur birthday_user, gestion de l'age des utilisateurs nomades
-- 
ALTER TABLE `caf_user` CHANGE `birthday_user` `birthday_user` BIGINT(20) NULL DEFAULT NULL; 


--
-- Modification dans la table `caf_content_html`
-- Précision : besoin d'être inscrit au CAF Lyon-Villeurbanne
--

CREATE TABLE IF NOT EXISTS `caf_user_niveau` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_user` int(11) unsigned NOT NULL,
  `id_commission` int(11) unsigned NOT NULL,
  `niveau_technique` smallint(2) unsigned DEFAULT NULL,
  `niveau_physique` smallint(2) unsigned DEFAULT NULL,
  `commentaire` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `caf_evt` CHANGE `distance_evt` `distance_evt` FLOAT(10,2) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `caf_evt` ADD `itineraire` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `difficulte_evt`;
ALTER TABLE `caf_evt` CHANGE `denivele_evt` `denivele_evt` INT(5) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `caf_evt` ADD `tarif_detail` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `tarif_evt`;
ALTER TABLE `caf_evt` ADD `tarif_restaurant` FLOAT(10,2) UNSIGNED NULL DEFAULT NULL AFTER `tarif_detail`;
ALTER TABLE `caf_evt` ADD `repas_restaurant` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `tarif_detail`;
ALTER TABLE `caf_evt` CHANGE `tarif_evt` `tarif_evt` FLOAT(10,2) NULL DEFAULT NULL;
ALTER TABLE `caf_evt` ADD `id_groupe` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `commission_evt`;

CREATE TABLE IF NOT EXISTS `caf_groupe` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_commission` int(11) unsigned NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text,
  `niveau_physique` int(2) unsigned DEFAULT NULL,
  `niveau_technique` int(2) unsigned DEFAULT NULL,
  `actif` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `caf_lieu` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) NOT NULL,
  `description` text,
  `ign` text,
  `lat` varchar(20) DEFAULT NULL,
  `lng` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `caf_destination` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_lieu` int(11) unsigned NOT NULL,
  `id_user_who_create` int(11) unsigned NOT NULL,
  `id_user_responsable` int(11) unsigned NOT NULL,
  `id_user_adjoint` int(11) unsigned DEFAULT NULL,
  `publie` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `annule` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `mail` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'les emails de cloture ont ils déjà été envoyés ?',
  `nom` varchar(100) NOT NULL,
  `code` varchar(100) DEFAULT NULL,
  `description` text,
  `date` datetime DEFAULT NULL,
  `date_fin` date DEFAULT NULL,
  `cout_transport` float(10,2) unsigned DEFAULT NULL,
  `ign` text,
  `inscription_ouverture` datetime DEFAULT NULL,
  `inscription_fin` datetime DEFAULT NULL,
  `inscription_locked` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `caf_bus` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_destination` int(11) unsigned NOT NULL,
  `intitule` varchar(50) NOT NULL,
  `places_max` int(5) unsigned NOT NULL,
  `places_disponibles` int(5) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `caf_bus_lieu_destination` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_bus` int(11) unsigned NOT NULL,
  `id_destination` int(11) unsigned NOT NULL,
  `id_lieu` int(11) unsigned NOT NULL,
  `type_lieu` varchar(50) DEFAULT NULL COMMENT 'Choisir entre : ramasse, reprise',
  `date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `caf_evt_join` ADD `id_bus_lieu_destination` INT(11) UNSIGNED NULL DEFAULT NULL ;
ALTER TABLE `caf_evt_join` ADD `id_destination` INT(11) UNSIGNED NULL DEFAULT NULL , ADD `is_covoiturage` TINYINT(1) UNSIGNED NULL DEFAULT NULL ;
ALTER TABLE `caf_evt_join` ADD `is_restaurant` TINYINT(1) UNSIGNED NULL DEFAULT NULL ;

CREATE TABLE IF NOT EXISTS `caf_evt_destination` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_evt` int(11) unsigned NOT NULL,
  `id_destination` int(11) unsigned NOT NULL,
  `id_lieu_depose` int(11) unsigned DEFAULT NULL,
  `date_depose` datetime DEFAULT NULL,
  `id_lieu_reprise` int(11) unsigned DEFAULT NULL,
  `date_reprise` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
TRUNCATE caf_usertype_attr;

ALTER TABLE `caf_evt` ADD `cb_evt` TINYINT(1) UNSIGNED NULL DEFAULT NULL ;
ALTER TABLE `caf_evt_join` ADD `is_cb` TINYINT(1) UNSIGNED NULL DEFAULT NULL ;

--
-- Structure de la table `caf_partenaires`
--

CREATE TABLE `caf_partenaires` (
  `part_id` int(11) NOT NULL,
  `part_name` varchar(50) CHARACTER SET latin1 NOT NULL,
  `part_url` varchar(256) CHARACTER SET latin1 NOT NULL,
  `part_desc` varchar(500) CHARACTER SET latin1 NOT NULL,
  `part_image` varchar(100) CHARACTER SET latin1 NOT NULL,
  `part_type` int(1) NOT NULL DEFAULT '1' COMMENT '1=prive,2=public',
  `part_enable` int(11) NOT NULL DEFAULT '1' COMMENT 'partenaire actif =1',
  `part_order` int(11) NOT NULL DEFAULT '999999',
  `part_click` int(11) NOT NULL DEFAULT '0' COMMENT 'nb de cliques'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='infos partenaires';

--
-- Index pour la table `caf_partenaires`
--
ALTER TABLE `caf_partenaires`
  ADD PRIMARY KEY (`part_id`);


--
-- AUTO_INCREMENT pour la table `caf_partenaires`
--
ALTER TABLE `caf_partenaires`
  MODIFY `part_id` int(11) NOT NULL AUTO_INCREMENT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
