-- 
-- On autorise les NULL sur birthday_user, gestion de l'age des utilisateurs nomades
-- 
ALTER TABLE `caf_user` CHANGE `birthday_user` `birthday_user` BIGINT(20) NULL DEFAULT NULL; 

