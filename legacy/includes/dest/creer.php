<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

?>
<div style="padding:10px 0 0 30px; line-height:18px; ">

    <?php if (isset($errTab) && count($errTab) > 0) {
    echo '<div class="alerte"><p>Impossible de poursuivre cette opération : </p><ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
} ?>

    <?php
        $lock = '<div class="check-nice ">'.
                    '<label for="inscription_locked"
                            class="in_front" style="">'.
                        '<input
                            type="checkbox" name="inscription_locked" id="inscription_locked" class="custom" '.
                            ((in_array($_POST['inscription_locked'], [1, 'on'], true)) ? ' checked="checked" ' : '').
                            ' />  Bloquer les inscriptions à toutes les sorties de cette destination ?'.
                    '</label>'.
            '</div>';

        $new_lieu = display_new_lieu();

        $new_bus = '<div class="bus">'.
            '<p class="note"><small>Si plusieurs bus font le même trajet, avec les mêmes points de ramassage, merci de ne créer que 1 bus du nombre de places totales disponibles pour ce trajet.<br><b>Exemple</b>: 2 bus font le même trajet avec les mêmes lieux de ramasse, le premier fait 20 places, le second fait 50 places. Je créé 1 seul bus de 20+50 = 70 places.</small></p><br>'.
            '<div style="width:50%;float:left">'.
            '<label for="bus_intitule">Bus :</label>'.
            '<input type="text" name="newbus[\'+nbus+\'][intitule]" id="bus_intitule" class="type2" style="width:95%" value="'.inputVal('newbus|intitule', '').'" placeholder="ex: Bus \'+nbus+\'" value="Bus \'+nbus+\'">'.
            '</div>'.
            '<div style="width:50%;float:left">'.
            '<label for="places_max">Places maximum :</label>'.
            '<input type="text" name="newbus[\'+nbus+\'][places_max]" id="places_max" class="type2" style="width:95%" value="'.inputVal('newbus|places_max', '').'" placeholder="ex: 50">'.
            '</div>'.
            '<br class="clear">'.
            '<p><small>Une fois ce bus enregistré, vous pourrez gérer ses lieux de ramasse.</small></p>'.
            '<a href="" class="cancel_bus cancel">'.'Annuler ce bus'.'</a>'.
            '</div>';

    ?>

    <?php if (!isset($_POST['id_user_responsable']) && user()) {
        $_POST['id_user_responsable'] = (string) getUser()->getId();
    } ?>

    <?php if ($id_dest_to_update) {
        require __DIR__.'/../../includes/dest/admin_status.php';
    } ?>

    <form action="<?php echo $versCettePage; ?>" method="post">
        <input type="hidden" name="operation"
               value="<?php echo $id_dest_to_update ? 'dest_update' : 'dest_create'; ?>"/>
        <?php /* Création */ if (!$id_dest_to_update) { ?>
            <input type="hidden" name="id_user_who_create" value="<?php echo user() ? (string) getUser()->getId() : null; ?>"/>
        <?php } ?>
        <?php /* Modification */ if ($id_dest_to_update) { ?>
            <input type="hidden" name="id_dest_to_update" value="<?php echo (int) $id_dest_to_update; ?>"/>
        <?php } ?>

        <div id="infos-dest">
            <h2 class="trigger-h2">Informations sur la destination</h2>
            <div class="trigger-me">
                <div style="width:70%; float:left">
                    <label for="nom">Intitulé :</label><br>
                    <input type="text" name="nom"  id="nom" class="type1" style="width:95%" value="<?php echo inputVal('nom', ''); ?>" placeholder="ex: Journée découverte et sportive en altitude">
                </div>
                <br class="clear">
                <div style="width:50%; float:left">
                    <label for="date">Début :</label><br>
                    <input type="text" name="date"  id="date" class="type1 dtpick" style="width:90%" value="<?php echo inputVal('date', ''); ?>" placeholder="aaaa-mm-dd hh:ii:ss">
                </div>
                <div style="width:50%; float:left">
                    <label for="date_fin">Fin :</label><br>
                    <input type="text" name="date_fin"  id="date_fin" class="type1 dtpick" style="width:90%" value="<?php echo inputVal('date_fin', ''); ?>" placeholder="aaaa-mm-dd">
                </div>
            </div>
            <br class="clear">
            <?php echo $lock; ?>

        </div>

        <div id="individus">
            <h2 class="trigger-h2">Responsable :</h2>

            <div class="trigger-me radio-nice">
                <?php
                if (!count($select_leaders)) {
                    echo '<p class="info">Aucun adhérent n\'est déclaré <b>responsable</b> pour cette destination.</p>';
                } else {
                    foreach ($select_leaders as $id_user => $leader) {
                        echo '<label for="leader-'.$id_user.'"><p>';
                        echo '<input type="radio" '.($id_user == $_POST['id_user_responsable'] ? 'checked="checked"' : '').'
                                    name="id_user_responsable"
                                    value="'.$id_user.'"
                                    id="leader-'.$id_user.'" />&nbsp;';
                        echo $leader.'
                                <a class="fancyframe" href="includer.php?p=includes/fiche-profil.php&amp;id_user='.$id_user.'" title="Voir la fiche">
                                    <img src="/img/base/bullet_toggle_plus.png" alt="I" title="" />
                                </a>';
                        echo '</p></label>';
                    }
                }
                ?>
                <br style="clear:both" />
            </div>

            <h2 class="trigger-h2">Co-responsable :</h2>

            <div class="trigger-me radio-nice">
                <?php
                if (!count($select_leaders)) {
                    echo '<p class="info">Aucun adhérent n\'est déclaré <b>co-responsable</b> pour cette destination.</p>';
                } else {
                    foreach ($select_leaders as $id_user => $leader) {
                        echo '<label for="adjoint-'.$id_user.'"><p>';
                        echo '<input type="radio" '.($id_user == $_POST['id_user_adjoint'] ? 'checked="checked"' : '').'
                                    name="id_user_adjoint"
                                    value="'.$id_user.'"
                                    id="adjoint-'.$id_user.'" />&nbsp;';
                        echo $leader.'
                                <a class="fancyframe" href="includer.php?p=includes/fiche-profil.php&amp;id_user='.$id_user.'" title="Voir la fiche">
                                    <img src="/img/base/bullet_toggle_plus.png" alt="I" title="" />
                                </a>';
                        echo '</p></label>';
                    }
                    if ($_POST['id_user_adjoint']) {
                        echo '<label for="adjoint-none"><p>';
                        echo '<input type="radio"
                                        name="id_user_adjoint"
                                        value=""
                                        id="adjoint-none" />&nbsp;';
                        echo 'Supprimer le co-responsable';
                        echo '</p></label>';
                    }
                }
                ?>
                <br style="clear:both" />
            </div>
        </div>

        <div id="date_management">
            <h2 class="trigger-h2">Période autorisée des inscriptions :</h2>
            <p><small>Les renseignements saisis s'appliqueront à toutes les sorties liées à cette destination.</small></p>
            <div class="trigger-me">
                <div style="width:50%; float:left">
                    <label for="">Ouverture des inscriptions</label><br>
                    <input type="text" name="inscription_ouverture"  id="inscription_ouverture" class="type2 dtpick" value="<?php echo inputVal('inscription_ouverture', ''); ?>" placeholder="aaaa-mm-dd hh:ii:ss">
                </div>
                <div style="width:50%; float:left">
                    <label for="">Fin des inscriptions</label><br>
                    <input type="text" name="inscription_fin" id="inscription_fin" class="type2 dtpick" value="<?php echo inputVal('inscription_fin', ''); ?>" placeholder="aaaa-mm-dd hh:ii:ss">
                </div>
                <br class="clear">
            </div>
        </div>


        <div id="geoloc">
            <h2 class="trigger-h2">Localisation :</h2>
            <div class="trigger-me">

                <?php if ($id_lieu) { ?>
                    <input type="hidden" name="id_lieu" value="<?php echo $id_lieu; ?>">

                    <div class="display_lieu" data-id_lieu="<?php echo $id_lieu; ?>" style="position:relative;">
                        <?php echo display_edit_lieu_link($id_lieu, inputVal('ancien_lieu|nom', '')); ?>
                        <b><?php echo inputVal('ancien_lieu|nom', ''); ?></b><br>
                        <div class="map" data-lat="" data-lng=""></div>
                        <?php if ($destination['ancine_lieu']['ign']) { ?>
                        <div class="ign"><?php echo display_frame_geoportail(inputVal('ancien_lieu|ign', ''), 620, 350); ?></div>
                        <?php } ?>
                        <div class="description"><?php echo inputVal('ancien_lieu|description', ''); ?></div>
                    </div>
                    <a href="" id="modify_lieu" class="add">Changer de lieu</a>
                    <div id="new_lieu"></div>

                <?php } else { ?>

                    <?php echo $new_lieu; ?>

                <?php } ?>
            </div>
        </div>

        <div id="geoloc">
            <h2 class="trigger-h2">Aperçu topographique du secteur :</h2>
            <div class="trigger-me">
                <label for="ign_dest">Extrait IGN : <small>Insérez le code de partage fourni par <a href="https://www.geoportail.gouv.fr/" target="_blank">GeoPortail</a>.</small></label>
                <textarea name="ign" id="ign_dest" style="width:95%;height:80px;" class="type2"><?php if (inputVal('ign', '')) {
                    echo display_frame_geoportail(inputVal('ign', ''), 425, 350);
                } ?></textarea>
                <?php if ('' != inputVal('ign', '')) {
                    echo display_frame_geoportail(inputVal('ign', ''), 620, 350);
                } ?>
            </div>
        </div>

        <h2 class="trigger-h2">Informations complémentaires :</h2>
        <div class="trigger-me">
            <?php require __DIR__.'/../../includes/help/tinymce.php'; ?>
            <textarea name="description" style="width:99%"><?php echo stripslashes($_POST['description']); ?></textarea>
        </div>

        <div id="transport">
            <h2 class="trigger-h2">Transport :</h2>
            <div class="trigger-me">
                <label for="cout_transport">Tarif :</label><br>
                <input type="text"  class="type2" name="cout_transport" value="<?php echo inputVal('cout_transport', ''); ?>">&nbsp;&euro;<br>
                <br>
                <h3>Gestion des bus et des places</h3>
                <br>
                <?php if (null !== $destination['bus'] && count($destination['bus']) > 0) { ?>
                    <?php $b = 1; foreach ($destination['bus'] as $bus) { ?>
                        <div class="bus check-nice" id="bus-<?php echo $bus['id']; ?>">
                            <a href="includer.php?p=includes/dest/bus.php&amp;id_bus=<?php echo $bus['id']; ?>" class="edit fancyframe" style="float:right;" title="Modifier : <?php echo $bus['intitule']; ?>"></a>
                            <div class="presentation">
                                <b><?php echo $bus['intitule']; ?></b> : <?php echo $bus['places_max']; ?> places
                            </div>
                            <div class="parcours">
                                <?php if ($bus['ramassage']) { ?>
                                <p>Points de ramassage :</p>
                                <ul>
                                <?php foreach ($bus['ramassage'] as $point) { ?>
                                    <li><?php echo $point['nom']; ?></b>, à <?php echo display_time($point['date']); ?></li>
                                <?php } ?>
                                </ul>
                                <?php } else { ?>
                                    <b class="text-alert">Attention : </b>aucun point de ramassage défini pour ce bus
                                <?php } ?>
                            </div>
                            <label>
                                <input type="checkbox" name="bus_delete[]" value="<?php echo $bus['id']; ?>"> Supprimer <?php echo $bus['intitule']; ?>
                            </label>
                        </div>
                    <?php } ?>
                <?php } ?>

                <?php if ($id_dest_to_update) { ?>
                    <a href="#" id="add_bus" class="add">Ajouter un bus</a>
                <?php } else { ?>
                    <span class="add"><b>Ajouter un bus</b> : Merci d'enregistrer la destination pour gérer les bus</span>
                <?php } ?>
                <div id="new_bus"></div>
            </div>
        </div>
        <br>
        <br />
        <div style="text-align:center">
            <a class="biglink" href="javascript:void(0)" title="Enregistrer" onclick="$(this).parents('form').submit()">
                <span class="bleucaf">&gt;</span>
                <?php if ($id_dest_to_update) { ?>
                    ENREGISTRER LA DESTINATION
                <?php } else { ?>
                    ENREGISTRER, PUIS GERER LES BUS
                <?php } ?>
            </a>
        </div>

    </form>

        <br /><br />


