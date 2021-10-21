<?php /* VALIDATION / ACCEPTATION DES CGU */ ?>
<p>
    <label style="width:100%;clear:both;overflow:hidden;">
        <input type="checkbox" name="confirm" /> J'ai lu les conditions d'usage ci-dessus et confirme ma demande d'inscription.
    </label>
</p>
<hr class="clear">

<?php /* Paiement en ligne */ if ($evt['cb_evt']) { ?>

    <?php if (1 == $_POST['is_cb']) {
    $my_choices['is_cb'] = $_POST['is_cb'];
}

        $title = iconv('UTF-8', 'ASCII//TRANSLIT', str_replace(')', '', str_replace('(', '', str_replace('!', '', $evt['titre_evt']))));
        $compl = ' du '.date('d-m-Y', $evt[tsp_evt]).' '.$_SESSION['user']['firstname_user'].' '.$_SESSION['user']['lastname_user'];
        $size_title = strlen($title);
        $size_compl = strlen($compl);
        $new_title = substr($title, 0, 64 - $size_compl).$compl;

        if ($evt['joins']['encadrant'][0]) {
            $encadrant_name = $evt['joins']['encadrant'][0]['firstname_user'].' '.$evt['joins']['encadrant'][0]['lastname_user'];
        } elseif ($evt['joins']['coencadrant'][0]) {
            $encadrant_name = $evt['joins']['coencadrant'][0]['firstname_user'].' '.$evt['joins']['coencadrant'][0]['lastname_user'];
        }
        if ($evt['joins']['encadrant'][0]) {
            $encadrant_email = $evt['joins']['encadrant'][0]['email_user'];
        } elseif ($evt['joins']['coencadrant'][0]) {
            $encadrant_email = $evt['joins']['coencadrant'][0]['email_user'];
        }

    ?>

    <input type="hidden" name="is_cb" value="1">
    <p>
        <label style="width:100%;clear:both;overflow:hidden;">
            <input type="checkbox" name="jeveuxpayerenligne" onclick="$('.paiement').slideToggle(200)"  <?php echo (1 == $my_choices['is_cb']) ? ' checked="checked" ' : ''; ?> />
            Cochez cette case si vous souhaitez payer en ligne
            <div class="paiement" style="display:<?php if (1 == $my_choices['is_cb']) {
        echo 'block';
    } else {
        echo 'none';
    } ?>">
            <a style="border:1px solid red; background:rgba(255,153,153,.4);"
                href="<?php echo $p_url_paiement; ?>
?lck_vads_order_id=<?php echo rawurlencode(html_utf8($new_title)); ?>
&lck_vads_ext_info_Encadrant=<?php echo rawurlencode(html_utf8($encadrant_name)); ?>
&lck_vads_ext_info_E-mail%20encadrant=<?php echo rawurlencode(html_utf8($encadrant_email)); ?>
&lck_vads_ext_info_Sortie=<?php echo rawurlencode(html_utf8($title.' - '.$evt['id_evt'].' du '.date('d-m-Y', $evt[tsp_evt]))); ?>
&lck_vads_cust_first_name=<?php echo rawurlencode(html_utf8($_SESSION['user']['firstname_user'])); ?>
&lck_vads_cust_last_name=<?php echo rawurlencode(html_utf8($_SESSION['user']['lastname_user'])); ?>
&lck_vads_cust_id=<?php echo rawurlencode(html_utf8($_SESSION['user']['cafnum_user'])); ?>
&lck_vads_cust_email=<?php echo rawurlencode(html_utf8($_SESSION['user']['email_user'])); ?>
&lck_vads_cust_cell_phone=<?php echo rawurlencode(html_utf8($_SESSION['user']['tel_user'])); ?>
&lck_vads_amount=<?php echo rawurlencode(html_utf8($evt['tarif_evt'])); ?>"
                 target="_blank" alt="paiement en ligne">Cliquez ici  pour payer en ligne (avant de valider la demande d'inscription)</a>.
            <br />
            ATTENTION : le paiement en ligne n'implique pas une inscription automatique à la sortie, la validation
            de l'inscription reste à la discrétion des encadrants !
            </div>
        </label>
    </p>
<?php } ?>

<?php /* REPAS AU RESTAURANT */ if ($evt['repas_restaurant']) { ?>
    <input type="hidden" name="is_restaurant" value="1">
    <p>
        <label style="width:100%;clear:both;overflow:hidden;">
            <input type="checkbox" name="jeveuxmangerauresto" <?php echo 1 == $my_choices['is_restaurant'] ? ' checked="checked" ' : ''; ?> />
            Cochez cette case si vous souhaitez profiter du <b>repas au restaurant</b>, pour un tarif de <b><?php echo $evt['tarif_restaurant']; ?> &euro;</b> (généralement hors consommations).
        </label>
    </p>
<?php } ?>

<?php /* BESOIN DE BENEVOLES */ if ($evt['need_benevoles_evt'] && !$my_choices) { ?>
    <p>
        <label style="width:100%;clear:both;overflow:hidden;">
            <input type="checkbox" name="jeveuxetrebenevole" /> Cochez cette case si vous souhaitez rejoindre la sortie en tant que <b>bénévole</b>.
        </label>
    </p>
<?php } ?>

