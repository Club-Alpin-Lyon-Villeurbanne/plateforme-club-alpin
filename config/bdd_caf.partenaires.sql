
--
-- Contenu de la table `caf_content_inline`
--

INSERT INTO `caf_content_inline` (`id_content_inline`, `groupe_content_inline`, `code_content_inline`, `lang_content_inline`, `contenu_content_inline`, `date_content_inline`, `linkedtopage_content_inline`) VALUES
(NULL, 2, 'meta-title-partenaires-club', 'fr', 'Partenaires du club', 1371041597, ''),
(NULL, 2, 'meta-description-partenaires-caf', 'fr', 'partenaires_caf', 1371041597, ''),
(NULL, 2, 'meta-title-nos-partenaires-publics', 'fr', 'Nos partenaires publics', 1375653068, ''),
(NULL, 2, 'meta-title-nos-partenaires-prives', 'fr', 'Nos partenaires privés', 1375653085, '');

-- --------------------------------------------------------

--
-- Contenu de la table `caf_page`
--

INSERT INTO `caf_page` (`id_page`, `parent_page`, `admin_page`, `superadmin_page`, `vis_page`, `ordre_page`, `menu_page`, `ordre_menu_page`, `menuadmin_page`, `ordre_menuadmin_page`, `code_page`, `default_name_page`, `meta_title_page`, `meta_description_page`, `priority_page`, `add_css_page`, `add_js_page`, `lock_page`, `pagelibre_page`, `created_page`) VALUES
(NULL, 54, 0, 0, 0, 86, 0, 86, 0, 0, 'partenaires-caf', 'Partenaires du club', 0, 1, '0.9', '', '', 0, 1, 1371041597),
(NULL, 54, 0, 0, 1, 107, 0, 107, 0, 0, 'nos-partenaires-publics', 'Nos partenaires publics', 0, 0, '0.9', '', '', 0, 1, 1375653068),
(NULL, 54, 0, 0, 1, 108, 0, 108, 0, 0, 'nos-partenaires-prives', 'Nos partenaires privés', 0, 0, '0.9', '', '', 0, 1, 1375653085),
(NULL, 0, 1, 0, 1, 0, 0, 0, 1, 5, 'admin-partenaires', 'Gestion des partenaires', 1, 0, '0.0', '', '', 0, 0, 0);

-- --------------------------------------------------------

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
-- Contenu de la table `caf_partenaires`
--

INSERT INTO `caf_partenaires` (`part_id`, `part_name`, `part_url`, `part_desc`, `part_image`, `part_type`, `part_enable`, `part_order`, `part_click`) VALUES
(1, 'PARTENAIRE1', 'http://www.cafchambery.com/', 'PARTENAIRE1', 'partenaire1.png', 1, 1, 999, 1),
(2, 'FFCAM', 'http://www.ffcam.fr/', 'FFCAM', 'ffcam.png', 1, 1, 999, 1);


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



