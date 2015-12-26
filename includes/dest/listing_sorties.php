<?php if ($destination['sorties']) { ?>
    <?php
    $list = null;
    for($i=0; $i<sizeof($destination['sorties']); $i++){
        $sortie=$destination['sorties'][$i];
        $list_elt = '<li class="wide">'.
            '<a href="sortie/'.html_utf8($sortie['code_evt']).'-'.intval($sortie['id_evt']).'.html?commission='.$sortie['code_commission'].'" title="Voir la sortie">'.
            ($sortie['status_evt'] == 0 ? '[En attente de publication] ' : ($sortie['status_evt'] == 2 ? '[Refusé] ':'')).
            '<span class="bleucaf">'.html_utf8($sortie['titre_evt']).'</span> '.
            ($sortie['groupe']?' <small>('.$sortie['groupe']['nom'].')</small>':'').
            ' - '.
            ($sortie['cancelled_evt']?'<span class="cancelled">Sortie annulée</span>':'<span style="color:#4D4D4D">'.html_utf8($sortie['title_commission']).'</span>').
            '</a>'.
            '</li>';
        if ($sortie['status_evt'] == 1) {
            $list .= $list_elt;
        } else {
            if (
                $destination['id_user_who_create'] == $_SESSION['user']['id_user']
                || $destination['id_user_who_create'] == $_SESSION['user']['id_user']
                || $destination['id_user_who_create'] == $_SESSION['user']['id_user']) {
                $list .= $list_elt;
            }
        }
    }
    if (!is_null($list)) { echo '<ul class="nice-list">'.$list.'</ul>';} else { ?>
        <p>Aucune sortie publiée pour le moment.</p>
    <?php } ?>
<?php } else { ?>
    <p>Aucune sortie enregistrée pour cette destination.</p>
<?php } ?>