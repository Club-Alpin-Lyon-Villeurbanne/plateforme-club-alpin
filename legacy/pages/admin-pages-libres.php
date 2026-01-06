<?php

use App\Helper\HtmlHelper;
use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

if (!isGranted(SecurityConstants::ROLE_CONTENT_MANAGER)) {
    echo 'Vous n\'êtes pas autorisé à accéder à cette page. Pour toute question, rapprochez-vous du service informatique de votre club.';
} else {
    $tab = [];
    $req = 'SELECT * FROM `caf_page` WHERE `pagelibre_page` =1 ORDER BY `ordre_page` DESC LIMIT 1000';
    $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($row = $result->fetch_assoc()) {
        // récupération de la date de dernière modification de l'élément contenu si existant
        $id_page = $row['id_page'];
        $lang_content_html = 'fr';
        $req = "SELECT `date_content_html`
            FROM `caf_content_html`
            WHERE `code_content_html` LIKE 'main-pagelibre-$id_page'
            AND `lang_content_html` LIKE '$lang_content_html'
            ORDER BY `date_content_html` ASC
            LIMIT 1";
        $result2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        if (!$result2->num_rows) {
            $row['lastedit_page'] = 0;
        } else {
            while ($row2 = $result2->fetch_array(\MYSQLI_ASSOC)) {
                $row['lastedit_page'] = $row2['date_content_html'];
            }
        }

        $tab[] = $row;
    } ?>

    <h1>Gestion des pages &laquo;libres&raquo; du site</h2>
    <p>
        <img src="/img/base/info.png" style="vertical-align:middle" />
        Ajoutez, modifiez, masquez ou supprimez les pages libres du site.
        Une page masquée apparaît "rayée" dans le tableau ci-dessous.
    </p>

    <br />
    <a href="/includer.php?admin=true&p=pages/admin-pages-libres-add.php" title="" class="fancyframe boutonFancy"><img src="/img/base/page_white_add.png" alt="" title="" /> Nouvelle page</a>


    <!-- AFFICHAGE DU TABLEAU -->
    <br />
    <br />
    <link rel="stylesheet" href="/tools/datatables/extras/TableTools/media/css/TableTools.css" type="text/css" media="screen" />
    <script type="text/javascript" src="/tools/datatables/extras/TableTools/media/js/TableTools.min.js"></script>

    <script type="text/javascript">
    $(document).ready(function() {
        $('#pagesLibres').dataTable( {
            "iDisplayLength": 50,
            "aaSorting": [[ 7, "desc" ]],
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
                <th>ID</th>
                <!--<th>Vis</th>-->
                <th>Code</th>
                <th>Titre par défaut</th>
                <th>URL</th>
                <th>Description par défaut</th>
                <th>Priorité SEO</th>
                <th>Création</th>
                <th>Dernière modification</th> <!-- info tirée de l'élément html contenu -->
            </tr>
        </thead>
        <tbody>
            <?php
            $total = 0;

    for ($i = 0; $i < count($tab); ++$i) {
        $elt = $tab[$i];

        echo '<tr id="tr-' . $elt['id_page'] . '" class="' . ($elt['vis_page'] ? 'vis-on' : 'vis-off') . '">'
                    . '<td style="width:120px">'
                        . '<a class="delete" href="javascript:void(0)" rel="' . (int) $elt['id_page'] . '|' . HtmlHelper::escape($elt['code_page']) . '" title="Supprimer définitivement cette page"><img src="/img/base/delete.png" alt="DEL" title="Supprimer" /></a> &nbsp;'
                        . '<a href="' . LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'pages/' . rawurlencode($elt['code_page']) . '.html" title="Modifier cette page"><img src="/img/base/page_edit.png" alt="EDIT" title="Modifier cette page" /></a> &nbsp;'
                        . '<a class="fancyframe" href="/includer.php?admin=true&p=pages/admin-pages-libres-edit.php&amp;id_page=' . (int) $elt['id_page'] . '" title="Modifier les METAS"><img src="/img/base/application_form_edit.png" alt="EDIT METAS" title="Modifier les metas" /></a> &nbsp;'
                        . '<a class="majVis" href="javascript:void(0)" rel="' . (int) $elt['id_page'] . '|' . $elt['lastedit_page'] . '" title="Afficher/masquer cette page aux visiteurs du site"><img src="/img/base/vis-' . ($elt['vis_page'] ? 'on' : 'off') . '.png" alt="VIS" title="Afficher/masquer" /></a> &nbsp;'
                    . '</td>'
                    . '<td>' . (int) $elt['id_page'] . '</td>'
                    // .'<td>'.intval($elt['vis_page']).'</td>'
                    . '<td>' . HtmlHelper::escape($elt['code_page']) . '</td>'
                    . '<td>' . HtmlHelper::escape($elt['default_name_page']) . '</td>'
                    . '<td><a href="/pages/' . rawurlencode($elt['code_page']) . '.html" title="">/pages/' . HtmlHelper::escape($elt['code_page']) . '.html</a></td>'
                    . '<td>' . HtmlHelper::escape(substr($elt['default_name_page'] ?? '', 0, 200)) . '</td>'
                    . '<td>' . ((int) $elt['priority_page'] * 10) . '%</td>'
                    . '<td><span style="display:none">' . $elt['created_page'] . '</span> ' . date('d/m/Y H:i', $elt['created_page']) . '</td>'
                    . '<td><span style="display:none">' . $elt['lastedit_page'] . '</span> ' . (!$elt['lastedit_page'] ? 'Jamais' : date('d/m/Y H:i', $elt['lastedit_page'])) . '</td>'
                . '</tr>' . "\n";
    } ?>
        </tbody>
    </table>

    <!-- éléments à faire apparaitre en lightbox -->

    <div id="noteditedyet" style="display:none">
        <h3>Damned !</h3>
        <p>Vous ne pouvez pas modifier la visibilité de cette page tant que vous n'y avez pas ajouté de contenu.</p>
    </div>

    <div id="confirm-afficher" style="display:none">
        <form method="post" action="admin-pages-libres.html" class="ajaxform">
            <input type="hidden" name="operation" value="majBd" />
            <input type="hidden" name="table" value="page" />
            <input type="hidden" name="champ" value="vis_page" />
            <input type="hidden" name="val" value="1" />
            <input type="hidden" name="id" value="" />

            <h3>Afficher cette page !</h3>
            <p>Voulez-vous vraiment rendre cette page visible aux visiteurs du site ?</p>
            <br />
            <input type="submit" class="nice green" value="Confirmer" />
            <input type="button" class="nice" value="Annuler" onclick="$.fancybox.close()" />
            <br />&nbsp;
        </form>
    </div>

    <div id="confirm-masquer" style="display:none">
        <form method="post" action="admin-pages-libres.html" class="ajaxform">
            <input type="hidden" name="operation" value="majBd" />
            <input type="hidden" name="table" value="page" />
            <input type="hidden" name="champ" value="vis_page" />
            <input type="hidden" name="val" value="0" />
            <input type="hidden" name="id" value="" />

            <h3>Masquer cette page !</h3>
            <p>Voulez-vous vraiment masquer cette page aux visiteurs du site ?</p>
            <br />
            <input type="submit" class="nice orange" value="Confirmer" />
            <input type="button" class="nice" value="Annuler" onclick="$.fancybox.close()" />
            <br />&nbsp;
        </form>
    </div>

    <div id="confirm-delete" style="display:none">
        <form method="post" action="admin-pages-libres.html" class="ajaxform">
            <input type="hidden" name="operation" value="pagelibre_del" />
            <input type="hidden" name="id_page" value="" />

            <h3>Supprimer cette page !</h3>
            <p>
                Voulez-vous vraiment supprimer définitivement cette page et tous ses contenus ? <br />
                Tapez en majuscule les lettres &laquo;<i>SUPPRIMER</i>&raquo; dans la case ci-dessous pour confirmer :
            </p>
            <input type="text" name="confirm" value="" />
            <br />
            <br />
            <input type="submit" class="nice red" value="Supprimer" />
            <input type="button" class="nice" value="Annuler" onclick="$.fancybox.close()" />
            <br />&nbsp;
        </form>
    </div>

    <script type="text/javascript">
    $().ready(function(){

        // OUTIL : MONTRER/AFFIHCER

        // action lors du submit
        $(document).on('submit', '.ajaxform', function(){

            // vars
            var datas='';
            $(this).find('input, select, textarea').each(function(){
                if($(this).val() && $(this).attr('name'))
                    datas += (datas?'&':'')+$(this).attr('name')+'='+$(this).val();
            });

            // call
            $.ajax({
                type: "POST",
                dataType : "json",
                url: "/?ajx=operations",
                data: datas,
                success: function(jsonMsg){
                    if(jsonMsg.success){

                        // différentes actions en fonctiond de l'opération
                        if(jsonMsg.operation=='majBd' && jsonMsg.val=='0'){
                            $('#tr-'+jsonMsg.id).addClass('vis-off').removeClass('vis-on')
                                .find('a.majVis img').attr('src','img/base/vis-off.png');
                            $.fancybox.close();
                        }
                        if(jsonMsg.operation=='majBd' && jsonMsg.val=='1'){
                            $('#tr-'+jsonMsg.id).addClass('vis-on').removeClass('vis-off')
                                .find('a.majVis img').attr('src','img/base/vis-on.png');
                            $.fancybox.close();
                        }
                        if(jsonMsg.operation=='pagelibre_del'){
                            window.location.href='admin-pages-libres.html';
                            window.location.reload();
                        }
                        // fin des actions spéciales
                    }
                    else{
                        $.fancybox('<p class="erreur">Erreur : <br />'+(jsonMsg.error).join(',<br />')+'</p>');
                    }
                }
            });

            return false;
        });

        // action : mise à jour VISIBILITE
        $(document).on('click', 'a.majVis', function(){
            var tab=$(this).attr('rel').split('|');
            var id=parseInt(tab[0]);
            var edit=parseInt(tab[1]);
            var hidden=$(this).parents('tr:first').hasClass('vis-off');
            if(id){
                // mise a jour des champs necessaires aux formulaires
                $('#confirm-afficher input[name=id], #confirm-masquer input[name=id]').val(id);

                if(!edit)	$.fancybox($('#noteditedyet').html());
                else{
                    if(hidden)  $.fancybox($('#confirm-afficher').html());
                    else		$.fancybox($('#confirm-masquer').html());
                }
            }
            return false;
        });

        // action au clic sur le bouton
        $(document).on('click', 'a.delete', function(){
            var tab=$(this).attr('rel').split('|');
            var id=parseInt(tab[0]);
            // var code=parseInt(tab[1]);
            if(id){
                // mise a jour des champs necessaires aux formulaires
                $('#confirm-delete input[name=id_page]').val(id);
                $.fancybox($('#confirm-delete').html());
            }
            return false;
        });

    });
    </script>
    <br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br />
    <?php
}
