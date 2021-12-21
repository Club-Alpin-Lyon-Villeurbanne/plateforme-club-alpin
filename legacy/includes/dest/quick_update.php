<?php

// Modération

if (
    allowed('destination_activer_desactiver') ||
    allowed('destination_supprimer') ||
    (user() && $destination['id_user_who_create'] == (string) getUser()->getId()) ||
    (user() && $destination['id_user_responsable'] == (string) getUser()->getId()) ||
    (user() && $destination['id_user_adjoint'] == (string) getUser()->getId())
) {
    if (
        allowed('destination_activer_desactiver') ||
        (user() && $destination['id_user_who_create'] == (string) getUser()->getId()) ||
        (user() && $destination['id_user_responsable'] == (string) getUser()->getId()) ||
        (user() && $destination['id_user_adjoint'] == (string) getUser()->getId())
    ) {
        echo 0 == (int) ($destination['publie']) ?
            '<form action="'.$versCettePage.'" method="post" style="display:inline" class="loading">
                <input type="hidden" name="operation" value="dest_validate" />
                <input type="hidden" name="publie" value="1" />
                <input type="hidden" name="id_dest_to_update" value="'.((int) ($destination['id'])).'" />
                <input type="submit" value="Publier" class="nice2 green" title="Rend la destination disponible" />
            </form>' :
            '<form action="'.$versCettePage.'" method="post" style="display:inline" class="loading">
                <input type="hidden" name="operation" value="dest_validate" />
                <input type="hidden" name="publie" value="0" />
                <input type="hidden" name="id_dest_to_update" value="'.((int) ($destination['id'])).'" />
                <input type="submit" value="Masquer" class="nice2 orange" title="Masque la destination et ses sorties" />
            </form>';
    }

    if (
        allowed('destination_activer_desactiver') ||
        (user() && $destination['id_user_who_create'] == (string) getUser()->getId()) ||
        (user() && $destination['id_user_responsable'] == (string) getUser()->getId()) ||
        (user() && $destination['id_user_adjoint'] == (string) getUser()->getId())
    ) {
        echo 0 == (int) ($destination['annule'])
            ? (date('Y-m-d H:i:s') > $destination['inscription_fin'] ? '' : (0 == (int) ($destination['inscription_locked'])
                ? '<form action="'.$versCettePage.'" method="post" style="display:inline" class="loading">
                        <input type="hidden" name="operation" value="dest_lock" />
                        <input type="hidden" name="inscription_locked" value="1" />
                        <input type="hidden" name="id_dest_to_update" value="'.((int) ($destination['id'])).'" />
                        <input type="submit" value="Bloquer inscr." class="nice2 orange" title="Bloquer les inscriptions aux sorties" />
                    </form>'
                : '<form action="'.$versCettePage.'" method="post" style="display:inline" class="loading">
                        <input type="hidden" name="operation" value="dest_lock" />
                        <input type="hidden" name="inscription_locked" value="0" />
                        <input type="hidden" name="id_dest_to_update" value="'.((int) ($destination['id'])).'" />
                        <input type="submit" value="Autoriser inscr." class="nice2 green" title="Ouvrir les inscriptions aux sorties" />
                    </form>'
            ))
            : '<form action="'.$versCettePage.'" method="post" style="display:inline" class="loading">
                        <input type="hidden" name="operation" value="dest_annuler" />
                        <input type="hidden" name="annule" value="0" />
                        <input type="hidden" name="id_dest_to_update" value="'.((int) ($destination['id'])).'" />
                        <input type="submit" value="Rétablir" class="nice2 orange" title="Rétablir la destination" />
                    </form>'
        ;
    }
    if (
        allowed('destination_supprimer') ||
        (user() && $destination['id_user_who_create'] == (string) getUser()->getId()) ||
        (user() && $destination['id_user_responsable'] == (string) getUser()->getId()) ||
        (user() && $destination['id_user_adjoint'] == (string) getUser()->getId())
    ) {
        echo 0 == (int) ($destination['annule'])
                ? '<a class="nice2 noprint red" href="annuler-une-sortie/destination/'.$destination['code'].'-'.(int) ($destination['id']).'.html" title="Annuler la destination ci-dessous et toutes les sorties">
				<img src="/img/base/delete.png" alt="" title="" style="">&nbsp;&nbsp;Annuler</a>'
                : ''
            ;
    }
}
