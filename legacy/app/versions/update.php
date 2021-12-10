<?php

require __DIR__.'/../../app/includes.php';

$queries = [];
$app_version = get_version_nb();

function get_version_nb()
{
    return '1.0';
}

function set_version_nb($nb)
{
    // write
    return $nb;
}

if ($app_version < '1.0.1') {
    /* Table des notes utilisateur par commission */
    $queries[] =
        'CREATE TABLE IF NOT EXISTS `caf_user_niveau` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `id_user` int(11) unsigned NOT NULL,
            `id_commission` int(11) unsigned NOT NULL,
            `niveau_technique` smallint(2) unsigned DEFAULT NULL,
            `niveau_physique` smallint(2) unsigned DEFAULT NULL,
            `commentaire` text,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';
    /* Droits d'attribution / lecture des notes */
    $queries[] =
        "INSERT INTO caf_`userright` (`id_userright`, `code_userright`, `title_userright`, `ordre_userright`, `parent_userright`)
        VALUES
            (NULL, 'user_note_comm_edit', 'Définir le niveau d''un adhérent  dans une commission', '600', 'GESTION DES COMPTES ADHERENTS'),
            (NULL, 'user_note_comm_read', 'Consulter le niveau d''un adhérent dans une commission', '610', 'GESTION DES COMPTES ADHERENTS');";

    $app_version = set_version_nb('1.0.1');
}

// Nouvelle version majeure : gestion des destinations avec plusieurs sorties

