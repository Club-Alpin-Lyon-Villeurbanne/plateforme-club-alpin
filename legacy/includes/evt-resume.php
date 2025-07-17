<?php

if (!$evt) {
    echo '<p class="erreur">Erreur : événement non défini</p>';
} else {
    // commission
    echo '<p class="bleucaf">' . html_utf8($evt['title_commission']) . '</p>'
    // titre
    . '<h1 style="padding-top:0">
		<span class="bleucaf">&gt;</span> ' . html_utf8($evt['titre_evt']) . '
		<span class="mini"> / ' . jour(date('N', $evt['tsp_evt'])) . ' ' . date('d', $evt['tsp_evt']) . ' ' . mois(date('m', $evt['tsp_evt'])) . '</span>
	</h1>'

    // LISTE D'INFOS DIFFICULTE ETC...
    . '<ul class="nice-list">'

        // massif ?
        . ($evt['massif_evt'] ?
            '<li><b>MASSIF :</b> ' . html_utf8($evt['massif_evt']) . '</li>'
        : '')

         // lieu départ ?
         . ($evt['place_evt'] ?
            '<li><b>LIEU DÉPART ACTIVITÉ :</b> ' . html_utf8($evt['place_evt']) . '</li>'
        : '')

        // denivele ?
        . ($evt['denivele_evt'] ?
            '<li><b>DÉNIVELÉ :</b> ' . html_utf8($evt['denivele_evt']) . ' m</li>'
        : '')

        // difficulte ?
        . ($evt['difficulte_evt'] ?
            '<li class="wide"><b>DIFFICULTÉ :</b> ' . html_utf8($evt['difficulte_evt']) . '</li>'
        : '')

        // materiel ?
        . ('' != $evt['matos_evt'] ?
            '<li class="wide"><b>MATÉRIEL :</b> ' . nl2br(html_utf8(trim($evt['matos_evt']))) . '</li>'
        : '')

    . '</ul>'

    . '<br style="clear:both" />'
    . '<hr />';

    // CONTENU LIBRE
    echo ''
    . '<div class="description_evt">' . $evt['description_evt'] . '</div>'
    . '<hr style="clear:both" /><br />';

    // LISTE D'INFOS TRANSPORTS
    echo ''
    . '<ul class="nice-list">'
        // rdv : heure
        . '<li><b>DÉPART :</b> Le ' . date('d', $evt['tsp_evt']) . ' ' . mois(date('m', $evt['tsp_evt'])) . ' ' . date('Y', $evt['tsp_evt']) . ', ' . date('H:i', $evt['tsp_evt']) . '.</li>'
        // retour : le meme jour ou un autre jour
        . '<li><b>RETOUR :</b> ' . (date('dmy', $evt['tsp_evt']) == date('dmy', $evt['tsp_end_evt']) ? 'Le même jour' : 'Le ' . date('d', $evt['tsp_evt']) . ' ' . mois(date('m', $evt['tsp_evt']))) . '.</li>'
        // rdv : lieu
        . '<li><b>RDV :</b> ' . html_utf8($evt['rdv_evt']) . '</li>'
        // tarif ?
        . ($evt['tarif_evt'] > 0 ?
            '<li><b>TARIF :</b> ' . (float) $evt['tarif_evt'] . ' Euros</li>'
        : '')
    . '</ul><br style="clear:both" />';
}
