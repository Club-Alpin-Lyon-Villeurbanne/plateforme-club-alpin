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
-- Contenu de la table `caf_article`
--

INSERT INTO `caf_article` (`id_article`, `status_article`, `status_who_article`, `topubly_article`, `tsp_crea_article`, `tsp_validate_article`, `tsp_article`, `tsp_lastedit`, `user_article`, `titre_article`, `code_article`, `commission_article`, `evt_article`, `une_article`, `cont_article`, `nb_vues_article`) VALUES
(1, 1, 1, 1, 1395307511, 1395308586, 1395307511, '2014-03-20 09:25:11', 1, 'Vestibulum iaculis cursus lacinia', 'vestibulum-iaculis-cursus-laci', 0, 0, 1, '<p>Quisque dignissim rhoncus arcu. Praesent felis velit, gravida vitae augue et, porta mollis massa. Suspendisse potenti. Suspendisse semper tellus est, vitae adipiscing dui egestas ut. Maecenas elementum non turpis quis posuere. Aliquam gravida quis velit id hendrerit. Aliquam cursus vel nunc non dictum. Cras at augue elementum, laoreet felis a, aliquam lorem. Curabitur rhoncus at urna vitae porttitor. Nunc molestie posuere enim, non pellentesque risus ornare eu. Nulla neque odio, tincidunt at diam eu, laoreet egestas ante.</p>', 0),
(2, 1, 1, 1, 1395308533, 1395308588, 1395308533, '2014-03-20 09:42:13', 1, 'Integer dignissim massa', 'integer-dignissim-massa', 0, 0, 1, '<p>In tincidunt, arcu quis suscipit pellentesque, orci metus feugiat sem, in imperdiet metus eros congue ipsum. Vestibulum vitae imperdiet tellus. Integer sem nisl, consequat id sem vitae, feugiat semper purus. Suspendisse viverra ligula sed nisl fermentum sollicitudin. Ut ullamcorper, mi quis tempor condimentum, tortor lacus porta arcu, sit amet gravida ipsum nulla non velit. Proin viverra scelerisque eleifend. Sed fringilla nisi accumsan metus tincidunt, ac tristique nisl dapibus. Proin quis eros sit amet libero hendrerit commodo eu vitae orci.</p>', 0),
(3, 1, 1, 1, 1395308578, 1395308590, 1395308578, '2014-03-20 09:42:58', 1, 'Aliquam pulvinar luctus dolor', 'aliquam-pulvinar-luctus-dolor', 0, 0, 1, '<p>Nulla facilisi. Cras egestas nisi eu tincidunt cursus. Vestibulum varius justo ac tincidunt dictum. Vestibulum eget justo mauris. Aliquam consectetur a felis vel luctus. Nullam vel libero vel tellus vehicula rutrum nec ac lacus. In at dapibus turpis. Curabitur eget auctor tellus, a dapibus elit. Duis fermentum metus ac commodo molestie. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Fusce tincidunt eu ante eget eleifend. Duis in dictum augue. Ut bibendum congue magna, sed posuere odio luctus non. Phasellus blandit molestie elit at accumsan. Vivamus quis sapien in magna aliquet elementum eu id elit.</p>', 0),
(4, 1, 1, 1, 1395308726, 1395308799, 1395308726, '2014-03-20 09:45:26', 1, 'Sed aliquam dapibus placerat', 'sed-aliquam-dapibus-placerat', 0, 0, 0, '<p>Quisque venenatis condimentum lorem, in blandit diam mattis vitae. Praesent ac nunc massa. Nunc rhoncus condimentum sodales. Nam sit amet ipsum est. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Suspendisse mattis massa lobortis volutpat volutpat. Duis urna neque, tincidunt id eleifend sit amet, rhoncus sit amet lorem. In ut massa leo. Phasellus egestas congue ullamcorper. Integer et justo a justo gravida interdum.</p>', 0),
(5, 1, 1, 1, 1395308761, 1395308801, 1395308761, '2014-03-20 09:46:01', 1, 'In non ultricies diam', 'in-non-ultricies-diam', 0, 0, 0, '<p>Curabitur ornare mi felis, sit amet malesuada tellus feugiat a. Maecenas nunc enim, tincidunt eu nibh sed, viverra viverra libero. Maecenas sed ante quis turpis ultricies dapibus. Praesent id pretium dolor. Duis et facilisis mauris, cursus adipiscing nibh. Vestibulum eleifend, neque vitae suscipit venenatis, eros libero facilisis erat, eget dictum dui mi sit amet nisi. Sed scelerisque lorem erat, ut viverra dolor porta et. Sed nec feugiat tellus. In enim elit, hendrerit at bibendum sit amet, faucibus eu orci.</p>', 0),
(6, 1, 1, 1, 1395308793, 1395308803, 1395308793, '2014-03-20 09:46:33', 1, 'Pellentesque urna neque', 'pellentesque-urna-neque', 0, 0, 0, '<p>Fusce condimentum sagittis ante, id laoreet sapien porta vel. Vestibulum consectetur blandit laoreet. Cras placerat nisi quis arcu pellentesque, eget iaculis ipsum fermentum. Aliquam eget mollis lacus, sit amet porttitor odio. Sed sit amet tellus ullamcorper nulla tempor adipiscing. Quisque porta, urna sed faucibus ultrices, lacus nisi bibendum ipsum, malesuada mollis libero nibh quis odio. Proin dui turpis, luctus ut nisi ac, ultrices bibendum odio.</p>', 0);

-- --------------------------------------------------------

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

--
-- Contenu de la table `caf_commission`
--

INSERT INTO `caf_commission` (`id_commission`, `ordre_commission`, `vis_commission`, `code_commission`, `title_commission`) VALUES
(1, 1, 1, 'sorties-familles', 'Sorties familles'),
(2, 2, 1, 'alpinisme', 'Alpinisme'),
(3, 3, 1, 'ski-alpin', 'Ski alpin / Snowboard'),
(4, 4, 1, 'ecole-d-aventure', 'École d''aventure'),
(5, 5, 1, 'ecole-d-escalade', 'École d''escalade'),
(6, 6, 1, 'ecole-de-ski', 'École de ski'),
(7, 7, 1, 'escalade', 'Escalade'),
(8, 8, 1, 'escalade-competition', 'Escalade compétition'),
(9, 9, 1, 'handicaf', 'Handicaf'),
(10, 10, 1, 'randonnee-pedestre', 'Randonnée pédestre'),
(11, 11, 1, 'randonnee-raquette', 'Randonnée raquette'),
(12, 12, 1, 'ski-de-randonnee', 'Ski de randonnée'),
(13, 13, 1, 'protection-de-la-montagne', 'Protection de la montagne'),
(14, 14, 1, 'formation', 'Formation');

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
-- Contenu de la table `caf_content_html`
--

