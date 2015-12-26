<?php /* REPAS AU RESTAURANT */ if ($evt['repas_restaurant']) { ?>
    <input type="hidden" name="is_restaurant" value="1">
    <p>
        <label style="width:100%;clear:both;overflow:hidden;">
            <input type="checkbox" name="jeveuxmangerauresto" <?php echo ($my_choices['is_restaurant'] == 1 ? ' checked="checked" ':'') ; ?> />
            Cochez cette case si vous souhaitez profiter du <b>repas au restaurant</b>, pour un tarif de <b><?php echo $evt['tarif_restaurant']; ?> &euro;</b> (généralement hors consommations).
        </label>
    </p>
<?php } ?>

<?php /* BESOIN DE BENEVOLES */ if($evt['need_benevoles_evt'] && !$my_choices){ ?>
    <p>
        <label style="width:100%;clear:both;overflow:hidden;">
            <input type="checkbox" name="jeveuxetrebenevole" /> Cochez cette case si vous souhaitez rejoindre la sortie en tant que <b>bénévole</b>.
        </label>
    </p>
<?php } ?>

<?php /* EXISTANCE DE FILIATIONS, seulement lors de la création, pas de l'update */ if(sizeof($filiations)){ ?>
    <?php if(!is_array($_POST['id_user_filiation'])) $_POST['id_user_filiation'] = array($_SESSION['user']['id_user']); ?>

    <hr class="clear" />
    <?php inclure('inscrire-filiation-select'); ?>

    <input type="hidden" name="filiations" value="on" />
    <br />
    <label for="filiation_id_user_<?php echo intval($_SESSION['user']['id_user']); ?>" style="width:100%;clear:both;overflow:hidden;">
        <input type="checkbox"
            <?php echo (in_array($_SESSION['user']['id_user'], $_POST['id_user_filiation'])? 'checked="checked" ' : ''); ?>
            class="custom"
            name="id_user_filiation[]"
            value="<?php echo intval($_SESSION['user']['id_user']); ?>"
            id="filiation_id_user_<?php echo intval($_SESSION['user']['id_user']); ?>" />
                Moi-même (<?php echo userlink($_SESSION['user']['id_user'], $_SESSION['user']['nickname_user']); ?>)
    </label>
    <br />
    <?php foreach($filiations as $enfant){ ?>
        <br />
        <label for="filiation_id_user_<?php echo intval($enfant['id_user']); ?>" style="width:100%;clear:both;overflow:hidden;">
            <input
                type="checkbox" <?php echo (in_array($enfant['id_user'], $_POST['id_user_filiation'])? 'checked="checked" ' : ''); ?>
                class="custom"
                name="id_user_filiation[]"
                value="<?php echo intval($enfant['id_user']); ?>"
                id="filiation_id_user_<?php echo intval($enfant['id_user']); ?>" />
                    <?php echo ucfirst(strtolower($enfant['lastname_user'])).', '.$enfant['firstname_user'].' ('.userlink($enfant['id_user'], $enfant['nickname_user']).')'; ?>
        </label>
    <?php } ?>
    <hr class="clear" />
<?php  }  ?>

<?php /* VALIDATION / ACCEPTATION DES CGU */ ?>
<p>
    <label style="width:100%;clear:both;overflow:hidden;">
        <input type="checkbox" name="confirm" /> J'ai lu les conditions d'usage ci-dessus et confirme ma demande d'inscription.
    </label>
</p>
<hr class="clear">