<?php /* EXISTANCE DE FILIATIONS, seulement lors de la création, pas de l'update */ if (count($filiations)) { ?>
    <?php if (!is_array($_POST['id_user_filiation'])) {
        $_POST['id_user_filiation'] = [$_SESSION['user']['id_user']];
    } ?>

    <hr class="clear" />
    <?php inclure('inscrire-filiation-select'); ?>

    <input type="hidden" name="filiations" value="on" />
    <br />
    <label for="filiation_id_user_<?php echo (int) ($_SESSION['user']['id_user']); ?>" style="width:100%;clear:both;overflow:hidden;">
        <input type="checkbox"
            <?php echo in_array($_SESSION['user']['id_user'], $_POST['id_user_filiation'], true) ? 'checked="checked" ' : ''; ?>
            class="custom"
            name="id_user_filiation[]"
            value="<?php echo (int) ($_SESSION['user']['id_user']); ?>"
            id="filiation_id_user_<?php echo (int) ($_SESSION['user']['id_user']); ?>" />
                Moi-même (<?php echo userlink($_SESSION['user']['id_user'], $_SESSION['user']['nickname_user']); ?>)

    </label>
    <br />
    <?php foreach ($filiations as $enfant) {
        $title = iconv('UTF-8', 'ASCII//TRANSLIT', str_replace(')', '', str_replace('(', '', str_replace('!', '', $evt['titre_evt']))));
        $compl = ' du '.date('d-m-Y', $evt[tsp_evt]).' '.$enfant['firstname_user'].' '.$enfant['lastname_user'];
        $size_title = strlen($title);
        $size_compl = strlen($compl);
        $new_title = substr($title, 0, 64 - $size_compl).$compl;

        if ($evt['joins']['encadrant'][0]) {
            $encadrant_name = $evt['joins']['encadrant'][0]['firstname_user'].' '.$evt['joins']['encadrant'][0]['lastname_user'];
        } elseif ($evt['joins']['coencadrant'][0]) {
            $encadrant_name = $evt['joins']['coencadrant'][0]['firstname_user'].' '.$evt['joins']['coencadrant'][0]['lastname_user'];
        }
        if ($evt['joins']['encadrant'][0]) {
            $encadrant_email = $evt['joins']['encadrant'][0]['email_user'];
        } elseif ($evt['joins']['coencadrant'][0]) {
            $encadrant_email = $evt['joins']['coencadrant'][0]['email_user'];
        } ?>

        <br />
        <label for="filiation_id_user_<?php echo (int) ($enfant['id_user']); ?>" style="width:100%;clear:both;overflow:hidden;">
            <input
                type="checkbox" <?php echo in_array($enfant['id_user'], $_POST['id_user_filiation'], true) ? 'checked="checked" ' : ''; ?>
                onclick="<?php echo "$('#paiement_enfant_".(int) ($enfant['id_user'])."').slideToggle(200)"; ?>"
                class="custom"
                name="id_user_filiation[]"
                value="<?php echo (int) ($enfant['id_user']); ?>"
                id="filiation_id_user_<?php echo (int) ($enfant['id_user']); ?>" />
                    <?php echo ucfirst(strtolower($enfant['lastname_user'])).', '.$enfant['firstname_user'].' ('.userlink($enfant['id_user'], $enfant['nickname_user']).')'; ?>
                <?php if ('1' == $evt['cb_evt']) { ?>
                <div id="<?php echo 'paiement_enfant_'.(int) ($enfant['id_user']); ?>" style="display:<?php if (1 == $my_choices['is_cb']) {
            echo 'hidden';
        } else {
            echo 'none';
        } ?>">
                <a style="border:1px solid red; background:rgba(255,153,153,.4);"
                    href="<?php echo $p_url_paiement; ?>
?lck_vads_order_id=<?php echo rawurlencode(html_utf8($new_title)); ?>
&lck_vads_ext_info_Encadrant=<?php echo rawurlencode(html_utf8($encadrant_name)); ?>
&lck_vads_ext_info_E-mail%20encadrant=<?php echo rawurlencode(html_utf8($encadrant_email)); ?>
&lck_vads_ext_info_Sortie=<?php echo rawurlencode(html_utf8($title.' - '.$evt['id_evt'].' du '.date('d-m-Y', $evt[tsp_evt]))); ?>
&lck_vads_cust_first_name=<?php echo rawurlencode(html_utf8($enfant['firstname_user'])); ?>
&lck_vads_cust_last_name=<?php echo rawurlencode(html_utf8($enfant['lastname_user'])); ?>
&lck_vads_cust_id=<?php echo rawurlencode(html_utf8($enfant['cafnum_user'])); ?>
&lck_vads_cust_email=<?php echo rawurlencode(html_utf8($enfant['email_user'])); ?>
&lck_vads_cust_cell_phone=<?php echo rawurlencode(html_utf8($enfant['tel_user'])); ?>
&lck_vads_amount=<?php echo rawurlencode(html_utf8($evt['tarif_evt'])); ?>"
                     target="_blank" alt="paiement en ligne">Cliquez ici pour payer en ligne (avant de valider la demande d'inscription)</a>
                </div>
                <?php } ?>
        </label>
    <?php
    } ?>
    <hr class="clear" />
<?php }  ?>
