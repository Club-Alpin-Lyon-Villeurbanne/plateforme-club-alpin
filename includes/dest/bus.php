<?php

    $bus = get_bus($_GET['id_bus']);
    $new_lieu = display_new_lieu();
    $lieux_ramasse_connus = select_lieux_ramasse_connus();
    $ids_lieux_ramasse_destination = select_lieux_ramasse_connus($bus['destination']['id'], false, $bus['id']);
    $ramasse_in_use = array();

?>

<form action="<?php echo $versCettePage; ?>" method="post">

    <input type="hidden" name="operation" value="bus_update"/>
    <input type="hidden" name="id" value="<?php echo $bus['id']; ?>"/>
    <input type="hidden" name="id_destination" value="<?php echo $bus['id_destination']; ?>"/>

    <h1><span class="bleucaf"><?php echo $bus['intitule']; ?></span> <small>pour</small> <span class="bleucaf"><?php echo $bus['destination']['nom']; ?></span> <small>, <?php echo display_dateTime($bus['destination']['date']); ?></small></h1>

    <?php if($_POST['operation'] && sizeof($errTab)) { ?>
        <div class="erreur">Erreur : <ul><li><?php echo implode('</li><li>', $errTab); ?></li></ul></div><br>
    <?php } elseif($_POST['operation']) { ?>
        <p class="info">
            <img src="img/base/tick.png" alt="" title="" /> Mise à jour effectuée.</p><br>
    <?php } ?>

    <div class="half lft">
        <label for="intitule">Intitulé :</label><br>
        <input type="text" name="intitule" value="<?php echo $bus['intitule']; ?>" class="type2 full"/>
    </div>
    <div class="half lft">
        <label for="places_max">Nombre de places :</label><br>
        <input type="text" name="places_max" value="<?php echo $bus['places_max']; ?>" class="type2"/>
    </div>

    <br class="clear">

    <br>
    <h2>Lieux de ramassage</h2>
    <?php if ($bus['ramassage'] && count($bus['ramassage'])) { ?>
        <?php foreach ($bus['ramassage'] as $point) { ?>
        <?php $ramasse_in_use[] = $point['id_lieu']; ?>
        <div class="point_ramasse check-nice">
            <div data-id_lieu="<?php echo $point['id_lieu'];?>" style="position:relative;">
                <?php echo display_edit_lieu_link($point['id_lieu'], $point['nom']); ?>
                <b><?php echo htmlspecialchars($point['nom']);?></b>, à <?php echo display_time($point['date']); ?><br>
                <div class="mapmarker" data-lat="<?php echo $point['lat'];?>" data-lng="<?php echo $point['lng'];?>"></div>
                <div class="ign"><?php echo display_frame_geoportail($point['ign'], 620, 350); ?></div>
                <div class="description"><?php echo $point['description'];?></div>
                <label>
                    <input type="checkbox" name="lieu_ramasse_delete[]" value="<?php echo $point['bdl_id']; ?>"> Supprimer ce lieu de ramassage
                </label><br class="clear">
            </div>
        </div>
        <?php } ?>
    <?php } ?>
    <a href="" id="modify_lieu" class="add">Ajouter un lieu de ramassage</a>
    <div id="new_lieu"></div>

    <br>
    <br />
    <div style="text-align:center">
        <a class="biglink" href="javascript:void(0)" title="Enregistrer" onclick="$(this).parents('form').submit()">
            <span class="bleucaf">&gt;</span>
                ENREGISTRER
        </a>
    </div>

</form>



<link rel="stylesheet" href="tools/jquery-ui-1.11.2/jquery-ui.css" type="text/css"  media="screen" />
<script type="text/javascript" charset="utf-8" src="tools/jquery-ui-1.11.2/jquery-ui.min.js"></script>
<script type="text/javascript" charset="utf-8" src="js/jquery-ui-timepicker-addon.js"></script>
<script src="https://unpkg.com/leaflet@1.4.0/dist/leaflet.js"
                            integrity="sha512-QVftwZFqvtRNi0ZyCtsznlKSWOStnDORoefr1enyq5mVL4tmKB3S/EnC3rRJcxCPavG10IcrVGSmPh6Qw5lwrg=="
                            crossorigin=""></script>
                    <script type="text/javascript" src="js/osm-organiser.js"></script>

