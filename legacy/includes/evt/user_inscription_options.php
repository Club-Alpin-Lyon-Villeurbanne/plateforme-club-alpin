<?php

use App\Legacy\LegacyContainer;

$URL_PAIEMENT = LegacyContainer::getParameter('legacy_env_URL_PAIEMENT');

?>
<p>
    <label style="width:100%;clear:both;overflow:hidden;">
        <input type="checkbox" name="confirm" /> J'ai lu les conditions d'usage ci-dessus et confirme ma demande d'inscription.
    </label>
</p>
<hr class="clear">

<?php /* BESOIN DE BENEVOLES */ if ($evt['need_benevoles_evt'] && !$my_choices) { ?>
    <p>
        <label style="width:100%;clear:both;overflow:hidden;">
            <input type="checkbox" name="jeveuxetrebenevole" /> Cochez cette case si vous souhaitez rejoindre la sortie en tant que <b>bénévole</b>.
        </label>
    </p>
<?php } ?>

<?php /* EXISTANCE DE FILIATIONS, seulement lors de la création, pas de l'update */ if (count($filiations)) { ?>
    <?php if (!is_array($_POST['id_user_filiation']) && user()) {
        $_POST['id_user_filiation'] = [(string) getUser()->getId()];
    } ?>

    <hr class="clear" />
    <?php inclure('inscrire-filiation-select'); ?>

    <input type="hidden" name="filiations" value="on" />
    <br />
    <label for="filiation_id_user_<?php echo user() ? getUser()->getId() : ''; ?>" style="width:100%;clear:both;overflow:hidden;">
        <input type="checkbox"
            <?php echo user() && in_array((string) getUser()->getId(), $_POST['id_user_filiation'], true) ? 'checked="checked" ' : ''; ?>
            class="custom"
            name="id_user_filiation[]"
            value="<?php echo user() ? getUser()->getId() : ''; ?>"
            id="filiation_id_user_<?php echo user() ? getUser()->getId() : ''; ?>" />
                Moi-même (<?php echo user() ? userlink(getUser()->getId(), getUser()->getNickname()) : ''; ?>)

    </label>
    <br />
    <?php foreach ($filiations as $enfant) {
        $title = iconv('UTF-8', 'ASCII//TRANSLIT', str_replace(')', '', str_replace('(', '', str_replace('!', '', $evt['titre_evt']))));
        $compl = ' du '.date('d-m-Y', $evt['tsp_evt']).' '.$enfant['firstname_user'].' '.$enfant['lastname_user'];
        $size_title = strlen($title);
        $size_compl = strlen($compl);
        $new_title = substr($title, 0, 64 - $size_compl).$compl;

        if ($evt['joins']['encadrant'][0]) {
            $encadrant_name = $evt['joins']['encadrant'][0]['firstname_user'].' '.$evt['joins']['encadrant'][0]['lastname_user'];
        } elseif ($evt['joins']['stagiaire'][0]) {
            $encadrant_name = $evt['joins']['stagiaire'][0]['firstname_user'].' '.$evt['joins']['stagiaire'][0]['lastname_user'];
        } elseif ($evt['joins']['coencadrant'][0]) {
            $encadrant_name = $evt['joins']['coencadrant'][0]['firstname_user'].' '.$evt['joins']['coencadrant'][0]['lastname_user'];
        }
        if ($evt['joins']['encadrant'][0]) {
            $encadrant_email = $evt['joins']['encadrant'][0]['email_user'];
        } elseif ($evt['joins']['stagiaire'][0]) {
            $encadrant_email = $evt['joins']['stagiaire'][0]['email_user'];
        } elseif ($evt['joins']['coencadrant'][0]) {
            $encadrant_email = $evt['joins']['coencadrant'][0]['email_user'];
        } ?>

        <br />
        <label for="filiation_id_user_<?php echo (int) $enfant['id_user']; ?>" style="width:100%;clear:both;overflow:hidden;">
            <input
                type="checkbox" <?php echo in_array($enfant['id_user'], $_POST['id_user_filiation'], true) ? 'checked="checked" ' : ''; ?>
                onclick="<?php echo "$('#paiement_enfant_".(int) $enfant['id_user']."').slideToggle(200)"; ?>"
                class="custom"
                name="id_user_filiation[]"
                value="<?php echo (int) $enfant['id_user']; ?>"
                id="filiation_id_user_<?php echo (int) $enfant['id_user']; ?>" />
            <?php echo ucfirst(strtolower($enfant['lastname_user'])).', '.$enfant['firstname_user'].' ('.userlink($enfant['id_user'], $enfant['nickname_user']).')'; ?>
        </label>
    <?php
    } ?>
    <hr class="clear" />
<?php }  ?>