</div>

<script src="https://unpkg.com/leaflet@1.4.0/dist/leaflet.js"
        integrity="sha512-QVftwZFqvtRNi0ZyCtsznlKSWOStnDORoefr1enyq5mVL4tmKB3S/EnC3rRJcxCPavG10IcrVGSmPh6Qw5lwrg=="
        crossorigin=""></script>
<script type="text/javascript" src="/js/osm-organiser.js"></script>

<?php if ($id_lieu) { ?>
    <script>
        var new_lieu = '<?php echo $new_lieu; ?>';
        $('#modify_lieu').click(function(e){
            e.preventDefault();
            $('#new_lieu').html('<a href="" id="cancel_lieu" class="cancel">Annuler le changement de lieu</a><br>'+new_lieu);
            $('#modify_lieu').hide();
            $('#cancel_lieu').on('click', function(e){
                e.preventDefault();
                $('#new_lieu').html('');
                map=false;
                $('#modify_lieu').show();
                return false;
            });
            return false;
        });
    </script>

<?php } ?>

<script type="text/javascript">

    var nbus = <?php echo (null !== $destination['bus'] && count($destination['bus']) > 0) ? count($destination['bus']) + 1 : 1; ?>;
    $('#add_bus').click(function(e){
        e.preventDefault();
        var new_bus = '<?php echo $new_bus; ?>';
        $('#new_bus').html($('#new_bus').html()+new_bus);
        nbus++;
        $('.cancel_bus').on('click', function(e){
            e.preventDefault();
            $(this).parent('.bus').remove();
            return false;
        });
    });

    $('#add_bus_lieu').on('click', function(e){
        var new_bus_lieu = '<?php echo $new_bus_lieu; ?>';
    });