INSERT INTO `caf_content_html` (`id_content_html`, `code_content_html`, `lang_content_html`, `contenu_content_html`, `date_content_html`, `linkedtopage_content_html`, `current_content_html`, `vis_content_html`) VALUES
(1, 'profil-infos', 'fr', '<p>Modifiez vos informations sur le site, et votre photo. Ne joignez aucun fichier image si vous souhaitez conserver la photo actuelle.</p>\r\n<p>Les <strong>informations publiques</strong> sont visibles par tous les visiteurs du site, votre pseudonyme vous aide à conserver l''anonymat.</p>\r\n<p>Les <strong>informations privées</strong> ne sont accessibles qu''au responsables du CAF et sont indispensables pour des raisons évidentes de sécurité, et pour vous identifier lors de la préparation des sorties. Les responsables des sorties valident les inscriptions en vérifiant votre profil, prenez donc soin de le tenir à jour.</p>\r\n<p>Enfin, pour sauvegarder l''ensemble des modifications entrées ci-dessous, cliquez sur le bouton <em>Enregistrer</em> en bas de page.</p>', 1378148481, '', 1, 1),
(2, 'mainmenu-creer-mon-compte', 'fr', '<p class="menutitle">Activer mon compte</p>\r\n<p>Pour rejoindre le site, vous devez être inscrit au Club Alpin Français.<br />Munissez-vous de votre numéro d''adhérent et de votre adresse e-mail, choisissez un peudonyme et un mot de passe, et laissez-vous guider.</p>', 1364203587, '', 1, 1),
(3, 'activer-profil', 'fr', '<p><span>Pour rejoindre le site, vous devez être inscrit au Club Alpin Français depuis plus de 3j.</span><br /><span>Munissez-vous de votre numéro d''adhérent : il est inscrit sur votre carte.</span></p>\r\n<p><span>Après avoir renseigné le formulaire ci-dessous, vous allez recevoir un mail de validation contenant un lien sur lequel il faudra cliquer.<br /><span style="color: red;">Vérifiez votre boite SPAM, il est probable que ce mail de validation s''y trouve.</span> Pour résoudre ce problème, déclarez préalablement l''adresse du site dans vos contacts : <img src="/img/adresse-website.png" alt="" width="208" height="20" />.<br /></span></p>', 1394046993, '', 1, 1),
(4, 'mainmenu-connection', 'fr', '<p class="menutitle">Connexion</p>', 1363258064, '', 1, 1),
(5, 'mdp-perdu', 'fr', '<h1>Alors comme ça, vous avez perdu votre mot de passe ?</h1>\r\n<p>Nous n''y avons pas accès nous-même, donc nous ne pouvons pas vous le renvoyer. Par contre vous pouvez le réinitialiser : <br />Entrez ci-dessous votre adresse e-mail, puis le nouveau mot de passe désiré. Vous recevrez un courrier avec un lien sur lequel cliquer pour confirmer le changement.</p>', 1372409085, '', 1, 1),
(6, 'presentation-splitboard', 'fr', '<h1>Splitboard</h1>\r\n<p>A splitboard is a snowboard that can be separated into two ski-like parts used with climbing skins to ascend slopes the same way alpine touring or telemark skis are. The two halves can then be connected to form a regular snowboard for descent.</p>', 1366625999, '', 1, 1),
(7, 'infos-lieu-de-rdv', 'fr', '<p>Ville, et adresse du lieu de RDV pour vous rendre à la sortie. Ce champ permet de placer automatiquement le point sur la carte.</p>', 1366703256, '', 1, 1),
(8, 'infos-tarifs', 'fr', '<p class="mini">Le champ <em>tarif</em> est facultatif ! Mais <span style="text-decoration: underline;">n''oubliez pas de préciser</span> dans le cadre «Description complète» si les membres devront participer aux frais de covoiturage : essence, péage...</p>', 1366703644, '', 1, 1),
(9, 'infos-carte', 'fr', '<p>Cliquez ci-dessous pour placer le point sur la carte, puis déplacez ce dernier sur le <span style="text-decoration: underline;">lieu exact du RDV</span>. Vous pouvez zoomer / dézoomer.</p>', 1366703757, '', 1, 1),
(10, 'infos-matos', 'fr', '<p>Note : Ci-dessus, les sauts de ligne seront remplacés par des virgules sur la page dédiée à la sortie.</p>', 1366719359, '', 1, 1),
(11, 'alerte-benevoles', 'fr', '<p>Nous recherchons encore des bénévoles pour cette sortie ! Pour être bénévole, inscrivez-vous ci-dessous et cochez la case correspondante.</p>', 1366787584, '', 1, 1),
(12, 'formalites-inscription', 'fr', '<h2>Note importante sur les inscriptions :</h2>\r\n<p>En envoyant votre demande d''inscription, vous n''êtes pas instantanément inscrit à la sortie : ce sont les responsables qui valident ou non votre participation, en fonction du nombre d''inscrits, du niveau des participants ou autres...</p>\r\n<p>Que votre inscription soit confirmée ou déclinée, vous recevrez un e-mail pour vous en avertir.</p>', 1366791192, '', 1, 1),
(13, 'formalites-inscription-suppression', 'fr', '<h2>Annuler votre inscription</h2>\r\n<p>Vous pouvez annuler instantanément votre inscription. Si celle-ci a été validée par l''organisateur, il recevra un e-mail pour l''en avertir.</p>', 1366794314, '', 1, 1),
(14, 'info-inscription-pas-encore', 'fr', '<hr />\r\n<h2>Inscriptions :</h2>\r\n<p>Les inscriptions pour cette sortie n''ont pas encore démarré.</p>', 1366806847, '', 1, 1),
(15, 'info-inscription-non-connecte', 'fr', '<hr />\r\n<h2>Inscriptions :</h2>\r\n<p>Vous devez avoir un compte sur le site pour vous inscrire aux sorties du CAF. Pour savoir comment nous rejoindre, rendez-vous dans la page « <a href="profil.html">activer mon compte</a> ».</p>', 1366901162, '', 1, 1),
(16, 'formalites-gestion-des-inscrits-evt-passe', 'fr', '<h2>Votre sortie est terminée</h2>\r\n<p>Vous pouvez toujours modifier les status des inscrits pour indiquer qu''ils ont -<em>ou non</em>- participé à l''événement. Si un membre était absent, passez-le en mode "<em>en attente</em>". Ceci est utile pour les statistiques du site.</p>\r\n<p>Cette fois, l''événement étant passé, aucun e-mail ne sera envoyé aux membres modifiés.</p>', 1366961792, '', 1, 1),
(17, 'infos-profil-statuts', 'fr', '<p>Vos statuts sont attribués par les responsables du site. Vous pouvez être encadrant pour une commission donnée, rédacteur, salarié etc... Vos statuts vous permettent d''accéder à davantage de possibilités sur le site.</p>', 1366964975, '', 1, 1),
(18, 'profil-sorties-prev', 'fr', '<h2><span class="bleucaf">&gt;</span> Retrouvez ici les sorties auxquelles vous avez participé</h2>', 1366968690, '', 1, 1),
(19, 'profil-sorties-next', 'fr', '<h2><span class="bleucaf">&gt;</span> Retrouvez ici les futures sorties auxquelles votre participation a été confirmée</h2>', 1366969996, '', 1, 1),
(20, 'annuler-une-sortie', 'fr', '<p>Voulez-vous vraiment annuler la sortie ci-dessous ? Celle-ci a déjà est publiée sur le site et des membres y sont inscrits, ils recevront donc automatiquement un e-mail pour les en avertir. Vous devez ajouter un message pour expliquer la raison de l''annulation :</p>', 1366979024, '', 1, 1),
(21, 'supprimer-une-sortie', 'fr', '<p>Supprimer définitivement la sortie et les fichiers liés. <strong>Attention</strong> : vous ne devriez jamais supprimer une sortie qui a été publiée sur le site car la conserver permet d''établir des statistiques, et l''URL de la page dédiée à cette sortie retournera une erreur si des utilisateurs souhaitent y retourner.</p>', 1367228553, '', 1, 1),
(22, 'profil-sorties-self', 'fr', '<h2><span class="bleucaf">&gt;</span> Retrouvez ci-dessous la liste des sorties que vous organisez, par ordre de création de la plus récente à la plus ancienne.</h2>', 1367229138, '', 1, 1),
(23, 'gestion-des-sorties-main', 'fr', '<p>Publiez les sorties en attente, ou demandez une modification à l''auteur d''une sortie avant de la diffuser sur le site.</p>', 1367229529, '', 1, 1),
(24, 'info-coredacteurs', 'fr', '<p>Vous pouvez partager la rédaction de cet article avec d''autre rédacteurs. Si vous souhaitez être relu, ou demander la participation d''autres membres dans la rédaction, décochez la case « <em>Demander la publication de cet article dès que possible </em>» jusqu''à ce que chacun ait participé efficacement.</p>', 1367327327, '', 1, 1),
(25, 'info-topubly-checkbox', 'fr', '<h2><span style="font-family: Verdana; font-size: 12px;">Les responsables du club doivent valider un article avant sa publication sur le site. Décochez la case ci-dessous si vous ne voulez pas diffuser votre article tout de suite, pour y apporter des modifications avant de le soumettre.</span></h2>\r\n<p><span style="font-family: Verdana; font-size: 12px;"> </span></p>', 1367328319, '', 1, 1),
(26, 'profil-sorties-', 'fr', '<p>Accédez à tous vos articles pour les suivre, les modifier, les retirer...</p>', 1367337704, '', 1, 1),
(27, 'recherche', 'fr', '<p>Utilisez le moteur de recherche à droite pour trouver des articles ou des sorties du club. Vous pouvez rechercher des mots clés dans les titres ou les contenus, mais aussi en tapant les pseudonymes des auteurs.</p>', 1367569944, '', 1, 1),
(28, 'commission-add', 'fr', '<p>Attention ! Les commissions sont l''épine dorsale du site, créer une commission se fait définitivement et sérieusement : les sorties, les droits des utilisateurs, les actualités etc... sont liés à celles-ci.</p>\r\n<p>Plusieurs images sont nécessaires à la création d''une commission :</p>\r\n<ul>\r\n<li>La grande image de fond</li>\r\n<li>Le picto bleu CAF</li>\r\n<li>Le picto clair</li>\r\n<li>Le picto sombre</li>\r\n</ul>\r\n<p>Nous vous invitons vivement à utiliser les fichiers de base à confier à votre infographiste :<br />&gt; <a href="./ftp/telechargements/commission-base.zip">commissions-base.zip</a></p>', 1367577892, '', 1, 1),
(29, 'commission-add-bigimg', 'fr', '<p>Celle-ci devrait avoir une dimension d''environ <strong>2050 * 905 pixels</strong>, et être <strong>optimisée pour le web</strong>. Le poids d''une telle image ne doit pas dépasser <strong>300 ko</strong> dans le pire des cas. </p>\r\n<p>L''image de fond doit être <strong>très claire</strong> pour maintenir la <strong>cohérence</strong> graphique du site, et assurer son <strong>ergonomie</strong>. Seule une <strong>personne compétente</strong> doit la créer, le modèle contenu dans le fichier .zip est un bon exemple. <strong>Une simple photo est à proscrire absolument</strong>.</p>\r\n<p>Cette image n''est pas redimensionnée ni retouchée lors de l''envoi.</p>', 1367576653, '', 1, 1),
(30, 'commission-add-nom', 'fr', '<p>Pour des raisons techniques et graphiques, le nom de la commission est limité à 25 caractères. Utilisez un nom <strong>court</strong>.</p>', 1367582197, '', 1, 1),
(31, 'commission-add-pictos', 'fr', '<p>Chaque pictogramme doit être en <strong>.png</strong> transparent, et avoir pour dimensions <strong>35px * 35px</strong>. <br />Ils doivent être rigoureusement identiques, excepté leur couleur.<br />Voici les codes couleur hexadécimaux de chacun :</p>', 1367587140, '', 1, 1),
(32, 'gestion-des-commissions', 'fr', '<p>Activez, désactivez, réorganisez ou modifiez les commissions. Les cadres rayés représentent les commissions désactivées, invisibles sur le site.</p>\r\n<p><strong>Attention :</strong> les commissions sont l''épine dorsale du site. Toute désactivation d''une commission peut avoir des conséquences considérable sur le fonctionnement du site. Les <strong>sorties liées peuvent disparaître</strong>, certains liens peuvent <strong>renvoyer une erreur</strong> !</p>\r\n<p>Utilisez la flèches pour glisser / déposer les commissions dans l''ordre voulu. Ceci modifie l''ordre d''apparition des commissions dans le menu supérieur du site. L''enregistrement du nouvel ordre est automatique et instantané.</p>\r\n<p>Pour créer une nouvelle commission, commencez par créer les images nécessaires à partir <a title="Télécharger le .zip contenant les fichiers utiles" href="./ftp/telechargements/commission-base.zip">du pack graphique</a>, puis rendez-vous <a title="Page de création, si vous disposez des droits nécessaires." href="commission-add.html">ici</a>.</p>', 1367824510, '', 1, 1),
(33, 'info-activer-commission', 'fr', '<p>Activer cette commission la rendra visible pour les visiteurs. <strong>Attention :</strong> il est compliqué de désactiver par la suite une commission activée, dès lors que des sorties ou des articles y ont été liés !</p>', 1367594051, '', 1, 1),
(34, 'info-desactiver-commission', 'fr', '<p><strong>Attention :</strong> désactiver une commission qui a un peu de vécu est déconseillé : les sorties liées disparaîtront, et certains liens existants pourraient renvoyer des erreurs.</p>', 1367594532, '', 1, 1),
(35, 'infos-supprimer-mon-commentaire', 'fr', '<p>Ce commentaire n''apparaîtra plus sur le site. Cete opération est définitive.</p>', 1367912250, '', 1, 1),
(36, 'infos-supprimer-any-commentaire', 'fr', '<p>Ce commentaire n''est <strong>pas lié à votre compte</strong>, si vous voyez ce message apparaître c''est que vous disposez d''un droit d''administration spécifique.</p>\r\n<p>L''auteur du commentaire <strong>ne sera pas averti</strong> automatiquement de cette action, vous pouvez donc choisir de le contacter prélablement pour l''en informer. Pour ceci, cliquez sur <em>Annuler</em>, puis sur le nom de l''auteur, et <em>contacter</em>.</p>', 1367912622, '', 1, 1),
(37, 'info-inscription-passee', 'fr', '<p>Les inscriptions pour cette sortie sont terminées...</p>', 1367913638, '', 1, 1),
(38, 'infos-cycle', 'fr', '<p>Dans un cycle, les membres <span style="text-decoration: underline;">s''inscrivent une seule fois pour l''ensemble des sorties</span>.</p>\r\n<p>Si vous désirez permettre aux membres de venir librement à une sortie et non une autre, ne créez pas de cycle mais précisez simplement que chaque sortie fait partie de la même série, dans la description complète.</p>\r\n<p>Pour créer un nouveau cycle, cochez la seconde case. Après avoir enregistré cette sortie, vous pourrez saisir la sortie suivante de ce cycle, et ainsi de suite...</p>', 1367931576, '', 1, 1),
(39, 'status-legal-2', 'fr', '<p><img style="float: left; margin-right: 5px;" title="Non validée" src="./ftp/images/valid_president_2.png" alt="Non validée" width="24" height="24" />Cette sortie <strong>n''est pas validée</strong> par le Club Alpin Français. La responsabilité du club n''est pas engagée en cas d''incident.</p>', 1367935963, '', 1, 1),
(40, 'status-legal-1', 'fr', '<p><img style="float:left; margin-right: 5px;" title="Validée" src="./ftp/images/valid_president_1.png" alt="Validée" width="24" height="24" />Validation du président :<br />Cette sortie est officiellement validée par le CAF ! </p>', 1367936051, '', 1, 1),
(41, 'status-legal-0', 'fr', '<p><img style="float: left; margin-right: 5px;" title="En attente" src="./ftp/images/valid_president_0.png" alt="En attente" width="24" height="24" />Validation en attente :<br />Cette sortie n''a <strong>pas encore</strong> été validée par les responsables du CAF.</p>', 1367936127, '', 1, 1),
(42, 'info-inscription-nieme-cycle', 'fr', '<hr />\r\n<h2>Inscriptions :</h2>\r\n<p>Cette sortie est la suite d''un cycle. Pour vous inscrire ou voir les membres inscrits, rendez-vous<br /> sur la pagée dédiée à la première sortie du site :</p>', 1367938368, '', 1, 1),
(43, 'commission-edit', 'fr', '<p>Attention ! Les commissions sont l''épine dorsale du site, les sorties, les droits des utilisateurs, les actualités etc... sont liés à celles-ci.</p>\r\n<p>Plusieurs images sont nécessaires à la commission :</p>\r\n<ul>\r\n<li>La grande image de fond</li>\r\n<li>Le picto bleu CAF</li>\r\n<li>Le picto clair</li>\r\n<li>Le picto sombre</li>\r\n</ul>\r\n<p>Nous vous invitons vivement à utiliser les fichiers de base à confier à votre infographiste :<br />&gt; <a href="./ftp/telechargements/commission-base.zip">commissions-base.zip</a></p>\r\n<p>Pour conserver une image existante, laissez simplement le champ vide.</p>', 1371129279, '', 1, 1),
(44, 'nouvel-article-info-photo', 'fr', '<p>L''image est redimensionnée dans les proportions indiquées ci-contre. Prenez soin de choisir des photographies horizontales, et jamais de textes ni de logos qui seraient tronqués de façon inesthétique.</p>\r\n<p>Vous pourrez ajouter toutes sortes d''images dans le corps de l''article plus bas.</p>', 1371198905, '', 1, 1),
(45, 'gestion-des-articles-main', 'fr', '<p>Publiez ou refusez les articles proposés par les membres autorisés. L''auteur reçoit un e-mail pour l''avertir de votre choix.</p>', 1371202171, '', 1, 1),
(46, 'validation-des-sorties-main', 'fr', '<p>Ces sorties sont publiées sur le site. Vous pouvez les valider comme sortie officielle du CAF, ou bien refuser de les afficher en tant que telles. Ceci n''annulera pas la sortie pour autant, libre à vous de contacter l''organisateur. Les boutons de validation / refus se trouvent <em>uniquement</em> sur la page dédiée à la sortie.</p>\r\n<p>  </p>', 1372317113, '', 1, 1),
(47, '404', 'fr', '<p style="text-align: center;"> </p>\r\n<h2 style="text-align: center;">404</h2>\r\n<p style="text-align: center;">Désolé, cette page est introuvable. Vous avez peut-être suivi un lien obsolète en dehors de ce site ? <br />Sinon, si c''est un lien dans ce site qui provoque cette erreur, merci de nous contacter.</p>\r\n<p style="text-align: center;">Bref, <a href="./">revenir à l''accueil</a> ?</p>', 1372346199, '', 1, 1),
(48, 'formalites-gestion-des-inscrits', 'fr', '<h2>Vous êtes organisateur / encadrant de cette sortie ?</h2>\r\n<p>C''est ici que vous pouvez gérer les inscriptions liées à cette sortie. Sélectionnez le statut de chaque demande d''inscription. Attention, chaque utilisateur dont le statut est modifié recevra un e-mail pour l''en avertir.</p>\r\n<p>Les modification s''appliquent seulement une fois que vous avez cliqué sur "Enregistrer" en bas du tableau.</p>\r\n<p>Par défaut, les entrées sont affichées dans l''ordre chronologique mais vous pouvez les trier par nom, rôle, statut... en cliquant sur le sommet de chaque colonne.</p>', 1372426621, '', 1, 1),
(49, 'info-inscription-moins-deux-jours', 'fr', '<h2>Inscriptions terminées</h2>\r\n<p>Cet événement a lieu dans moins de 1 jour. Les inscriptions en ligne sont terminées. Contactez l''organisateur pour en savoir plus.</p>', 1374657469, '', 1, 1),
(50, 'inscrire-filiation-select', 'fr', '<h2>Qui désirez-vous inscrire ?</h2>\r\n<p>Cochez ci-dessous les membres concernés par cette inscription. Note importante : si vous inscrivez un membre du club, c''est vous qui serez le référent de son inscription, et vous recevrez les e-mails informatifs liés à la sortie.</p>', 1375089512, '', 1, 1),
(51, 'infos-profil-filiation-parent', 'fr', '<p>Vous êtes affilié à un membre du CAF : cet utilisateur peut vous inscrire lui-même à des sorties.</p>', 1375919892, '', 1, 1),
(52, 'infos-profil-coordonnees-perso-ffcam', 'fr', '<p>Les informations ci-dessous sont celles communiquées lors de votre inscription au club et communiquées à la fédération. Merci de faire attention à les mettre à jour.<br />Pour les modifier, merci d''envoyer un <a class="mailthisanchor"></a><script type="text/javascript" class="mailthis">mailThis(''secretariat'', ''caf'', ''com'', '''', ''mail au secr&eacute;tariat en cliquant ici'');</script>.</p>', 1378241555, '', 1, 1),
(53, 'mainfooter-1', 'fr', '<h2>Les différentes commissions<br />du Club Alpin</h2>', 1375973545, '', 1, 1),
(54, 'responsables', 'fr', '<h1>Responsables du Club Alpin par commission</h1>', 1375973618, '', 1, 1),
(55, 'infos-profil-filiation-enfants', 'fr', '<p>Les adhérents ci-dessous sont liés à votre compte. Vous pouvez les inscrire vous-même à des sorties.</p>', 1378243454, '', 1, 1),
(56, 'alerte-licence-renouveler', 'fr', '<h2>Attention :</h2>\r\n<p>Votre licence au Club Alpin expire au 30 septembre.<br />D''ici au 31 octobre, vous pouvez participer aux sorties organisées par le club (vous êtes couvert par l''assurance).<br /><span style="text-decoration: underline;">Pensez à renouveler votre adhésion</span>, au local, ou en ligne.<br />Dans le cas contraire, votre compte sera désactivé le 31 décembre.</p>', 1378279848, '', 1, 1),
(57, 'mainfooter-2', 'fr', '<h2>Pour nous contacter</h2>\r\n<p>Nos locaux sont situés :</p>\r\n<p>adresse<br />code postal ville <br />Tel : (33) 04-XX-XX-XX-XX</p>\r\n<p>Courriel : info@caf.com</p>\r\n<p><strong>PERMANENCE :</strong></p>\r\n<p>Nous vous accueillons :</p>\r\n<h3><strong>&gt; du mardi au jeudi de 16h30 à 19h</strong></h3>\r\n<h3><strong>&gt; le vendredi de 17h à 19h30</strong></h3>', 1381909327, '', 1, 1),
(58, 'info-inscription-licence-obsolete', 'fr', '<p> </p>\r\n<h2>Inscription verrouillée</h2>\r\n<p class="erreur">Vous ne pouvez pas vous inscrire à cette sortie car votre licence semble avoir expirée.</p>', 1383604281, '', 1, 1),
(59, 'alerte-licence-obsolete', 'fr', '<h1>Attention, votre licence du club alpin semble ne plus être à jour !</h1>\r\n<p> </p>\r\n<p>Vous ne pourrez pas accéder à certaines options du site !</p>\r\n<p>Si votre licence a été renouvelée récemment, ce message devrait disparaître prochainement.</p>', 1383604411, '', 1, 1),
(60, 'info-encadrant-licence-obsolete', 'fr', '<h1>Attention, votre licence du club alpin semble ne plus être à jour !</h1>\r\n<p> </p>\r\n<p>Vous ne pourrez pas accéder à certaines options du site !</p>\r\n<p>Si votre licence a été renouvelée récemment, ce message devrait disparaître prochainement.</p>', 1383678122, '', 1, 1),
(61, 'explication-nomades', 'fr', '<h2>C''est quoi un «nomade» ? </h2>\r\n<p>Vous pouvez inscrire à une sortie une personne qui ne fait  pas partie du club.</p>\r\n<p><strong>Ne pas ajouter un adhérent</strong>, utiliser l''inscription manuelle<strong>.<br /></strong><br />Ceci a pour effet de l''ajouter à la fiche de sortie, mais vous ne devez pas oublier de la contacter par téléphone pour lui rappeler que la sortie a lieu.</p>\r\n<p>Tous les e-mails automatiques normalement envoyés par le site ne sont pas envoyés à un membre nomade.</p>', 1384119777, '', 1, 1),
(62, 'mainfooter-3', 'fr', '<h2><a href="pages/mentions-legales.html">Mentions légales</a></h2>\r\n<p>©Les contenus de ce site, images et textes, appartiennent au Club Alpin Français et ne peuvent être réutilisés sans l''accord de leurs auteurs respectifs.<br /><a href="pages/mentions-legales.html">[en savoir plus]</a></p>\r\n<p><a title="Création de sites internet à Chambéry, Savoie" href="http://www.herewecom.fr/" target="_blank">Site Internet réalisé par Herewecom</a></p>\r\n<hr />\r\n<p><span style="font-size: small;">Retrouvez-nous sur les réseaux sociaux<br /><a href="https://fr-fr.facebook.com/club.alpin.francais" target="_blank"><img src="/img/social/facebook.png" alt="Facebook" width="30" height="30" /></a></span> <a href="https://twitter.com/CAF" target="_blank"><img src="/img/social/twitter.png" alt="" width="30" height="30" /></a></p>', 1393961161, '', 1, 1),
(64, 'nav-menu-1', 'fr', '<p class="menutitle">Le club</p>\n<ul>\n<li><a href="pages/presentation.html">Présentation</a></li>\n<li><a href="pages/presentation.html">Pourquoi et comment adhérer ?</a></li>\n<li><a href="pages/presentation.html">Qui fait quoi ?</a></li>\n<li><a href="pages/presentation.html">Bénévoles au CAF</a></li>\n<li><a href="pages/presentation.html">Nos partenaires publics</a></li>\n<li><a href="pages/presentation.html">Nos partenaires privés</a></li>\n<li><a href="pages/presentation.html" target="_blank">Les responsables du club</a></li>\n</ul>', 1395756507, '', 1, 1),
(65, 'nav-menu-2', 'fr', '<p class="menutitle">ACTIVITÉS ESTIVALES</p>\n<ul>\n<li><a href="pages/alpinisme.html">Alpinisme</a></li>\n<li><a href="pages/alpinisme.html">Escalade</a></li>\n<li><a href="pages/alpinisme.html">Escalade de compétition</a></li>\n<li><a href="pages/alpinisme.html">Randonnée pédestre</a></li>\n</ul>\n<p> </p>\n<p class="menutitle">ACTIVITÉS HIVERNALES :</p>\n<ul>\n<li><a href="pages/alpinisme.html">Alpinisme / Cascade de glace</a></li>\n<li><a href="pages/alpinisme.html">Randonnée raquette</a></li>\n<li><a href="pages/alpinisme.html">Ski alpin / Snowboard</a></li>\n<li><a href="pages/alpinisme.html">Ski de randonnée</a></li>\n<li><a href="pages/alpinisme.html">Ski de fond</a></li>\n</ul>', 1395756750, '', 1, 1),
(66, 'presentation-general', 'fr', '<p>Cras <strong><span class="bleucaf">eget lorem nec ante luctus</span></strong> suscipit vitae id turpis. Vestibulum semper, massa eu facilisis volutpat, tortor urna sollicitudin est, eu rhoncus nibh felis sed nulla.</p>\n<p>Cras ullamcorper ornare ante, eu luctus elit consectetur in. Ut convallis tempor ante varius pulvinar.</p>\n<p><strong><span class="bleucaf">Nunc eu mauris ligula :</span></strong></p>\n<ul>\n<li>Phasellus purus risus,</li>\n<li>Volutpat vitae molestie eu,</li>\n<li>Sagittis non diam.</li>\n</ul>\n<p> </p>\n<h3><strong>&gt; Donec nec feugiat nibh</strong></h3>\n<h3><strong>&gt; Morbi dictum, nulla at pulvinar viverra</strong></h3>\n<h3><strong>&gt; Felis orci semper mauris, quis condimentum sapien nulla id sem</strong></h3>\n<h3><strong>&gt; Mauris sit amet dui diam. Phasellus at egestas mi, ac luctus sem</strong></h3>\n<p>Curabitur eu mauris porta, commodo nisl sed, luctus nunc. Integer sit amet tortor sed nisi molestie hendrerit in eu ante. Ut a commodo nulla. Mauris bibendum magna eu metus eleifend, in suscipit ante congue. Quisque quis fringilla magna. Sed fringilla, tortor eget aliquet tristique, nibh risus euismod est, ac ornare erat risus vel mi.</p>', 1395826045, '', 1, 1),
(67, 'main-pagelibre-44', 'fr', '<div id="lipsum">\r\n<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas mollis odio et mauris sodales, non dapibus ligula auctor. Vestibulum varius urna non nisi fringilla sollicitudin. Maecenas semper ante sed erat fermentum semper. Suspendisse nisi mi, faucibus ac dui vitae, ornare posuere ligula. Donec congue nec massa vitae pretium. Mauris quam dolor, eleifend ut mattis sit amet, tempus at libero. Proin tempus, justo sed vestibulum venenatis, neque quam tristique sem, nec porta lorem augue id metus. Sed pharetra leo sit amet dui posuere convallis. Praesent semper pellentesque tristique.</p>\r\n<p>Nam aliquet porttitor purus, vitae euismod risus tempus nec. Sed iaculis pharetra turpis, a bibendum justo rutrum in. Integer ullamcorper imperdiet lacus, a rutrum mi faucibus id. Sed quam est, gravida id faucibus ac, suscipit eget lacus. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Vestibulum diam est, aliquet ut vestibulum eu, porttitor sit amet justo. Nam quis semper tellus. Sed volutpat eu tortor non condimentum. Nam vestibulum nibh sed bibendum faucibus.</p>\r\n<p>Praesent vitae velit at nisi convallis sodales sed eu purus. Ut ultricies sapien sit amet augue venenatis aliquet. Nam ornare est ut leo imperdiet ornare quis ut diam. Morbi quis luctus nibh, id volutpat enim. Pellentesque non ornare est. Aliquam enim lectus, mollis vitae cursus quis, euismod nec eros. Nunc non urna quis sem pharetra porttitor facilisis quis nisl. Aliquam nec mauris non nibh interdum condimentum. Vivamus suscipit ante imperdiet felis volutpat viverra. Suspendisse porta, turpis id consequat venenatis, leo nibh ultricies est, a tristique lacus elit at neque.</p>\r\n<p>Fusce sodales lacus euismod nibh fermentum fermentum. Curabitur felis ligula, cursus ut pellentesque vel, blandit non elit. Donec faucibus dignissim erat ut tempus. Sed tortor mauris, elementum vel bibendum consequat, sagittis feugiat turpis. Praesent elementum erat eu erat imperdiet semper. Integer sed vestibulum dui. Donec pretium eu urna id fermentum. Suspendisse elit diam, tempus ac vehicula a, mattis ut magna. Morbi convallis nibh ac neque gravida laoreet ut in risus. Aliquam sit amet ante urna. Nulla id tempor eros, non vestibulum purus. Aliquam magna arcu, gravida vel dictum fringilla, porta a justo. Proin ultrices viverra augue sit amet fringilla. In posuere vestibulum congue.</p>\r\n<p>Praesent enim turpis, ultrices vulputate arcu ac, aliquam vestibulum mauris. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Nunc scelerisque, mi in dapibus commodo, mauris enim tempus justo, vitae vestibulum lorem odio et nulla. Vivamus fermentum aliquet turpis ut cursus. Nam sit amet tellus auctor, bibendum ligula eu, feugiat mi. Praesent vulputate risus id nibh elementum dapibus. Sed ultricies nunc metus. Phasellus interdum sollicitudin dolor, at eleifend diam luctus ut. Vestibulum venenatis vel eros sit amet rhoncus. Integer laoreet accumsan tellus, vitae vulputate neque bibendum a. Integer lacinia nisl at pretium molestie. Integer eget enim vitae diam rutrum ultricies. Donec at consequat dolor. Aenean sed ante vel risus viverra eleifend.</p>\r\n</div>', 1395912963, '', 1, 1),
(68, 'main-pagelibre-43', 'fr', '<div id="lipsum">\r\n<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas mollis odio et mauris sodales, non dapibus ligula auctor. Vestibulum varius urna non nisi fringilla sollicitudin. Maecenas semper ante sed erat fermentum semper. Suspendisse nisi mi, faucibus ac dui vitae, ornare posuere ligula. Donec congue nec massa vitae pretium. Mauris quam dolor, eleifend ut mattis sit amet, tempus at libero. Proin tempus, justo sed vestibulum venenatis, neque quam tristique sem, nec porta lorem augue id metus. Sed pharetra leo sit amet dui posuere convallis. Praesent semper pellentesque tristique.</p>\r\n<p>Nam aliquet porttitor purus, vitae euismod risus tempus nec. Sed iaculis pharetra turpis, a bibendum justo rutrum in. Integer ullamcorper imperdiet lacus, a rutrum mi faucibus id. Sed quam est, gravida id faucibus ac, suscipit eget lacus. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Vestibulum diam est, aliquet ut vestibulum eu, porttitor sit amet justo. Nam quis semper tellus. Sed volutpat eu tortor non condimentum. Nam vestibulum nibh sed bibendum faucibus.</p>\r\n<p>Praesent vitae velit at nisi convallis sodales sed eu purus. Ut ultricies sapien sit amet augue venenatis aliquet. Nam ornare est ut leo imperdiet ornare quis ut diam. Morbi quis luctus nibh, id volutpat enim. Pellentesque non ornare est. Aliquam enim lectus, mollis vitae cursus quis, euismod nec eros. Nunc non urna quis sem pharetra porttitor facilisis quis nisl. Aliquam nec mauris non nibh interdum condimentum. Vivamus suscipit ante imperdiet felis volutpat viverra. Suspendisse porta, turpis id consequat venenatis, leo nibh ultricies est, a tristique lacus elit at neque.</p>\r\n<p>Fusce sodales lacus euismod nibh fermentum fermentum. Curabitur felis ligula, cursus ut pellentesque vel, blandit non elit. Donec faucibus dignissim erat ut tempus. Sed tortor mauris, elementum vel bibendum consequat, sagittis feugiat turpis. Praesent elementum erat eu erat imperdiet semper. Integer sed vestibulum dui. Donec pretium eu urna id fermentum. Suspendisse elit diam, tempus ac vehicula a, mattis ut magna. Morbi convallis nibh ac neque gravida laoreet ut in risus. Aliquam sit amet ante urna. Nulla id tempor eros, non vestibulum purus. Aliquam magna arcu, gravida vel dictum fringilla, porta a justo. Proin ultrices viverra augue sit amet fringilla. In posuere vestibulum congue.</p>\r\n<p>Praesent enim turpis, ultrices vulputate arcu ac, aliquam vestibulum mauris. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Nunc scelerisque, mi in dapibus commodo, mauris enim tempus justo, vitae vestibulum lorem odio et nulla. Vivamus fermentum aliquet turpis ut cursus. Nam sit amet tellus auctor, bibendum ligula eu, feugiat mi. Praesent vulputate risus id nibh elementum dapibus. Sed ultricies nunc metus. Phasellus interdum sollicitudin dolor, at eleifend diam luctus ut. Vestibulum venenatis vel eros sit amet rhoncus. Integer laoreet accumsan tellus, vitae vulputate neque bibendum a. Integer lacinia nisl at pretium molestie. Integer eget enim vitae diam rutrum ultricies. Donec at consequat dolor. Aenean sed ante vel risus viverra eleifend.</p>\r\n</div>', 1395912988, '', 1, 1);
-- --------------------------------------------------------

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
-- Contenu de la table `caf_content_inline`
--

INSERT INTO `caf_content_inline` (`id_content_inline`, `groupe_content_inline`, `code_content_inline`, `lang_content_inline`, `contenu_content_inline`, `date_content_inline`, `linkedtopage_content_inline`) VALUES
(1, 1, 'operation-en-cours', 'fr', 'Opération en cours, veuillez patienter', 1360938899, '0'),
(2, 1, 'news-title', 'fr', 'Actualités - Club Alpin Français', 1363685099, '0'),
(3, 1, 'logo-title', 'fr', 'Toutes les commissions, sorties et actus du CAF', 1363251905, '0'),
(4, 1, 'userlink-title', 'fr', 'Voir le profil', 1363685099, '0'),
(5, 2, 'meta-title-404', 'fr', '404 : Page introuvable', 1360938916, '0'),
(6, 4, 'mainmenu-accueil', 'fr', 'Accueil', 1361203020, '0'),
(7, 4, 'mainmenu-contact', 'fr', 'Contact', 1361203030, '0'),
(8, 2, 'meta-title-nouvelle-page-libre', 'fr', 'Nouvelle page libre', 1361444652, ''),
(9, 2, 'meta-title-nouvelle-page-libre-2', 'fr', 'Nouvelle page libre 2', 1361444760, ''),
(10, 2, 'meta-title-mot-de-passe-perdu', 'fr', 'Mot de passe perdu ?', 1361787615, ''),
(11, 2, 'meta-title-profil', 'fr', 'Mon profil', 1361807929, ''),
(12, 2, 'meta-title-user-confirm', 'fr', 'Confirmation de votre compte', 1361809925, ''),
(13, 2, 'meta-title-infos', 'fr', 'Mes infos', 1361871889, ''),
(14, 2, 'meta-title-sorties', 'fr', 'Mes sorties', 1361871929, ''),
(15, 2, 'meta-title-articles', 'fr', 'Mes articles', 1361871961, ''),
(16, 2, 'meta-title-photos', 'fr', 'Mes photos', 1361871989, ''),
(17, 2, 'meta-title-filiation', 'fr', 'Filiation', 1361872008, ''),
(18, 2, 'meta-title-creer-une-sortie', 'fr', 'Créer une sortie', 1361888945, ''),
(19, 5, 'site-meta-description', 'fr', 'Site officiel du Club Alpin : activités sportives été et hiver, alpinisme, randonnée, ski, refuges...', 1363251741, '0'),
(20, 2, 'meta-title-accueil', 'fr', 'Club Alpin Français', 1363251757, '0'),
(21, 2, 'meta-title-pages', 'fr', 'Club Alpin Français', 1363251770, '0'),
(22, 2, 'meta-title-gestion-des-sorties', 'fr', 'Gestion des sorties', 1363251782, '0'),
(23, 2, 'meta-title-contact', 'fr', 'Contact - Club Alpin Français', 1363251879, '0'),
(24, 2, 'meta-title-sortie', 'fr', 'Sortie', 1363686532, ''),
(25, 2, 'meta-title-supprimer-une-sortie', 'fr', 'Supprimer une sortie', 1363885698, '0'),
(26, 2, 'meta-title-annuler-une-sortie', 'fr', 'Annuler une sortie', 1363885814, '0'),
(27, 2, 'meta-title-adherents', 'fr', 'Liste des adhérents', 1363885832, '0'),
(28, 2, 'meta-title-adherents-creer', 'fr', 'Créer un adhérent', 1363958472, '0'),
(29, 2, 'site-meta-title', 'fr', 'Club Alpin Français', 1365692406, '0'),
(30, 2, 'meta-title-agenda', 'fr', 'Agenda des sorties - Club Alpin Français', 1365692434, '0'),
(31, 2, 'meta-title-mentions-legales', 'fr', 'Mentions légales - Club Alpin Français', 1366031027, ''),
(32, 2, 'meta-title-responsables', 'fr', 'Responsables des commissions', 1366988075, '0'),
(33, 2, 'meta-title-article-new', 'fr', 'Nouvel article / Modifier un article', 1367241422, '0'),
(34, 2, 'meta-title-article-edit', 'fr', 'Modifier un article', 1367481427, '0'),
(35, 2, 'meta-title-article', 'fr', 'Article', 1367481435, '0'),
(36, 2, 'meta-title-gestion-des-articles', 'fr', 'Gestion des articles', 1367498922, '0'),
(37, 2, 'meta-title-recherche', 'fr', 'Votre recherche', 1367564986, '0'),
(38, 2, 'meta-title-commission-add', 'fr', 'Créer une nouvelle commission', 1367574304, '0'),
(39, 2, 'meta-title-gestion-des-commissions', 'fr', 'Gestion des commissions', 1367586790, '0'),
(40, 2, 'meta-title-commission-edit', 'fr', 'Modifier une commission', 1371129302, '0'),
(41, 2, 'meta-title-validation-des-sorties', 'fr', 'Validation des sorties - Club Alpin Français', 1371827573, '0'),
(42, 2, 'meta-title-stats', 'fr', 'Statistiques', 1372679761, '0'),
(43, 2, 'meta-title-fichier-adherents', 'fr', 'Mise à jour du fichier adhérent', 1374221667, '0'),
(44, 2, 'meta-title-user-full', 'fr', 'Fiche adhérent', 1374226913, '0');

-- --------------------------------------------------------

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
-- Contenu de la table `caf_content_inline_group`
--

INSERT INTO `caf_content_inline_group` (`id_content_inline_group`, `ordre_content_inline_group`, `nom_content_inline_group`) VALUES
(1, 1, 'Ensemble du site'),
(2, 2, 'Titre des pages'),
(3, 3, 'Navigation'),
(4, 4, 'Descriptions des pages');

-- --------------------------------------------------------

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
-- Structure de la table `caf_ftp_allowedext`
--

CREATE TABLE IF NOT EXISTS `caf_ftp_allowedext` (
  `id_ftp_allowedext` int(11) NOT NULL AUTO_INCREMENT,
  `ext_ftp_allowedext` varchar(6) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id_ftp_allowedext`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=24 ;

--
-- Contenu de la table `caf_ftp_allowedext`
--

INSERT INTO `caf_ftp_allowedext` (`id_ftp_allowedext`, `ext_ftp_allowedext`) VALUES
(1, 'jpg'),
(2, 'gif'),
(3, 'jpeg'),
(4, 'png'),
(5, 'doc'),
(6, 'docx'),
(7, 'odt'),
(8, 'pdf'),
(9, 'avi'),
(10, 'mov'),
(11, 'mp3'),
(12, 'rar'),
(13, 'zip'),
(14, 'txt'),
(15, 'xls'),
(16, 'csv'),
(17, 'ppt'),
(18, 'pptx'),
(19, 'ai'),
(20, 'psd'),
(21, 'fla'),
(22, 'swf'),
(23, 'eps');

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
-- Contenu de la table `caf_page`
--

INSERT INTO `caf_page` (`id_page`, `parent_page`, `admin_page`, `superadmin_page`, `vis_page`, `ordre_page`, `menu_page`, `ordre_menu_page`, `menuadmin_page`, `ordre_menuadmin_page`, `code_page`, `default_name_page`, `meta_title_page`, `meta_description_page`, `priority_page`, `add_css_page`, `add_js_page`, `lock_page`, `pagelibre_page`, `created_page`) VALUES
(1, 0, 1, 1, 1, 0, 0, 0, 1, 0, 'admin-pages', 'Pages & arborescence', 1, 0, 0.0, '', '', 1, 0, 0),
(2, 0, 1, 1, 1, 0, 0, 0, 1, 1, 'admin-contenus', 'Contenus textuels', 1, 0, 0.0, '', '', 0, 0, 0),
(3, 0, 1, 0, 1, 0, 0, 0, 1, 2, 'admin-traductions', 'Traduction des contenus', 1, 0, 0.0, '', '', 0, 0, 0),
(4, 0, 1, 0, 1, 0, 0, 0, 1, 5, 'admin-pages-libres', 'Pages libres', 1, 0, 0.0, '', '', 0, 0, 0),
(5, 0, 1, 0, 1, 0, 0, 0, 1, 1, 'admin-matrice-droits', 'Matrice des droits', 1, 0, 0.0, '', '', 0, 0, 0),
(6, 0, 1, 0, 1, 0, 0, 0, 1, 5, 'admin-users', 'Gestion des membres / droits', 1, 0, 0.0, '', '', 0, 0, 0),
(7, 0, 0, 0, 1, 0, 0, 0, 0, 0, '404', 'Erreur 404', 0, 0, 0.0, '', '', 1, 0, 0),
(8, 0, 0, 0, 1, 1, 1, 0, 0, 0, 'accueil', 'Accueil', 0, 0, 0.5, '', '/js/accueil.js', 1, 0, 0),
(9, 0, 0, 0, 1, 0, 1, 5, 0, 0, 'contact', '', 0, 0, 0.0, '', '', 0, 0, 0),
(10, 0, 0, 0, 1, 0, 0, 0, 0, 0, 'pages', '', 0, 0, 0.0, '', '', 0, 0, 0),
(11, 11, 0, 0, 1, 76, 0, 76, 0, 0, 'mentions-legales', 'Mentions légales du CAF', 0, 0, 0.0, '', '', 0, 1, 1366031027),
(12, 0, 0, 0, 1, 56, 0, 56, 0, 0, 'mot-de-passe-perdu', '', 0, 0, 0.0, '', '', 0, 0, 0),
(13, 0, 0, 0, 1, 57, 0, 57, 0, 0, 'profil', '', 0, 0, 0.0, '', '', 0, 0, 0),
(14, 0, 0, 0, 1, 58, 0, 58, 0, 0, 'user-confirm', '', 0, 0, 0.0, '', '', 0, 0, 0),
(15, 13, 0, 0, 1, 59, 0, 59, 0, 0, 'infos', '', 0, 0, 0.0, '', '', 0, 0, 0),
(16, 13, 0, 0, 1, 61, 0, 61, 0, 0, 'sorties', '', 0, 0, 0.0, '', '', 0, 0, 0),
(17, 13, 0, 0, 1, 63, 0, 63, 0, 0, 'articles', '', 0, 0, 0.0, '', '', 0, 0, 0),
(18, 13, 0, 0, 1, 64, 0, 64, 0, 0, 'photos', '', 0, 0, 0.0, '', '', 0, 0, 0),
(19, 13, 0, 0, 1, 65, 0, 65, 0, 0, 'filiation', '', 0, 0, 0.0, '', '', 0, 0, 0),
(20, 0, 0, 0, 1, 66, 0, 66, 0, 0, 'creer-une-sortie', '', 0, 0, 0.0, '/css/ui-cupertino/jquery-ui-1.8.18.custom.css', '/js/jquery-ui-1.10.2.custom.min.js;/js/jquery-ui-timepicker-addon.js;/js/creer-une-sortie.js', 0, 0, 0),
(21, 0, 0, 0, 1, 0, 0, 0, 0, 0, 'gestion-des-sorties', 'Gestion des sorties', 0, 0, 0.0, '', '', 0, 0, 0),
(22, 0, 0, 0, 1, 69, 0, 69, 0, 0, 'sortie', '', 0, 0, 0.1, '', '', 0, 0, 0),
(23, 0, 0, 0, 1, 0, 0, 0, 0, 0, 'supprimer-une-sortie', 'Supprimer une sortie', 0, 0, 0.0, '', '', 0, 0, 0),
(24, 0, 0, 0, 1, 0, 0, 0, 0, 0, 'supprimer-une-sortie', 'Supprimer une sortie', 0, 0, 0.0, '', '', 0, 0, 0),
(25, 0, 0, 0, 1, 0, 0, 0, 0, 0, 'annuler-une-sortie', 'Annuler une sortie', 0, 0, 0.0, '', '', 0, 0, 0),
(26, 0, 0, 0, 1, 0, 0, 0, 0, 0, 'adherents', '', 0, 0, 0.0, '/tools/datatables/media/css/jquery.dataTables.css', '/tools/datatables/media/js/jquery.dataTables.min.js', 0, 0, 0),
(27, 0, 0, 0, 1, 0, 0, 0, 0, 0, 'adherents-creer', 'Créer un adhérent', 0, 0, 0.0, '', '', 0, 0, 0),
(28, 0, 0, 0, 1, 0, 0, 0, 0, 0, 'agenda', '', 0, 0, 0.0, '', '', 0, 0, 0),
(29, 0, 0, 0, 1, 0, 0, 0, 0, 0, 'responsables', '', 0, 0, 0.0, '', '', 0, 0, 0),
(30, 0, 0, 0, 1, 0, 0, 0, 0, 0, 'article-new', '', 0, 0, 0.0, '', '/js/article-new.js', 0, 0, 0),
(31, 0, 0, 0, 1, 0, 0, 0, 0, 0, 'article-edit', '', 0, 0, 0.0, '', '', 0, 0, 0),
(32, 0, 0, 0, 1, 0, 0, 0, 0, 0, 'article', '', 0, 0, 0.0, '', '', 0, 0, 0),
(33, 0, 0, 0, 1, 0, 0, 0, 0, 0, 'gestion-des-articles', '', 0, 0, 0.0, '', '', 0, 0, 0),
(34, 0, 0, 0, 1, 0, 0, 0, 0, 0, 'recherche', '', 0, 0, 0.0, '', '', 0, 0, 0),
(35, 0, 0, 0, 1, 0, 0, 0, 0, 0, 'commission-add', '', 0, 0, 0.0, '', '', 0, 0, 0),
(36, 0, 0, 0, 1, 0, 0, 0, 0, 0, 'gestion-des-commissions', '', 0, 0, 0.0, '', '', 0, 0, 0),
(37, 0, 0, 0, 1, 0, 0, 0, 0, 0, 'commission-edit', '', 0, 0, 0.0, '', '', 0, 0, 0),
(38, 0, 0, 0, 1, 0, 0, 0, 0, 0, 'validation-des-sorties', '', 0, 0, 0.0, '', '', 0, 0, 0),
(39, 0, 0, 0, 1, 0, 0, 0, 0, 0, 'stats', '', 0, 0, 0.0, '/tools/datatables/media/css/jquery.dataTables.css', '/tools/datatables/media/js/jquery.dataTables.min.js', 0, 0, 0),
(40, 0, 0, 0, 1, 0, 0, 0, 0, 0, 'fichier-adherents', '', 0, 0, 0.0, '', '', 0, 0, 0),
(41, 0, 0, 0, 1, 0, 0, 0, 0, 0, 'user-full', '', 0, 0, 0.0, '', '', 0, 0, 0),
(42, 0, 0, 0, 1, 0, 0, 0, 0, 0, 'email-change', 'Modification de votre e-mail', 1, 0, 0.0, '', '', 0, 0, 0),
(43, 10, 0, 0, 0, 43, 0, 43, 0, 0, 'presentation', 'Présentation', 0, 1, '0.0', '', '', 0, 1, 1395824906),
(44, 10, 0, 0, 0, 44, 0, 44, 0, 0, 'alpinisme', 'Alpinisme', 0, 1, '0.0', '', '', 0, 1, 1395825159);

-- --------------------------------------------------------

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
-- Contenu de la table `caf_user`
--

INSERT INTO `caf_user` (`id_user`, `email_user`, `mdp_user`, `cafnum_user`, `cafnum_parent_user`, `firstname_user`, `lastname_user`, `nickname_user`, `created_user`, `birthday_user`, `tel_user`, `tel2_user`, `adresse_user`, `cp_user`, `ville_user`, `pays_user`, `civ_user`, `moreinfo_user`, `auth_contact_user`, `valid_user`, `cookietoken_user`, `manuel_user`, `nomade_user`, `nomade_parent_user`, `date_adhesion_user`, `doit_renouveler_user`, `alerte_renouveler_user`, `ts_insert_user`, `ts_update_user`) VALUES
(1, 'contact@herewecom.fr', '098f6bcd4621d373cade4e832627b4f6', '749999999999', '', 'ADMIN', 'SUPER', 'Admin', 1375216633, 270687600, '0950985475', '', '19 ALLEE DU LAC ST ANDRE', '73375', 'LE BOURGET DU LAC', '', 'M', '', 'users', 1, '8f7d807e1f53eff5f9efbe5cb81090fb', 0, 0, 0, 1379977200, 0, 0, 1372629601, 1395098083);

-- --------------------------------------------------------

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
-- Contenu de la table `caf_userright`
--

INSERT INTO `caf_userright` (`id_userright`, `code_userright`, `title_userright`, `ordre_userright`, `parent_userright`) VALUES
(1, 'article_read', 'Consulter un article', 10, 'GESTION DES ARTICLES'),
(2, 'article_create', 'Créer un article', 20, 'GESTION DES ARTICLES'),
(3, 'article_ask_collaboration', 'Inviter un rédacteur à collaborer sur son article', 30, 'GESTION DES ARTICLES'),
(4, 'article_ask_collaboration_notmine', 'Inviter un rédacteur à collaborer sur l''article d''un tiers', 40, 'GESTION DES ARTICLES'),
(5, 'article_edit', 'Modifier un article (rédigé par soi)', 50, 'GESTION DES ARTICLES'),
(6, 'article_edit_notmine', 'Modifier un article (rédigé par un tiers)', 60, 'GESTION DES ARTICLES'),
(7, 'article_delete', 'Supprimer un article (rédigé par soi)', 70, 'GESTION DES ARTICLES'),
(8, 'article_delete_notmine', 'Supprimer un article (rédigé par un tiers)', 80, 'GESTION DES ARTICLES'),
(9, 'article_comment', 'Commenter un article', 90, 'GESTION DES ARTICLES'),
(10, 'evt_read', 'Consulter une sortie', 100, 'GESTION DES SORTIES '),
(11, 'evt_create', 'Créer une sortie', 110, 'GESTION DES SORTIES '),
(12, 'evt_edit', 'Modifier une sortie', 120, 'GESTION DES SORTIES '),
(13, 'evt_validate', 'Valider la publication d''une sortie selon la commission', 130, 'GESTION DES SORTIES '),
(14, 'evt_delete', 'Supprimer une sortie (effacement)', 140, 'GESTION DES SORTIES '),
(15, 'evt_cancel_any', 'Annuler une sortie (désactivation) de toutes les commissions', 150, 'GESTION DES SORTIES '),
(16, 'evt_print', 'Imprimer la fiche de sortie', 160, 'GESTION DES SORTIES '),
(17, 'evt_edit_bilan', 'Rédiger le compte rendu technique (cloture)', 170, 'GESTION DES SORTIES '),
(18, 'evt_join', 'S''inscrire à une sortie (pour son propre compte)', 180, 'GESTION DES SORTIES '),
(19, 'evt_unjoin', 'Se désinscrire à une sortie (pour son propre compte)', 190, 'GESTION DES SORTIES '),
(20, 'evt_join_notme', 'Inscrire un participant (adhérent tiers)', 200, 'GESTION DES SORTIES '),
(21, 'evt_unjoin_notme', 'Désinscrire un participants (adhérent tiers)', 210, 'GESTION DES SORTIES '),
(22, 'evt_joining_accept', 'Valider l''inscription d''un participant (adhérent tiers)', 220, 'GESTION DES SORTIES '),
(23, 'evt_joining_refuse', 'Refuser l''inscription d''un participant (adhérent tiers)', 230, 'GESTION DES SORTIES '),
(24, 'evt_contact_leader', 'Contacter l''encadrant d''une sortie', 240, 'GESTION DES SORTIES '),
(25, 'evt_contact_all', 'Contacter tous les participants', 250, 'GESTION DES SORTIES '),
(26, 'evt_legal_accept', 'Valider juridiquement une sortie', 260, 'GESTION DES SORTIES '),
(27, 'evt_legal_refuse', 'Annuler juridiquement une sortie', 270, 'GESTION DES SORTIES '),
(28, 'user_read_public', 'Consulter le profil public d''un adhérent', 280, 'GESTION DES COMPTES ADHERENTS'),
(29, 'user_read_limited', 'Consulter le profil semi-public d''un adhérent', 290, 'GESTION DES COMPTES ADHERENTS'),
(30, 'user_read_private', 'Consulter le profil complet d''un adhérent', 300, 'GESTION DES COMPTES ADHERENTS'),
(31, 'user_create_manually', 'Créer un compte adhérent', 310, 'GESTION DES COMPTES ADHERENTS'),
(32, 'user_edit_notme', 'Modifier un profil adhérent tiers (infos personnelles)', 320, 'GESTION DES COMPTES ADHERENTS'),
(33, 'user_givepresidence', 'Attribuer le droit "(vice)Président" à un compte adhérent', 330, 'GESTION DES COMPTES ADHERENTS'),
(34, 'user_desactivate_any', 'Désactiver un compte adhérent (compte conservé en bdd)', 340, 'GESTION DES COMPTES ADHERENTS'),
(35, 'user_reactivate', 'Réactiver un compte adhérent désactivé', 350, 'GESTION DES COMPTES ADHERENTS'),
(36, 'user_delete', 'Supprimer un compte adhérent', 360, 'GESTION DES COMPTES ADHERENTS'),
(37, 'user_see_all', 'Consulter la liste de tous les adhérents', 370, 'GESTION DES COMPTES ADHERENTS'),
(38, 'user_contact_authonly', 'Contacter un autre adhérent (si autorisé par l''adhérent)', 380, 'GESTION DES COMPTES ADHERENTS'),
(39, 'user_contact', 'Contacter un autre adhérent', 390, 'GESTION DES COMPTES ADHERENTS'),
(40, 'user_create', 'Créer son propre profil adhérent', 400, 'GESTION DES COMPTES ADHERENTS'),
(41, 'user_read_self', 'Consulter son propre profil adhérent', 410, 'GESTION DES COMPTES ADHERENTS'),
(42, 'user_edit', 'Modifier son propre profil adhérent (infos personnelles)', 420, 'GESTION DES COMPTES ADHERENTS'),
(43, 'user_desactivate', 'Supprimer son propre profil adhérent (>>désactivation)', 430, 'GESTION DES COMPTES ADHERENTS'),
(44, 'user_evtlist_read', 'Consulter la liste des sorties auxquelles il a participé', 440, 'GESTION DES COMPTES ADHERENTS'),
(45, 'stats_users_read', 'Consulter les statistiques de tous les adhérents', 450, 'STATISTIQUES'),
(46, 'stats_commissions_read', 'Consulter les statistiques par commission', 460, 'STATISTIQUES'),
(47, 'stats_commissions_export', 'Exporter les statistiques par commission', 470, 'STATISTIQUES'),
(48, 'stats_all_export', 'Exporter toutes les statistiques', 480, 'STATISTIQUES'),
(49, 'comm_read', 'Consulter la liste des commissions', 490, 'COMMISSIONS'),
(50, 'comm_create', 'Créer une commission', 500, 'COMMISSIONS'),
(51, 'comm_edit', 'Modifier une commission', 510, 'COMMISSIONS'),
(52, 'comm_desactivate', 'Désactiver une commission', 520, 'COMMISSIONS'),
(53, 'comm_delete', 'Supprimer une commission', 530, 'COMMISSIONS'),
(54, 'comm_lier_encadrant', 'Associer un encadrant / co-encadrant', 540, 'COMMISSIONS'),
(55, 'comm_delier_encadrant', 'Désassocier un encadrant / co-encadrant', 550, 'COMMISSIONS'),
(58, 'comm_lier_responsable', 'Associer un responsable à une commission', 560, 'COMMISSIONS'),
(56, 'article_contact_author', 'Contacter l''auteur d''un article via un formulaire', 11, 'GESTION DES ARTICLES'),
(57, 'article_validate_all', 'Valider/Refuser la publication d''un article de toutes les commissions', 61, 'GESTION DES ARTICLES'),
(59, 'comm_delier_responsable', 'Retirer un responsable d''une commission', 570, 'COMMISSIONS'),
(60, 'comm_lier_benevole', 'Associer un benevole à une commission', 580, 'COMMISSIONS'),
(61, 'comm_delier_benevole', 'Délier un benevole d''une commission', 590, 'COMMISSIONS'),
(62, 'evt_validate_all', 'Valider la publication d''une sortie de toutes les commissions', 131, 'GESTION DES SORTIES '),
(63, 'evt_cancel', 'Annuler une sortie (désactivation) de sa comm', 151, 'GESTION DES SORTIES '),
(64, 'user_giveright_1', 'Donner des droits d''encadrement (+coenc +bénév)', 331, 'GESTION DES COMPTES ADHERENTS'),
(65, 'user_giveright_2', 'Attribuer / retirer le type salarié à un adhérent', 335, 'GESTION DES COMPTES ADHERENTS'),
(67, 'user_giveright_3', 'Attribuer le statut "responsable de commission"', 336, 'GESTION DES COMPTES ADHERENTS'),
(68, 'comment_delete_any', 'Supprimer n''importe quel commentaire', 91, 'GESTION DES ARTICLES'),
(69, 'evt_join_doall', 'Administrer toutes les inscriptions aux sorties', 231, 'GESTION DES SORTIES '),
(70, 'user_reset', 'Remettre à zéro un compte utilisateur', 350, 'GESTION DES COMPTES ADHERENTS'),
(71, 'user_updatefiles', 'Mise à jour des fichiers adhérents', 279, 'GESTION DES COMPTES ADHERENTS'),
(72, 'article_validate', 'Valider/Refuser la publication d''un article de ses commissions', 62, 'GESTION DES ARTICLES');

-- --------------------------------------------------------

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
-- Contenu de la table `caf_usertype`
--

INSERT INTO `caf_usertype` (`id_usertype`, `hierarchie_usertype`, `code_usertype`, `title_usertype`, `limited_to_comm_usertype`) VALUES
(1, 0, 'visiteur', 'Visiteur', 0),
(2, 10, 'adherent', 'Adhérent', 0),
(3, 40, 'redacteur', 'Rédacteur', 1),
(4, 60, 'encadrant', 'Encadrant', 1),
(5, 70, 'responsable-commission', 'Resp. de commission', 1),
(6, 90, 'president', 'Président', 0),
(7, 80, 'vice-president', 'Vice Président', 0),
(8, 100, 'administrateur', 'Administrateur', 0),
(9, 20, 'salarie', 'Salarié', 0),
(10, 30, 'benevole', 'Bénévole', 1),
(11, 50, 'coencadrant', 'Co-encadrant', 1);

-- --------------------------------------------------------

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
-- Contenu de la table `caf_usertype_attr`
--

INSERT INTO `caf_usertype_attr` (`id_usertype_attr`, `type_usertype_attr`, `right_usertype_attr`, `details_usertype_attr`) VALUES
(1, 1, 49, '1394046177'),
(2, 2, 49, '1394046177'),
(3, 9, 49, '1394046177'),
(4, 10, 49, '1394046177'),
(5, 3, 49, '1394046177'),
(6, 11, 49, '1394046177'),
(7, 4, 49, '1394046177'),
(8, 5, 49, '1394046177'),
(9, 7, 49, '1394046177'),
(10, 6, 49, '1394046177'),
(11, 8, 49, '1394046177'),
(12, 7, 50, '1394046177'),
(13, 6, 50, '1394046177'),
(14, 8, 50, '1394046177'),
(15, 7, 51, '1394046177'),
(16, 6, 51, '1394046177'),
(17, 8, 51, '1394046177'),
(18, 7, 52, '1394046177'),
(19, 6, 52, '1394046177'),
(20, 8, 52, '1394046177'),
(21, 8, 53, '1394046177'),
(22, 9, 54, '1394046177'),
(23, 5, 54, '1394046177'),
(24, 7, 54, '1394046177'),
(25, 6, 54, '1394046177'),
(26, 8, 54, '1394046177'),
(27, 9, 55, '1394046177'),
(28, 5, 55, '1394046177'),
(29, 7, 55, '1394046177'),
(30, 6, 55, '1394046177'),
(31, 8, 55, '1394046177'),
(32, 7, 58, '1394046177'),
(33, 6, 58, '1394046177'),
(34, 8, 58, '1394046177'),
(35, 7, 59, '1394046177'),
(36, 6, 59, '1394046177'),
(37, 8, 59, '1394046177'),
(38, 5, 60, '1394046177'),
(39, 7, 60, '1394046177'),
(40, 6, 60, '1394046177'),
(41, 8, 60, '1394046177'),
(42, 5, 61, '1394046177'),
(43, 7, 61, '1394046177'),
(44, 6, 61, '1394046177'),
(45, 8, 61, '1394046177'),
(46, 1, 1, '1394046177'),
(47, 2, 1, '1394046177'),
(48, 9, 1, '1394046177'),
(49, 10, 1, '1394046177'),
(50, 3, 1, '1394046177'),
(51, 11, 1, '1394046177'),
(52, 4, 1, '1394046177'),
(53, 5, 1, '1394046177'),
(54, 7, 1, '1394046177'),
(55, 6, 1, '1394046177'),
(56, 8, 1, '1394046177'),
(57, 1, 56, '1394046177'),
(58, 2, 56, '1394046177'),
(59, 9, 56, '1394046177'),
(60, 10, 56, '1394046177'),
(61, 3, 56, '1394046177'),
(62, 11, 56, '1394046177'),
(63, 4, 56, '1394046177'),
(64, 5, 56, '1394046177'),
(65, 7, 56, '1394046177'),
(66, 6, 56, '1394046177'),
(67, 8, 56, '1394046177'),
(68, 9, 2, '1394046177'),
(69, 10, 2, '1394046177'),
(70, 3, 2, '1394046177'),
(71, 11, 2, '1394046177'),
(72, 4, 2, '1394046177'),
(73, 5, 2, '1394046177'),
(74, 7, 2, '1394046177'),
(75, 6, 2, '1394046177'),
(76, 8, 2, '1394046177'),
(77, 9, 3, '1394046177'),
(78, 10, 3, '1394046177'),
(79, 3, 3, '1394046177'),
(80, 11, 3, '1394046177'),
(81, 4, 3, '1394046177'),
(82, 5, 3, '1394046177'),
(83, 7, 3, '1394046177'),
(84, 6, 3, '1394046177'),
(85, 8, 3, '1394046177'),
(86, 5, 4, '1394046177'),
(87, 7, 4, '1394046177'),
(88, 6, 4, '1394046177'),
(89, 8, 4, '1394046177'),
(90, 9, 5, '1394046177'),
(91, 10, 5, '1394046177'),
(92, 3, 5, '1394046177'),
(93, 11, 5, '1394046177'),
(94, 4, 5, '1394046177'),
(95, 5, 5, '1394046177'),
(96, 7, 5, '1394046177'),
(97, 6, 5, '1394046177'),
(98, 8, 5, '1394046177'),
(99, 5, 6, '1394046177'),
(100, 7, 6, '1394046177'),
(101, 6, 6, '1394046177'),
(102, 8, 6, '1394046177'),
(103, 7, 57, '1394046177'),
(104, 6, 57, '1394046177'),
(105, 8, 57, '1394046177'),
(106, 7, 72, '1394046177'),
(107, 6, 72, '1394046177'),
(108, 8, 72, '1394046177'),
(109, 3, 7, '1394046177'),
(110, 11, 7, '1394046177'),
(111, 4, 7, '1394046177'),
(112, 5, 7, '1394046177'),
(113, 7, 7, '1394046177'),
(114, 6, 7, '1394046177'),
(115, 8, 7, '1394046177'),
(116, 5, 8, '1394046177'),
(117, 7, 8, '1394046177'),
(118, 6, 8, '1394046177'),
(119, 8, 8, '1394046177'),
(120, 2, 9, '1394046177'),
(121, 10, 9, '1394046177'),
(122, 3, 9, '1394046177'),
(123, 11, 9, '1394046177'),
(124, 4, 9, '1394046177'),
(125, 5, 9, '1394046177'),
(126, 7, 9, '1394046177'),
(127, 6, 9, '1394046177'),
(128, 8, 9, '1394046177'),
(129, 9, 68, '1394046177'),
(130, 3, 68, '1394046177'),
(131, 7, 68, '1394046177'),
(132, 6, 68, '1394046177'),
(133, 8, 68, '1394046177'),
(134, 7, 71, '1394046177'),
(135, 6, 71, '1394046177'),
(136, 8, 71, '1394046177'),
(137, 1, 28, '1394046177'),
(138, 2, 28, '1394046177'),
(139, 9, 28, '1394046177'),
(140, 10, 28, '1394046177'),
(141, 3, 28, '1394046177'),
(142, 11, 28, '1394046177'),
(143, 4, 28, '1394046177'),
(144, 5, 28, '1394046177'),
(145, 7, 28, '1394046177'),
(146, 6, 28, '1394046177'),
(147, 8, 28, '1394046177'),
(148, 2, 29, '1394046177'),
(149, 9, 29, '1394046177'),
(150, 10, 29, '1394046177'),
(151, 3, 29, '1394046177'),
(152, 11, 29, '1394046177'),
(153, 4, 29, '1394046177'),
(154, 5, 29, '1394046177'),
(155, 7, 29, '1394046177'),
(156, 6, 29, '1394046177'),
(157, 8, 29, '1394046177'),
(158, 11, 30, '1394046177'),
(159, 4, 30, '1394046177'),
(160, 5, 30, '1394046177'),
(161, 7, 30, '1394046177'),
(162, 6, 30, '1394046177'),
(163, 8, 30, '1394046177'),
(164, 7, 31, '1394046177'),
(165, 6, 31, '1394046177'),
(166, 8, 31, '1394046177'),
(167, 8, 32, '1394046177'),
(168, 6, 33, '1394046177'),
(169, 8, 33, '1394046177'),
(170, 9, 64, '1394046177'),
(171, 5, 64, '1394046177'),
(172, 7, 64, '1394046177'),
(173, 6, 64, '1394046177'),
(174, 8, 64, '1394046177'),
(175, 7, 65, '1394046177'),
(176, 6, 65, '1394046177'),
(177, 8, 65, '1394046177'),
(178, 7, 67, '1394046177'),
(179, 6, 67, '1394046177'),
(180, 8, 67, '1394046177'),
(181, 7, 34, '1394046177'),
(182, 6, 34, '1394046177'),
(183, 8, 34, '1394046177'),
(184, 7, 35, '1394046177'),
(185, 6, 35, '1394046177'),
(186, 8, 35, '1394046177'),
(187, 7, 70, '1394046177'),
(188, 6, 70, '1394046177'),
(189, 8, 70, '1394046177'),
(190, 8, 36, '1394046177'),
(191, 9, 37, '1394046177'),
(192, 10, 37, '1394046177'),
(193, 11, 37, '1394046177'),
(194, 4, 37, '1394046177'),
(195, 5, 37, '1394046177'),
(196, 7, 37, '1394046177'),
(197, 6, 37, '1394046177'),
(198, 8, 37, '1394046177'),
(199, 2, 38, '1394046177'),
(200, 9, 38, '1394046177'),
(201, 10, 38, '1394046177'),
(202, 3, 38, '1394046177'),
(203, 11, 38, '1394046177'),
(204, 4, 38, '1394046177'),
(205, 5, 38, '1394046177'),
(206, 7, 38, '1394046177'),
(207, 6, 38, '1394046177'),
(208, 8, 38, '1394046177'),
(209, 1, 39, '1394046177'),
(210, 2, 39, '1394046177'),
(211, 9, 39, '1394046177'),
(212, 10, 39, '1394046177'),
(213, 3, 39, '1394046177'),
(214, 11, 39, '1394046177'),
(215, 4, 39, '1394046177'),
(216, 5, 39, '1394046177'),
(217, 7, 39, '1394046177'),
(218, 6, 39, '1394046177'),
(219, 8, 39, '1394046177'),
(220, 1, 40, '1394046177'),
(221, 2, 41, '1394046177'),
(222, 2, 42, '1394046177'),
(223, 2, 43, '1394046177'),
(224, 2, 44, '1394046177'),
(225, 9, 44, '1394046177'),
(226, 10, 44, '1394046177'),
(227, 3, 44, '1394046177'),
(228, 11, 44, '1394046177'),
(229, 4, 44, '1394046177'),
(230, 5, 44, '1394046177'),
(231, 7, 44, '1394046177'),
(232, 6, 44, '1394046177'),
(233, 8, 44, '1394046177'),
(234, 1, 10, '1394046177'),
(235, 2, 10, '1394046177'),
(236, 9, 10, '1394046177'),
(237, 10, 10, '1394046177'),
(238, 3, 10, '1394046177'),
(239, 11, 10, '1394046177'),
(240, 4, 10, '1394046177'),
(241, 5, 10, '1394046177'),
(242, 7, 10, '1394046177'),
(243, 6, 10, '1394046177'),
(244, 8, 10, '1394046177'),
(245, 11, 11, '1394046177'),
(246, 4, 11, '1394046177'),
(247, 5, 11, '1394046177'),
(248, 7, 11, '1394046177'),
(249, 6, 11, '1394046177'),
(250, 8, 11, '1394046177'),
(251, 11, 12, '1394046177'),
(252, 4, 12, '1394046177'),
(253, 5, 12, '1394046177'),
(254, 7, 12, '1394046177'),
(255, 6, 12, '1394046177'),
(256, 8, 12, '1394046177'),
(257, 5, 13, '1394046177'),
(258, 7, 13, '1394046177'),
(259, 6, 13, '1394046177'),
(260, 8, 13, '1394046177'),
(261, 7, 62, '1394046177'),
(262, 6, 62, '1394046177'),
(263, 8, 62, '1394046177'),
(264, 7, 14, '1394046177'),
(265, 6, 14, '1394046177'),
(266, 8, 14, '1394046177'),
(267, 7, 15, '1394046177'),
(268, 6, 15, '1394046177'),
(269, 8, 15, '1394046177'),
(270, 4, 63, '1394046177'),
(271, 5, 63, '1394046177'),
(272, 7, 63, '1394046177'),
(273, 6, 63, '1394046177'),
(274, 8, 63, '1394046177'),
(275, 4, 16, '1394046177'),
(276, 5, 16, '1394046177'),
(277, 7, 16, '1394046177'),
(278, 6, 16, '1394046177'),
(279, 8, 16, '1394046177'),
(280, 4, 17, '1394046177'),
(281, 5, 17, '1394046177'),
(282, 7, 17, '1394046177'),
(283, 6, 17, '1394046177'),
(284, 8, 17, '1394046177'),
(285, 2, 18, '1394046177'),
(286, 4, 18, '1394046177'),
(287, 5, 18, '1394046177'),
(288, 7, 18, '1394046177'),
(289, 6, 18, '1394046177'),
(290, 8, 18, '1394046177'),
(291, 2, 19, '1394046177'),
(292, 4, 19, '1394046177'),
(293, 5, 19, '1394046177'),
(294, 7, 19, '1394046177'),
(295, 6, 19, '1394046177'),
(296, 8, 19, '1394046177'),
(297, 9, 20, '1394046177'),
(298, 4, 20, '1394046177'),
(299, 5, 20, '1394046177'),
(300, 7, 20, '1394046177'),
(301, 6, 20, '1394046177'),
(302, 8, 20, '1394046177'),
(303, 9, 21, '1394046177'),
(304, 4, 21, '1394046177'),
(305, 5, 21, '1394046177'),
(306, 7, 21, '1394046177'),
(307, 6, 21, '1394046177'),
(308, 8, 21, '1394046177'),
(309, 4, 22, '1394046177'),
(310, 5, 22, '1394046177'),
(311, 7, 22, '1394046177'),
(312, 6, 22, '1394046177'),
(313, 8, 22, '1394046177'),
(314, 4, 23, '1394046177'),
(315, 5, 23, '1394046177'),
(316, 7, 23, '1394046177'),
(317, 6, 23, '1394046177'),
(318, 8, 23, '1394046177'),
(319, 7, 69, '1394046177'),
(320, 6, 69, '1394046177'),
(321, 8, 69, '1394046177'),
(322, 2, 24, '1394046177'),
(323, 9, 24, '1394046177'),
(324, 4, 24, '1394046177'),
(325, 5, 24, '1394046177'),
(326, 7, 24, '1394046177'),
(327, 6, 24, '1394046177'),
(328, 8, 24, '1394046177'),
(329, 4, 25, '1394046177'),
(330, 5, 25, '1394046177'),
(331, 7, 25, '1394046177'),
(332, 6, 25, '1394046177'),
(333, 8, 25, '1394046177'),
(334, 7, 26, '1394046177'),
(335, 6, 26, '1394046177'),
(336, 7, 27, '1394046177'),
(337, 6, 27, '1394046177'),
(338, 7, 45, '1394046177'),
(339, 6, 45, '1394046177'),
(340, 8, 45, '1394046177'),
(341, 5, 46, '1394046177'),
(342, 7, 46, '1394046177'),
(343, 6, 46, '1394046177'),
(344, 8, 46, '1394046177'),
(345, 5, 47, '1394046177'),
(346, 7, 47, '1394046177'),
(347, 6, 47, '1394046177'),
(348, 8, 47, '1394046177'),
(349, 7, 48, '1394046177'),
(350, 6, 48, '1394046177'),
(351, 8, 48, '1394046177');

-- --------------------------------------------------------

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
-- Contenu de la table `caf_user_attr`
--

INSERT INTO `caf_user_attr` (`id_user_attr`, `user_user_attr`, `usertype_user_attr`, `params_user_attr`, `details_user_attr`) VALUES
(1, 1, 8, '', '1375216781'),
(2, 1, 6, '', '1375216781'),
(3, 1, 4, 'commission:sorties-familles', '1375216781');

-- --------------------------------------------------------

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

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