<script>

</script>
<script type="text/javascript">




        function buildDay() {

            var dayNamesMin = ["Di", "Lu", "Ma", "Me", "Je", "Ve", "Sa" ];
            var monthNames = ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Spetembre", "Octobre", "Novembre", "Décembre" ];


            $('#bus_dest_lieu_date').datetimepicker({
                dateFormat: 'yy-mm-dd',
                timeFormat: 'HH:mm:ss',
                closeText:'Ok',
                timeText:'',
                hourText:'Heures',
                minuteText:'Minutes',
                secondText:'Secondes',
                stepMinute:'5',
                dayNamesMin: dayNamesMin,
                monthNames: monthNames
            });

            setDay();
        }

        function setDay() {
            // datepicker
            
            <?php

                $dest_date = explode(' ',$bus['destination']['date']);
                $date = explode('-', $dest_date[0]);
                $hour = explode(':', $dest_date[1]);
            ?>
            
            var lockDate = new Date(<?php echo $date[0]; ?>, <?php echo $date[1]-1; ?>, <?php echo $date[2]; ?>, <?php echo $hour[0]; ?>, <?php echo $hour[1]; ?>);
            var maxLockDate = new Date(<?php echo $date[0]; ?>, <?php echo $date[1]-1; ?>, <?php echo $date[2]; ?>, 23, 59);
            $("#bus_dest_lieu_date").datepicker( "option", "minDate", lockDate ).datetimepicker( "option", "minDateTime", lockDate );
            $("#bus_dest_lieu_date").datepicker( "option", "maxDate", maxLockDate ).datetimepicker( "option", "maxDateTime", maxLockDate );
            return false;
        }

        var new_lieu = '<?php echo $new_lieu; ?>';
        var new_lieu_dest = '<p><a href="" id="cancel_lieu" class="cancel">Annuler</a></p><br><h3>Nouveau lieu de ramassage</h3>'+
            '<div>'+
            '<input type="hidden" name="bus_dest_lieu[id_bus]" value="<?php echo $bus['id']; ?>"/>'+
            '<input type="hidden" name="bus_dest_lieu[id_destination]" value="<?php echo $bus['id_destination']; ?>"/>'+
            '<input type="hidden" name="bus_dest_lieu[type_lieu]" value="ramasse"/><br>'+
            '<label for="bus_dest_lieu_date">Horaire de ramassage :</label><br>'+
            '<input type="text" name="bus_dest_lieu[date]" class="type2" id="bus_dest_lieu_date" value="<?php echo inputVal('bus_dest_lieu|date'); ?>">'+
            '</div><br>';


        var old_lieux_ramasse = <?php if ($lieux_ramasse_connus) { ?>'<select name="use_existant" class="type2" style="width:95%;">'
            +'<option value="">- Utiliser un lieu existant <?php if ($ids_lieux_ramasse_destination) { ?>( * indique un lieu déjà utilisé par un autre bus de cette destination )<?php } ?></option>'
            +'<?php foreach ($lieux_ramasse_connus as $lramasse) { if (!in_array($lramasse['id'], $ramasse_in_use)) {
            ?><option value="<?php echo $lramasse['id']; ?>"><?php
            if ($ids_lieux_ramasse_destination && in_array($lramasse['id'], $ids_lieux_ramasse_destination)) { echo ' <b>[*] </b>'; } ?><?php echo html_utf8($lramasse['nom']); ?></option><?php }} ?>'
            +'</select><br><br>OU<br><br>';
        <?php } else { ?> ''<?php } ?>;

        $('#modify_lieu').click(function(e){
            e.preventDefault();
            $('#new_lieu').html(new_lieu_dest+old_lieux_ramasse+new_lieu);
            buildDay();

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
