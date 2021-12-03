<?php if ($destination['sorties']) { ?>
    <?php
    $list = null;
    for ($i = 0; $i < count($destination['sorties']); ++$i) {
        $sortie = $destination['sorties'][$i];
        $list_elt = '<li class="wide">'.
            '<a href="sortie/'.html_utf8($sortie['code_evt']).'-'.(int) ($sortie['id_evt']).'.html?commission='.$sortie['code_commission'].'" title="Voir la sortie">'.
            (0 == $sortie['status_evt'] ? '[En attente de publication] ' : (2 == $sortie['status_evt'] ? '[Refusé] ' : '')).
            '<span class="bleucaf">'.html_utf8($sortie['titre_evt']).'</span> '.
            ($sortie['groupe'] ? ' <small>('.$sortie['groupe']['nom'].')</small>' : '').
            ' - '.
            ($sortie['cancelled_evt'] ? '<span class="cancelled">Sortie annulée</span>' : '<span style="color:#4D4D4D">'.html_utf8($sortie['title_commission']).'</span>').
            '</a>'.
            '</li>';
        if (1 == $sortie['status_evt']) {
            $list .= $list_elt;
        } else {
            if (
                (user() && $destination['id_user_who_create'] == (string) getUser()->getIdUser())
                || (user() && $destination['id_user_who_create'] == (string) getUser()->getIdUser())
                || (user() && $destination['id_user_who_create'] == (string) getUser()->getIdUser())) {
                $list .= $list_elt;
            }
        }
    }
    if (null !== $list) {
        echo '<ul class="nice-list">'.$list.'</ul>';
    } else { ?>
        <p>Aucune sortie publiée pour le moment.</p>
    <?php } ?>
<?php } else { ?>
    <p>Aucune sortie enregistrée pour cette destination.</p>
<?php } ?>