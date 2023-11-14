<?php
if ($evt['join_max_evt'] > 0) {
    $style = null;
    if (is_array($my_choices) && isset($my_choices['is_covoiturage'])) {
        $style = " style='border:1px solid red; background:rgba(255,153,153,.4);' ";
    } ?>
    <div style="text-align:center; padding:0 30px 0 0">
        <br />
        <a class="biglink" href="javascript:void(0)" onclick="$('#inscription').slideToggle(200)" title="" <?php echo $style; ?> >
            <span class="bleucaf">&gt;</span>
            <?php if (!$my_choices) { ?>
                JE SOUHAITE REJOINDRE CETTE SORTIE
            <?php } else { ?>
                JE METS A JOUR MES PREFERENCES <?php if (null !== $style) { ?><img src="/img/base/bullet_error.png" title="Mettre à jour les préférences" width="16px">&nbsp;<?php } ?>
            <?php } ?>
            <?php
            // incriptions de filiation ?
            if (count($filiations)) {
                echo '<span class="bleucaf">&nbsp; / &nbsp;</span> INSCRIRE DES ADHÉRENTS AFFILIÉS';
            } ?>
        </a>
    </div>
<?php
} else {
    echo '<hr /><div class="alerte">Pas d\'inscription par internet</div>';
}
