<?php

date_default_timezone_set('Europe/Paris');
setlocale(\LC_ALL, 'fr_FR');

// NOM DU SITE ( apparaît notamment dans les e-mailings )
$p_sitename = 'CAF Lyon Villeurbanne';

// destinataire principal
$p_contactdusite = 'sitemestre@clubalpinlyon.fr';

// -------------------
// PARAMS STATIQUES

error_reporting(\E_ALL ^ \E_NOTICE);

// vars de navigation, depuis l'URL via URL REWRITING // vars get toujours dispo grace au htaccess
$p1 = formater($_GET['p1'] ?? null, 3);
$p2 = formater($_GET['p2'] ?? null, 3);
$p3 = formater($_GET['p3'] ?? null, 3);
$p4 = formater($_GET['p4'] ?? null, 3);

// par défaut, la page courante n'est pas admin (modifié en aval dans pages.php)
$p_pageadmin = false;

$listeEquipementsRecommande = [
    'Ski alpinisme' => 'Carte CAF, vêtements pour activité extérieure, fourrure polaire, coupe-vent, casquette, lunettes de soleil, crème solaire, appareil photos.  SANS OUBLIER : DVA, sonde, pelle qui peuvent être prêtés par le CAF contre participation aux frais, skis, bâtons, peaux, couteaux. Casque conseillé',
    'Rando raquettes' => 'Carte du CAF Imprimée recto-verso, Vitale, Mutuelle. Sac à dos adapté à la randonnée raquettes (avec des sangles) et suffisamment grand pour contenir les vêtements de l’activité extérieure (30 L) : fourrure polaire, goretex ou équivalent, sur-sac, bonnet, gants, lunettes de soleil (masque suivant météo), crème solaire, guêtres. Bâtons avec grosses rondelles de neige / Kit de sécurité - comprenant DVA, pelle et sonde - qui peut être prêté par le CAF contre participation aux frais et un chèque de caution de 350 €. Prévoir un jeu de piles de rechange pour le DVA. Crampons a minima forestiers (contacter l’encadrant.e). COUVERTURE DE SURVIE OBLIGATOIRE. Pique-nique et boisson (thermos ou gourde ou autre). Raquettes adaptées à vos chaussures et réglées au préalable / Autres matériels suivant information de l’encadrant.e / Chaussures de rechange pour la voiture (avec sac plastique). Pour le covoiturage : espèces ou autre moyen comme PAYLIB.',
    'Randonnée Montagne' => 'Carte du CAF Imprimée recto-verso, Vitale, Mutuelle. Sac à dos adapté à la randonnée et suffisamment grand pour contenir les vêtements de l’activité extérieure : fourrure polaire, goretex ou équivalent, cape de pluie, sur-sac, gants, bonnet ou chapeau, pique-nique, boisson, lunettes de soleil et crème solaire. Chaussures de montagne avec une semelle crantée, bâtons. Crampons forestiers suivant période et avis encadrant.e. Prévoir chaussures de rechange pour la voiture (avec sac plastique). Autres matériels suivant information de l’encadrant.e. Pour le covoiturage : espèces ou autre moyen comme PAYLIB.',
    'Bivouac' => 'Sac de couchage, tapis de sol, lampe de poche, briquet, gamelles, repas, tente',
    'Grandes voies' => "Chaussures avec des semelles adhérentes, casque, baudrier, chaussons, longe dynamique de 8mm, 2 mousquetons à vis, un tube d'assurage, 2 machards, sac dos petit ou moyen, coupe vent, 2l d'eau, vivres de courses, lampe de poche, téléphone portable chargé et allumé, lunettes de soleil. En hiver : gants, bonnet.",
    'Via ferrata' => "Casque, baudrier, longe de via ferrata, gants de jardinage, vêtements de sport, petit sac à dos, 1-2 litres d'eau, pique nique",
    'Spéléo' => "Vêtements de sport sales, pull en laine, bottes ou chaussures de marche, gants Mappa, 1 litre d'eau, pique nique, 4 piles rondes type LR 6 (vous les récupérez à la fin de la sortie)",
    'Camping' => "Sac de couchage (avec sac à viande), tapis de sol, popote (assiette + bol), gourde, couverts, lampe de poche (frontale c'est mieux), petit nécessaire de toilette",
    'Escalade SAE' => "Baudrier, assureur et mousqueton de sécurité, chaussons d'escalade, licence CAF à jour, gourde d’eau, vêtements adaptés à l’escalade, haut chaud (il peut faire froid quand on assure), chaussures fermées propres pour assurer, élastique pour attacher les cheveux, pharmacie personnelle et du chocolat pour les encadrant.e.s ! Note : pour le baudrier, attention à ne pas dépasser la durée d’usage indiquée sur la notice constructeur. Dans tous les cas, cet équipement doit être mis au rebut au plus tard 10 ans après leur fabrication.",
    'Escalade SNE' => "Casque normé EN12492, baudrier, assureur avec son mousqueton de sécurité, longe dynamique cousue par le fabricant avec son mousqueton de sécurité, un jeu de minimum 7 dégaines, un machard avec son mousqueton de sécurité, chaussons d'escalade, licence CAF à jour, gourde d’eau et/ou thermos, encas, vêtements adaptés à l’escalade, haut chaud (il peut faire froid quand on assure), une membrane coupe-vent, chaussures fermées pour assurer, lunettes de soleil, crème solaire, pharmacie personnelle et du chocolat pour les encadrant.e.s ! Note : pour les éléments textiles de vos équipements de sécurité (baudrier, longe dynamique, sangles de dégaines, machard, etc.), attention à ne pas dépasser la durée d’usage indiquée sur la notice constructeur. Dans tous les cas, ces équipements doivent être mis au rebut au plus tard 10 ans après leur fabrication.",
    'Affaires personnelles' => 'Carte CAF, vêtements pour activité extérieure, fourrure polaire, coupe-vent, casquette, lunettes de soleil, crème solaire, appareil photos',
    'Alpinisme' => 'Piolet, casque, baudrier, crampons, 3 mousquetons à vis, longe en corde dynamique (pas de sangle pour se vacher), une sangle de 120, 2 anneaux de cordelette pour machard, gourde, sac à dos (30 litres), chaussures à semelles rigides, lampe frontale, lunettes de soleil cat 4. Vetements : système 3 couches : veste, et pantalon gore-tex ou équivalent, t-shirt merinos, polaire, guêtres, gants (prévoir une paire de rechange), bonnet.',
    'Cascade de glace' => 'Une paire de piolets techniques, une paire de crampons techniques, grosses chaussures à tiges rigides, 2 voire 3 paires de gants (dont imperméables), veste imperméable, vêtements chauds, bonnet, thé chaud...',
    'Vélo de Montagne' => 'Casque, gants et protections, chaussures, eau et nourriture de course, une chambre à air, une pompe, démonte-pneus, un multi-tool, une attache rapide de chaine, une patte de dérailleur, et un VTT en bon état de fonctionnement: freins, pneus, transmission, serrages... Et savoir réparer les petites pannes!',
    'Snowboard rando' => 'Carte CAF, doudoune, frontale, gants rechange, bonnet rechange, lunettes de soleil, crème solaire, appareil photos. SANS OUBLIER : DVA, sonde, pelle qui peuvent être prêtés par le CAF contre participation aux frais, boots, splitboard, bâtons, peaux, couteaux, visserie de rechange. Casque recommandé',
];
