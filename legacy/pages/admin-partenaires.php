<?php

if (!admin()) {
    echo 'Votre session administrateur a expiré';
} else {
    ?>
	<h1>Gestion du slider partenaires de la page d'accueil&nbsp;&nbsp;<a href="/includer.php?p=pages/partenaire-modifier.php&amp;part_id=-1" class="fancyframe" title="ajouter un nouveau partenaire"><img src="/img/base/add.png" /></a></h1>
	<p>
		<img src="/img/base/magnifier.png" style="vertical-align:middle" />
		Le champ "<i>Search</i>" en haut à droite du tableau vous permet de rechercher n'importe quelle valeur instantanément.<br />
		<img src="/img/base/database_go.png" style="vertical-align:middle" />
		Les boutons de droite vous permettent d'exporter le tableau courant, le plus utile étant l'exportation en .csv.<br />
		<img src="/img/base/info.png" style="vertical-align:middle" />
		Vous pouvez trier les résultats selon différents critères en même temps, en pressant la touche <i>Maj / Shift</i> en cliquant sur les titres des colonnes.<br />
	</p>
	<p>
	<strong>Voir les partenaires :</strong>
		<a href="admin-partenaires.html?show=all" <?php if ('all' == $show) {
        echo 'style="background:#d3d6ff"';
    } ?> class="boutonFancy">&nbsp;tous&nbsp;</a>
		<a href="admin-partenaires.html?show=enabled" <?php if ('enabled' == $show) {
        echo 'style="background:#d3d6ff"';
    } ?> class="boutonFancy">&nbsp;activé&nbsp;</a>&nbsp;
		<a href="admin-partenaires.html?show=disabled" <?php if ('disabled' == $show) {
        echo 'style="background:#d3d6ff"';
    } ?> class="boutonFancy">&nbsp;désactivé&nbsp;</a>&nbsp;
		<a href="admin-partenaires.html?show=private" <?php if ('private' == $show) {
        echo 'style="background:#d3d6ff"';
    } ?> class="boutonFancy">&nbsp;privé&nbsp;</a>&nbsp;
		<a href="admin-partenaires.html?show=public" <?php if ('public' == $show) {
        echo 'style="background:#d3d6ff"';
    } ?> class="boutonFancy">&nbsp;public&nbsp;</a>&nbsp;
	</p>

	<!-- AFFICHAGE DU TABLEAU -->
	<br />
	<br />
	<link rel="stylesheet" href="/tools/datatables/extras/TableTools/media/css/TableTools.css" type="text/css" media="screen" />
	<script type="text/javascript" src="/tools/datatables/extras/TableTools/media/js/TableTools.min.js"></script>

	<script type="text/javascript">
	$(document).ready(function() {
		$('#pagesLibres').dataTable( {
			"iDisplayLength": 100,
//			"aaSorting": [[ 2, "desc" ], [ 4, "asc" ]],
			"sDom": 'T<"clear">lfrtip',
			"oTableTools": {
				"sSwfPath": "/tools/datatables/extras/TableTools/media/swf/copy_csv_xls_pdf.swf",
				"aButtons": [
					"copy",
					"csv",
					"xls",
					{
						"sExtends": "pdf",
						"sPdfOrientation": "landscape"
						// "sPdfMessage": "Your custom message would go here."
					},
					"print"
				]
			}
		} );
		$('span.br').html('<br />');
	});
	</script>


	<br />
	<table id="pagesLibres" class="datatables ">
		<thead>
			<tr>
				<th>Outils</th>
				<th>Nom partenaire</th>
				<th>Description</th>
				<th>Statut</th>
				<th>URL</th>
				<th>Nom image</th>
				<th>Image</th>
				<th>Type</th>
				<th>Nb-cliques</th>
				<th>Ordre affichage</th>
			</tr>
		</thead>
		<tbody>
			<?php

            $total = 0;

    for ($i = 0; $i < count($partenairesTab); ++$i) {
        $elt = $partenairesTab[$i];

        echo '<tr id="tr-'.$elt['part_id'].'" class="'.($elt['part_enable'] ? 'vis-on' : 'vis-off').'">'
                    .'<td style="white-space:nowrap;">';
        // edit
        echo '<a href="/includer.php?p=pages/partenaire-modifier.php&amp;part_id='.(int) ($elt['part_id']).'" class="fancyframe" title="Modifier ce partenaire"><img src="/img/base/application_form_edit.png" alt="MODIFIER" title=""></a> ';
        echo '&nbsp;&nbsp;&nbsp;<a href="/includer.php?p=pages/partenaire-supprimer.php&amp;part_id='.(int) ($elt['part_id']).'" class="fancyframe" title="Supprimer"><img src="/img/base/delete.png" alt="SUPPRIMER" title="SUPPRIMER"  style="margin-bottom:-2px;"></a> ';

        if (1 == $elt['part_enable']) {
            // desactiver
//						echo '<a href="/includer.php?p=pages/partenaire-disable.php&amp;part_id='.intval($elt['part_id']).'" class="fancyframe" title="Désactiver ce partenaire"><img src="/img/base/delete.png" alt="DESACTIVER" title=""></a> ';
        }
        // activer
        //						echo '<a href="/includer.php?p=pages/partenaire-enable.php&amp;part_id='.intval($elt['part_id']).'" class="fancyframe" title="Activer ce partenaire"><img src="/img/base/add.png" alt="ACTIVER" title=""></a> ';

        echo '</td>'
                    .'<td>'.html_utf8($elt['part_name']).'</td>'
                    .'<td>'.html_utf8($elt['part_desc']).'</td>'
                    .'<td>'.(1 == $elt['part_enable'] ? 'ACTIF' : 'INACTIF').'</td>'
                    .'<td><a target="_blank" href="'.html_utf8($elt['part_url']).'">'.html_utf8($elt['part_url']).'</a></td>'
                    .'<td>'.html_utf8($elt['part_desc']).'</td>'
                    .'<td align="center">';
        echo '<a target="_blank" href="/goto/partenaire/'.$elt['part_id'].'/'.formater($elt['part_name'], 3).'.html">';
        if (file_exists('./ftp/partenaires/'.$elt['part_image'])) {
            echo '<img src="/ftp/partenaires/'.$elt['part_image'].'" style="max-width:150px;max-height:60px">';
        } else {
            echo '<img src="/img/base/cross.png" width="25" height="25" alt="non trouvée" />';
        }
        echo '</a></td>'
                    .'<td>'.(1 == $elt['part_type'] ? 'PRIVÉ' : 'PUBLIC').'</td>'
                    .'<td>'.html_utf8($elt['part_click']).'</td>'
                    .'<td>'.html_utf8($elt['part_order']).'</td>'
                .'</tr>';
    } ?>
		</tbody>
	</table>


	<br style="clear:both" />
	<br style="clear:both" />
	<?php
}
?>