if ($app_version < '1.1') {
    // Feuille de sortie
    $queries[] =
        "INSERT INTO `caf_page` VALUES(NULL, 0, 0, 0, 1, 0, 0, 0, 0, 0, 'feuille-de-sortie', 'Feuille de sortie', 0, 0, '0.0', '', '', 0, 0, 0);";

    /* FICHE SORTIE */
    // Gestion des distances
    $queries[] =
        'ALTER TABLE `caf_evt` CHANGE `distance_evt` `distance_evt` FLOAT(10,2) UNSIGNED NULL DEFAULT NULL;';
    /* Itinéraire : descriptif textuel */
    $queries[] =
        'ALTER TABLE `caf_evt` ADD `itineraire` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `difficulte_evt`;';
    /* Dénivelé : chiffre en (m) */
    $queries[] =
        'ALTER TABLE `caf_evt` CHANGE `denivele_evt` `denivele_evt` INT(5) UNSIGNED NULL DEFAULT NULL;';
    /* Détail des tarifs */
    $queries[] =
        'ALTER TABLE `caf_evt` ADD `tarif_detail` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `tarif_evt`;';
    /* Tarif facultatif de repas au restaurant */
    $queries[] =
        'ALTER TABLE `caf_evt` ADD `tarif_restaurant` FLOAT(10,2) UNSIGNED NULL DEFAULT NULL AFTER `tarif_detail`;';
    /* Repas facultatif au restaurant */
    $queries[] =
        "ALTER TABLE caf_evt` ADD `repas_restaurant` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `tarif_detail`;";
    /* Tarif NULL */
    $queries[] =
        'ALTER TABLE `caf_evt` CHANGE `tarif_evt` `tarif_evt` FLOAT(10,2) NULL DEFAULT NULL;';
    /* Groupe */
    $queries[] =
        'ALTER TABLE `caf_evt` ADD `id_groupe` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `commission_evt`;';

    /* GROUPES DE NIVEAUX */
    // Gestion des groupes
    $queries[] =
        "CREATE TABLE IF NOT EXISTS `caf_groupe` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `id_commission` int(11) unsigned NOT NULL,
          `nom` varchar(100) NOT NULL,
          `description` text,
          `niveau_physique` int(2) unsigned DEFAULT NULL,
          `niveau_technique` int(2) unsigned DEFAULT NULL,
          `actif` tinyint(1) unsigned NOT NULL DEFAULT '1',
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
    // Droits de creation suppression des groupes de niveaux
    $queries[] =
        "INSERT INTO caf_`userright` (`id_userright`, `code_userright`, `title_userright`, `ordre_userright`, `parent_userright`)
        VALUES
            (NULL, 'comm_groupe_edit', 'Créer un groupe de niveau au sein d''une commission', '600', 'COMMISSIONS'),
            (NULL, 'comm_groupe_delete', 'Supprimer un groupe de niveau au sein d''une commission', '610', 'COMMISSIONS'),
            (NULL, 'comm_groupe_activer_desactiver', 'Activer et désactiver un groupe au sein d''une commission', '620', 'COMMISSIONS');";

    // Gestion des lieux
    $queries[] =
        'CREATE TABLE IF NOT EXISTS `caf_lieu` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `nom` varchar(50) NOT NULL,
          `description` text,
          `ign` text,
          `lat` varchar(20) DEFAULT NULL,
          `lng` varchar(20) DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';
    // Gestion des destinations
    $queries[] =
        "CREATE TABLE IF NOT EXISTS `caf_destination` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
    $queries[] =
        "INSERT INTO `caf_page` (`id_page`, `parent_page`, `admin_page`, `superadmin_page`, `vis_page`, `ordre_page`, `menu_page`, `ordre_menu_page`, `menuadmin_page`, `ordre_menuadmin_page`, `code_page`, `default_name_page`, `meta_title_page`, `meta_description_page`, `priority_page`, `add_css_page`, `add_js_page`, `lock_page`, `pagelibre_page`, `created_page`)
        VALUES (48, 0, 0, 0, 1, 0, 0, 0, 0, 0, 'destination', '', 0, 0, '0.0', '', '', 0, 0, 0);";
    // Nouveaux droits de gestion des destinations
    $queries[] =
        "INSERT INTO `caf_userright` (`id_userright`, `code_userright`, `title_userright`, `ordre_userright`, `parent_userright`)
        VALUES
            (NULL, 'destination_consulter', 'Consulter une destination', '10', 'GESTION DES DESTINATIONS'),
            (NULL, 'destination_leader', 'Etre (co)responsable d''une destination', '15', 'GESTION DES DESTINATIONS'),
            (NULL, 'destination_creer', 'Créer une destination', '20', 'GESTION DES DESTINATIONS'),
            (NULL, 'destination_modifier', 'Modifier une destination', '30', 'GESTION DES DESTINATIONS'),
            (NULL, 'destination_supprimer', 'Supprimer une destination', '40', 'GESTION DES DESTINATIONS'),
            (NULL, 'destination_print', 'Imprimer les bilans de destination', '50', 'GESTION DES DESTINATIONS'),
            (NULL, 'destination_mailer', 'Envoyer les emails de cloture', '60', 'GESTION DES DESTINATIONS'),
            (NULL, 'destination_activer_desactiver', '(Dé)bloquer une destination', '70', 'GESTION DES DESTINATIONS');";
    // Gestion des Bus
    $queries[] =
        'CREATE TABLE IF NOT EXISTS `caf_bus` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `id_destination` int(11) unsigned NOT NULL,
          `intitule` varchar(50) NOT NULL,
          `places_max` int(5) unsigned NOT NULL,
          `places_disponibles` int(5) unsigned DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';
    // Gestion des lieux de dépose - et ramasse (?) par destination
    $queries[] =
        "CREATE TABLE IF NOT EXISTS `caf_bus_lieu_destination` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `id_bus` int(11) unsigned NOT NULL,
          `id_destination` int(11) unsigned NOT NULL,
          `id_lieu` int(11) unsigned NOT NULL,
          `type_lieu` varchar(50) DEFAULT NULL COMMENT 'Choisir entre : ramasse, reprise',
          `date` datetime DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
    // Infos complémentaires sur l'inscription à un evt : lieu de récupération, en lien avec une destination
    $queries[] =
        'ALTER TABLE `caf_evt_join` ADD `id_bus_lieu_destination` INT(11) UNSIGNED NULL DEFAULT NULL ;';
    $queries[] =
        'ALTER TABLE `caf_evt_join` ADD `id_destination` INT(11) UNSIGNED NULL DEFAULT NULL , ADD `is_covoiturage` TINYINT(1) UNSIGNED NULL DEFAULT NULL ;';
    // Utilisateur choisit d'aller au restaurant (facultatif : null (non proposé, 0, 1)
    $queries[] =
        'ALTER TABLE `caf_evt_join` ADD `is_restaurant` TINYINT(1) UNSIGNED NULL DEFAULT NULL ;';
    // table d'association sortie / destination
    $queries[] =
        'CREATE TABLE IF NOT EXISTS `caf_evt_destination` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `id_evt` int(11) unsigned NOT NULL,
          `id_destination` int(11) unsigned NOT NULL,
          `id_lieu_depose` int(11) unsigned DEFAULT NULL,
          `date_depose` datetime DEFAULT NULL,
          `id_lieu_reprise` int(11) unsigned DEFAULT NULL,
          `date_reprise` datetime DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

    $app_version = set_version_nb('1.1');
}

// Nouvelle version mineure : gestion du paiement en ligne

if ($app_version < '1.1.1') {
    /* Paiement en ligne */
    $queries[] =
        "ALTER TABLE `caf_evt` ADD `cb_evt` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `tarif_evt`;";

    // Utilisateur choisit de payer en ligne (facultatif : null (non proposé, 0, 1)
    $queries[] =
        'ALTER TABLE `caf_evt_join` ADD `is_cb` TINYINT(1) UNSIGNED NULL DEFAULT NULL ;';
    $app_version = set_version_nb('1.1');
}

// print_r($queries);

foreach ($queries as $query) {
    echo $query."\n";
    // Exécuter les requetes
    // if (!LegacyContainer::get('legacy_mysqli_handler')->query($query)) {
    // $errTab[] = 'Une erreur est apparue lors de la mise à jour de la base de données.';
    // }
}

if (!isset($errTab) || 0 === count($errTab)) {
    // ecrire le numéro de version
}