</script>

<script type="text/javascript">


    $(document).ready(function(){
        // datepicker
        var dayNamesMin = ["Di", "Lu", "Ma", "Me", "Je", "Ve", "Sa" ];
        var    monthNames = ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Spetembre", "Octobre", "Novembre", "Décembre" ];

        function setMaxVal(elt) {
            var maxValue = elt.datetimepicker('getDate');
            $("#date_fin").datepicker( "option", "minDate", maxValue );
            $("#inscription_ouverture").datepicker( "option", "maxDate", maxValue ).datetimepicker( "option", "maxDateTime", maxValue );
            $("#inscription_fin").datepicker( "option", "maxDate", maxValue ).datetimepicker( "option", "maxDateTime", maxValue );
            return false;
        }
        function setMinVal(elt) {
            var minValue = elt.datetimepicker('getDate');
            $("#inscription_fin").datepicker( "option", "minDate", minValue ).datetimepicker( "option", "minDateTime", minValue );
            return false;
        }

        $('#date').datetimepicker({
            dateFormat: 'yy-mm-dd',
            timeFormat: 'HH:mm:ss',
            firstDay: 1,
            closeText:'Ok',
            timeText:'',
            hourText:'Heures',
            minuteText:'Minutes',
            secondText:'Secondes',
            stepMinute:'5',
            dayNamesMin: dayNamesMin,
            monthNames: monthNames,
            onSelect: function (selectedDateTime){
                setMaxVal($(this));
            }
        });

        $('#date_fin').datepicker({
            dateFormat: 'yy-mm-dd',
            firstDay: 1,
            closeText:'Ok',
            dayNamesMin: dayNamesMin,
            monthNames: monthNames
        });

        $('#inscription_ouverture').datetimepicker({
            dateFormat: 'yy-mm-dd',
            timeFormat: 'HH:mm:ss',
            firstDay: 1,
            closeText:'Ok',
            timeText:'',
            hourText:'Heures',
            minuteText:'Minutes',
            secondText:'Secondes',
            stepMinute:'5',
            dayNamesMin: dayNamesMin,
            monthNames: monthNames,
            onSelect: function (selectedDateTime){
                setMinVal($(this));
            }
        });

        $('#inscription_fin').datetimepicker({
            dateFormat: 'yy-mm-dd',
            timeFormat: 'HH:mm:ss',
            firstDay: 1,
            closeText:'Ok',
            timeText:'',
            hourText:'Heures',
            minuteText:'Minutes',
            secondText:'Secondes',
            stepMinute:'5',
            dayNamesMin: dayNamesMin,
            monthNames: monthNames
        });

        setMaxVal( $('#date') );
        setMinVal( $('#inscription_ouverture') );




    });

