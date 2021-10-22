-- 
-- On autorise les NULL sur birthday_user, gestion de l'age des utilisateurs nomades
-- 
ALTER TABLE `caf_user` CHANGE `birthday_user` `birthday_user` BIGINT(20) NULL DEFAULT NULL; 


--
-- Modification dans la table `caf_content_html`
-- Précision : besoin d'être inscrit au CAF Lyon-Villeurbanne
--

UPDATE `caf_content_html` SET `contenu_content_html` = '<p class="menutitle">Activer mon compte</p> <p>Pour rejoindre le site, vous devez être inscrit au Club Alpin Français de Lyon-Villeurbanne ou sa section Ouest Lyonnais.<br />Munissez-vous de votre numéro d''adhérent et de votre adresse e-mail, choisissez un peudonyme et un mot de passe, et laissez-vous guider.</p>' WHERE `id_content_html` = 2

