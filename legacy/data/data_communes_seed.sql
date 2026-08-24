-- Seed minimal de la table communes pour dev/CI.
-- Lancé après les migrations Doctrine, avant les fixtures.
-- Pour un dev complet (autocomplete sur toute la France), utiliser
-- `make database-bootstrap` qui appelle ensuite `app:import-communes`.

TRUNCATE TABLE communes;

INSERT INTO communes (code_commune_insee, nom_commune, code_postal, libelle_acheminement, ligne5, geopoint, latitude, longitude) VALUES
-- Lyon (point de RDV / covoiturage majoritaire)
('69381', 'Lyon 1er Arrondissement', '69001', 'LYON',                       NULL, '45.76667,4.83472',  45.76667000, 4.83472000),
('69383', 'Lyon 3e Arrondissement',  '69003', 'LYON',                       NULL, '45.74944,4.85944',  45.74944000, 4.85944000),

-- Massif du Mont-Blanc (Haute-Savoie)
-- Chamonix : plusieurs entrées par hameau (ligne5) pour tester la désambiguïsation de l'autocomplete
('74056', 'Chamonix-Mont-Blanc',     '74400', 'CHAMONIX MONT BLANC',         NULL,                   '45.92375,6.86861',  45.92375000, 6.86861000),
('74056', 'Chamonix-Mont-Blanc',     '74400', 'CHAMONIX MONT BLANC',         'ARGENTIERE',           '45.96806,6.92694',  45.96806000, 6.92694000),
('74056', 'Chamonix-Mont-Blanc',     '74400', 'CHAMONIX MONT BLANC',         'LES PRAZ DE CHAMONIX', '45.94028,6.89556',  45.94028000, 6.89556000),
('74056', 'Chamonix-Mont-Blanc',     '74400', 'CHAMONIX MONT BLANC',         'LES BOSSONS',          '45.90611,6.84417',  45.90611000, 6.84417000),
('74236', 'Saint-Gervais-les-Bains', '74170', 'ST GERVAIS LES BAINS',        NULL, '45.89139,6.71139',  45.89139000, 6.71139000),
('74010', 'Annecy',                  '74000', 'ANNECY',                      NULL, '45.89944,6.12889',  45.89944000, 6.12889000),

-- Massif des Écrins (Hautes-Alpes / Isère)
('38442', 'Saint-Christophe-en-Oisans', '38520', 'ST CHRISTOPHE EN OISANS', 'LA BERARDE', '44.93917,6.24917', 44.93917000, 6.24917000),
('38052', 'Bourg-d''Oisans (Le)',    '38520', 'LE BOURG D OISANS',           NULL, '45.05278,6.03083',  45.05278000, 6.03083000),
('05101', 'Pelvoux',                 '05340', 'PELVOUX',                     NULL, '44.87194,6.48750',  44.87194000, 6.48750000),
('05023', 'Briançon',                '05100', 'BRIANCON',                    NULL, '44.89889,6.63556',  44.89889000, 6.63556000),

-- Massif de la Vanoise (Savoie)
('73208', 'Pralognan-la-Vanoise',    '73710', 'PRALOGNAN LA VANOISE',        NULL, '45.37806,6.72222',  45.37806000, 6.72222000),
('73041', 'Bonneval-sur-Arc',        '73480', 'BONNEVAL SUR ARC',            NULL, '45.37194,7.04194',  45.37194000, 7.04194000),

-- Massif du Vercors (Isère)
('38548', 'Villard-de-Lans',         '38250', 'VILLARD DE LANS',             NULL, '45.07000,5.55333',  45.07000000, 5.55333000);