</script>

<!-- tinyMCE -->
<script language="javascript" type="text/javascript" src="/tools/tinymce/tiny_mce.js"></script>
<script language="javascript" type="text/javascript" src="/js/jquery.webkitresize.min.js"></script><!-- debug handles -->
<script language="javascript" type="text/javascript">
    tinyMCE.init({
        // debug handles
        init_instance_callback: function () { $(".mceIframeContainer iframe").webkitimageresize().webkittableresize().webkittdresize(); },

        height : 500,
        theme : "advanced",
        mode : "exact",
        language : "fr",
        elements : "description",
        entity_encoding : "raw",
        plugins : "safari,spellchecker,style,layer,table,save,advhr,advimage,advlink,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,pagebreak",
        remove_linebreaks : false,
        file_browser_callback : 'userfilebrowser',

        // forecolor,backcolor,|,
        theme_advanced_buttons1 : "newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,|,removeformat,cleanup",
        theme_advanced_buttons2 : "undo,redo,|,cut,copy,paste,pastetext,|,bullist,numlist,|,link,unlink,image,|,charmap,sub,sup",
        theme_advanced_buttons3 : "tablecontrols,|,hr,visualaid,|,fullscreen",

        theme_advanced_toolbar_location : "top",
        theme_advanced_toolbar_align : "left",
        theme_advanced_statusbar_location : "bottom",
        theme_advanced_resizing : true,

        document_base_url : '<?php echo LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL); ?>',

        content_css : "<?php echo LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL); ?>css/base.css,<?php echo LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL); ?>css/style1.css,<?php echo $p_racine; ?>fonts/stylesheet.css",
        body_id : "bodytinymce_user",
        body_class : "description_evt",
        theme_advanced_styles : "Entete Article=ArticleEntete;Titre de menu=menutitle;Bleu clair du CAF=bleucaf;Image flottante gauche=imgFloatLeft;Image flottante droite=imgFloatRight;Lien fancybox=fancybox;Mini=mini;Bloc alerte=erreur;Bloc info=info",

        relative_urls : true,
        convert_urls : false,
        remove_script_host : false,
        theme_advanced_blockformats : "p,h3,h4,h5,ul,li",

        theme_advanced_resize_horizontal : false,
        theme_advanced_resizing : true,
        apply_source_formatting : true,
        spellchecker_languages : "+English=en,Danish=da,Dutch=nl,Finnish=fi,French=fr,German=de,Italian=it,Polish=pl,Portuguese=pt,Spanish=es,Swedish=sv"

        // onchange_callback : "onchange"
    });
    function userfilebrowser(field_name, url, type, win) {
        // alert("Field_Name: " + field_name + "nURL: " + url + "nType: " + type + "nWin: " + win); // debug/testing
        tinyMCE.activeEditor.windowManager.open({
            file : 'includes/user-file-browser.php?type='+type,
            title : 'Mini-File Browser',
            width : 800,  // Your dimensions may differ - toy around with them!
            height : 500,
            resizable : "yes",
            inline : "yes",  // This parameter only has an effect if you use the inlinepopups plugin!
            close_previous : "no"
        }, {
            window : win,
            input : field_name
        });

        return false;
    }
</script>
<!-- /tinyMCE -->
