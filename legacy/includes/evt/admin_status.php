<?php

    // evt trouvé mais normalement pas visible, c'est le cas d'un mode admin ou validateur

    $messageDiv = false;

    if ('0' == $evt['status_evt']) {
        //pas validee
        $messageDiv = true;

        echo '<div class="alerte"><b>Note : Cette sortie n\'est pas publiée sur le site</b>. Si vous voyez ce message apparaître, c\'est que vous disposez de droits particuliers qui vous autorisent à voir cette page. Les usagers réguliers du site n\'ont pas accès aux informations ci-dessous.<br />';

        if ((allowed('evt_validate_all') || allowed('evt_validate', 'commission:'.$evt['code_commission'])) && (1 != $evt['status_evt'] || (1 == $evt['status_evt'] && 1 == $evt['cancelled_evt']))) {
            echo '<div><form action="'.$versCettePage.'" method="post" class="loading" style="display:inline"><input type="hidden" name="operation" value="evt_validate" />';
            echo '<input type="hidden" name="status_evt" value="1" />';
            echo '<input type="hidden" name="id_evt" value="'.((int) ($evt['id_evt'])).'" />';
            echo '<input type="submit" value="Autoriser &amp; publier" class="nice2 green" title="Autorise instantanément la publication de la sortie" />';
            echo '</form>';
            echo '<input type="button" value="Refuser" class="nice2 red" onclick="$.fancybox($(this).next().html())" title="Ne pas autoriser la publication de cette sortie. Vous devrez ajouter un message au créateur de la sortie." />
				<div style="display:none" id="refuser-'.(int) ($evt['id_evt']).'">
					<form action="'.$versCettePage.'?forceshow=true" method="post" class="loading">
						<input type="hidden" name="operation" value="evt_validate" />
						<input type="hidden" name="status_evt" value="2" />
						<input type="hidden" name="id_evt" value="'.((int) ($evt['id_evt'])).'" />

						<p>Laissez un message à l\'auteur pour lui expliquer la raison du refus :</p>
						<input type="text" name="msg" class="type1" placeholder="ex: Mauvais point de RDV" />
						<input type="submit" value="Refuser la publication" class="nice2 red" />
						<input type="button" value="Annuler" class="nice2" onclick="$.fancybox.close()" />
					</form>
				</div></div>';
        }
    } elseif ('2' == $evt['status_evt']) {
        //refuse
        $messageDiv = true;
        echo '<div class="alerte"><b>Note : Cette sortie a été refusée</b>. Si vous voyez ce message apparaître, c\'est que vous disposez de droits particuliers qui vous autorisent à voir cette page. Les usagers réguliers du site n\'ont pas accès aux informations ci-dessous.<br /><br />';
    } elseif ('1' == $evt['cancelled_evt']) {
        // evt deja annule, possibilite de le reactiver
        $messageDiv = true;
        echo '<div class="erreur"><img src="/img/base/cross.png" alt="" title="" style="float:left; padding:2px 6px 0 0;" /> <b>Sortie annulée :</b><br /> Cette sortie a été annulée le '.date('d/m/Y à H:i');
        if ($evt['cancelled_who_evt'] && $evt['cancelled_who_evt']['id_user']) {
            echo ', par '.userlink($evt['cancelled_who_evt']['id_user'], $evt['cancelled_who_evt']['nickname_user']);
        } else {
            echo ', suite à annulation de la destination.';
        }
        echo '<br />';
        if (user() && allowed('evt_cancel')) {
            echo '<form action="'.$versCettePage.'" method="post" class="loading"><input type="hidden" name="operation" value="evt_uncancel" />';
            echo '<a href="javascript:void(0)" title="Réactiver" class="nice2 red" onclick="$(this).parents(\'form\').submit()">Réactiver cette sortie</a>';
            echo '</form>';
        }
    } elseif ('1' == $evt['status_evt'] && (allowed('evt_validate_all') || allowed('evt_validate', 'commission:'.$evt['code_commission']))) {
        // evt publie
        $messageDiv = true;

        if (isset($destination)) {
            if (0 == is_destination_status($destination, 'publie')) {
                $destination_publiee = false;
            }
        }
        if (isset($destination_publiee) && !$destination_publiee) {
            echo '<div class="alerte"><b>Note :</b> Cette sortie n\'est pas publiée, elle fait partie d\'une destination qui n\'est pas encore publiée !<br />';
        } else {
            echo '<div class="alerte"><b>Note :</b> Cette sortie est publiée et visible par les adhérents !<br />';
        }
    }

    // j'en suis l'auteur ? Elle est pas validée ? modification possible !
    if ((user() && $evt['user_evt'] == (string) getUser()->getIdUser()) || allowed('evt_validate_all') || allowed('evt_validate', 'commission:'.$evt['code_commission'])) {
        if (1 != $evt['cancelled_evt']) {
            echo '<a href="creer-une-sortie/'.$evt['code_commission'].'/update-'.$evt['id_evt'].'.html" title="Vous êtes l\'auteur de cette sortie ? Cliquez ici pour la modifier." class="nice2 noprint orange"><img src="/img/base/pencil.png" alt="" title="" style="" />&nbsp;&nbsp;Modifier cette sortie</a>';
        }

        // sortie à venir
        if ($evt['tsp_end_evt'] > time()) {
            if (allowed('evt_delete', 'commission:'.$evt['code_commission']) && (1 != $evt['status_evt'] || (1 == $evt['status_evt'] && 1 == $evt['cancelled_evt']))) {
                // supprimer
                echo '<a class="nice2 noprint red" href="supprimer-une-sortie/'.html_utf8($evt['code_evt']).'-'.(int) ($evt['id_evt']).'.html" title="Supprimer définitivement la sortie ci-dessous"><img src="/img/base/x2.png" alt="" title="" style="" />&nbsp;&nbsp;Supprimer cette sortie</a>';
            } elseif (allowed('evt_cancel', 'commission:'.$evt['code_commission']) && '1' != $evt['cancelled_evt']) {
                //annuler
                echo '<a class="nice2 noprint red" href="annuler-une-sortie/'.html_utf8($evt['code_evt']).'-'.(int) ($evt['id_evt']).'.html" title="Annuler la sortie ci-dessous">
				<img src="/img/base/delete.png" alt="" title="" style="" />&nbsp;&nbsp;Annuler cette sortie</a>';
            }
        }
    }

    if ($messageDiv) {
        echo '</div><br />';
    }
