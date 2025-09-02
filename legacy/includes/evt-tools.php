<?php

use App\Legacy\LegacyContainer;

if ($evt) {
    echo '<div class="evt-tools">'

        // APERCU : je suis l'auteur et cet événement n'a pas encore été publié :
        . (user() && $evt['user_evt'] == (string) getUser()->getId() && 1 != $evt['status_evt'] ? '<a class="nice2" href="/sortie/' . html_utf8($evt['code_evt']) . '-' . (int) $evt['id_evt'] . '.html?forceshow=true' . ($current_commission ? '&amp;commission=' . $current_commission : '') . '" title="Aperçu de la page dédiée à la sortie ci-dessous">Aperçu</a>' : '')

        // MODIFIER : J'ai le droit de modifier les evts de cette commission :
        . (allowed('evt_edit', 'commission:' . $evt['code_commission']) ? '<a class="nice2 orange" href="' . LegacyContainer::get('router')->generate('modifier_sortie', ['event' => (int) $evt['id_evt']]) . '" title="Modifier la sortie ci-dessous et les encadrants liés">Modifier</a>' : '')

        // ANNULATION : PEU IMPORTE SI J'EN SUIS AUTEUR ICI : J'ai le droit d'annuler les evts de cette commission :
        // on ne peut annuler une sortie que si elle est deja validée
        // et pas deja passée ni en cours
        // ni deja annulée
        . (allowed('evt_cancel', 'commission:' . $evt['code_commission']) && 1 == $evt['status_evt'] && $evt['tsp_evt'] > time() && 0 == $evt['cancelled_evt'] ? '<a class="nice2 red" href="' . LegacyContainer::get('router')->generate('cancel_event', ['id' => (int) $evt['id_evt']]) . '" title="Annuler la sortie ci-dessous">Annuler</a>' : '')

        // SUPPRIMER
        // on ne peut supprimer que si elle n'est pas publiée OU annulée
        . (((allowed('evt_delete') || $evt['user_evt'] == getUser()->getId()) && ($evt['cancelled_evt'] || 1 != $evt['status_evt']))
            ? '<a class="nice2 red" href="/supprimer-une-sortie/' . html_utf8($evt['code_evt']) . '-' . (int) $evt['id_evt'] . '.html" title="Supprimer définitivement la sortie ci-dessous">Supprimer</a>' : '')

    . '</div>';
}
