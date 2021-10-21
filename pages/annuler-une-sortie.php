<!-- MAIN -->
<div id="main" role="main" class="bigoo" style="">

	<!-- partie gauche -->
	<div id="left1">
		<div class="main-type">

			<?php
            if (user()) {
                if ($evt) {
                    $joins = $evt['joins'];
                }
                if ($destination) {
                    $joins = $destination['joins'];
                }

                if ('destination' == $p2) {
                    // destination non trouvée, pas de message d'erreur, équivalent à un 404
                    if (!$destination && !$errPage) {
                        echo '<br /><br /><br /><p class="erreur">Hmmm... C\'est ennuyeux : nous n\'arrivons pas à trouver la destination correspondant à cette URL.</p>';
                    }
                    // destination non trouvée, avec message d'erreur, tentative d'accès mesquine ou sortié dévalidée
                    if (!$destination && $errPage) {
                        echo '<div class="erreur">'.$errPage.'</div>';
                    }
                    // destination trouvée, pas d'erreur, affichage normal :
                    if ($destination && !$errPage) {
                        ?>
                        <h1>Annuler une destination</h1>
                        <?php
                        inclure($p1, 'vide');
                        inclure($p1.'-'.$p2, 'vide');
                        if ('dest_cancel' == $_POST['operation'] && count($errTab)) {
                            echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div><br /><br />';
                            echo '<a href="'.$p_racine.'destination/'.$destination['code'].'-'.$destination['id'].'.html">Retourner vers la fiche de destination</a>';
                        } else {
                            if ('1' != $destination['annule']) {
                                ?>

                                <form action="<?php echo $versCettePage; ?>" method="post" class="loading">
                                    <input type="hidden" name="operation" value="dest_cancel" />

                                    <?php
                                    // TABLEAU
                                    if ('dest_cancel' == $_POST['operation'] && count($errTab)) {
                                        echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
                                    }
                                if ('dest_cancel' == $_POST['operation'] && !count($errTab)) {
                                    echo '<p class="info">Cette destination a été annulée.</p>';
                                } ?>
                                    <br />

                                    <?php

                                    // si la sortie est publiée, on annonce que des e-mails vont être envoyés
                                    if (1 == $destination['publie']) {
                                        ?>
                                        <textarea class="type2" style="width:610px" name="msg" placeholder="ex : Sorties annulées pour cause de météo défavorable."><?php echo inputVal('msg', ''); ?></textarea>

                                        <a href="javascript:void(0)" title="Enregistrer" class="nice2 red" onclick="$(this).parents('form').submit()">
                                            Annuler définitivement la destination ci-dessous et avertir <?php echo count($joins); ?> participant(s) inscrit(s).
                                        </a>
                                    <?php
                                    }
                                // sinon le message n'est pas necessaire
                                else {
                                    ?>
                                        <p class="mini">La destinatin n'est pas publiée : aucun message ne sera envoyé</p>

                                        <a href="javascript:void(0)" title="Enregistrer" class="nice2 red" onclick="$(this).parents('form').submit()">
                                            Annuler définitivement la destination ci-dessous
                                        </a>
                                    <?php
                                } ?>
                                </form>
                            <?php
                            }
                        } ?>
                    <?php
                    }
                } else {
                    // sortie non trouvée, pas de message d'erreur, équivalent à un 404
                    if (!$evt && !$errPage) {
                        echo '<br /><br /><br /><p class="erreur">Hmmm... C\'est ennuyeux : nous n\'arrivons pas à trouver la sortie correspondant à cette URL.</p>';
                    }
                    // sortie non trouvée, avec message d'erreur, tentative d'accès mesquine ou sortié dévalidée
                    if (!$evt && $errPage) {
                        echo '<div class="erreur">'.$errPage.'</div>';
                    }

                    // sortie trouvée, pas d'erreur, affichage normal :
                    if ($evt && !$errPage) {
                        ?>
                        <h1>Annuler une sortie</h1>

                        <?php
                        inclure($p1, 'vide');
                        if ('evt_cancel' == $_POST['operation'] && count($errTab)) {
                            echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div><br /><br />';
                            echo '<a href="'.$p_racine.'sortie/'.$evt['code_evt'].'-'.$evt['id_evt'].'.html">Retourner vers la fiche de sortie</a>';
                        } else {
                            if ('1' != $evt['cancelled_evt']) {
                                ?>

                                <form action="<?php echo $versCettePage; ?>" method="post" class="loading">
                                    <input type="hidden" name="operation" value="evt_cancel" />

                                    <?php
                                    // TABLEAU
                                    if ('evt_cancel' == $_POST['operation'] && count($errTab)) {
                                        echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
                                    }
                                if ('evt_cancel' == $_POST['operation'] && !count($errTab)) {
                                    echo '<p class="info">Cette sortie a été annulée.</p>';
                                } ?>
                                    <br />

                                    <?php

                                    if ($evt['cycle_master_evt'] > 0) {
                                        echo "<b>Cette sortie est la première d'un cycle de plusieurs sorties. <b>Son annulation entraînera l'annulation de toutes les sorties du cycle.</b></b><br /><br />";
                                    }

                                // si la sortie est publiée, on annonce que des e-mails vont être envoyés
                                if (1 == $evt['status_evt']) {
                                    ?>
                                        <textarea class="type2" style="width:610px" name="msg" placeholder="ex : Sortie annulée pour cause de météo défavorable."><?php echo inputVal('msg', ''); ?></textarea>
                                        <?php
                                            if (false && $evt['cycle_master_evt']) {
                                                echo '<input type="checkbox" name="del_cycle_master_evt" value="1" checked /> <b>SORTIE DE DEBUT DE CYCLE</b>, annuler toutes les sorties du cycle';
                                            } ?>


                                        <a href="javascript:void(0)" title="Enregistrer" class="nice2 red" onclick="$(this).parents('form').submit()">
                                            Annuler définitivement la sortie ci-dessous et avertir <?php echo count($joins); ?> participant(s) inscrit(s).
                                        </a>
                                        <?php
                                }
                                // sinon le message n'est pas necessaire
                                else {
                                    ?>
                                        <p class="mini">La sortie n'est pas publiée : aucun message ne sera envoyé</p>
                                        <?php
                                            if (false && $evt['cycle_master_evt']) {
                                                echo '<input type="checkbox" name="del_cycle_master_evt" value="1" checked /> <b>SORTIE DE DEBUT DE CYCLE</b>, annuler toutes les sorties du cycle';
                                            } ?>

                                        <a href="javascript:void(0)" title="Enregistrer" class="nice2 red" onclick="$(this).parents('form').submit()">
                                            Annuler définitivement la sortie ci-dessous
                                        </a>
                                        <?php
                                } ?>
                                </form>
                                <?php
                            } ?>
                            <br />
                            <br />
            <?php
                        }
                    }
                } ?>



                <br />
                <hr />
                <h2 style="text-align:center; background:white; padding:10px">INSCRITS :</h2>
                <?php
                // RESUME DE LA SORTIE
                echo '<table class="big-lines-table" style="width:570px; margin-left:20px;">';

                // echo '<pre>';print_r($joins); echo '</pre>';

                // inscrits en ligne via formulaire
                foreach ($joins as $tmpUser) {
                    echo '<tr>
                                        <td>
                                            '.userlink($tmpUser['id_user'], $tmpUser['nickname_user'])
                        .(allowed('user_read_private', $evt['code_commission']) ? '<p class="mini">'.strtoupper(html_utf8($tmpUser['lastname_user'])).' '.html_utf8($tmpUser['firstname_user']).'</p>' : '')
                        .'</td>'
                        .'<td class="small">'.(allowed('user_read_private', $evt['code_commission']) ? $tmpUser['tel_user'] : '').'</td>'
                        .($tmpUser['nomade_user'] ?
                            '<td class="small" colspan="3">
                                                <p class="alerte">
                                                    Attention ! Cet adhérent &laquo;nomade&raquo; ne recevra pas de message d\'annulation ! Vous devez
                                                    le prévenir vous-même si la sortie n\'a pas lieu.
                                                </p>
                                            </td>'
                            :
                            '<td class="small">'.(allowed('user_read_private', $evt['code_commission']) ? $tmpUser['tel2_user'] : '').'</td>
                                            <td class="small">'.(allowed('user_read_private', $evt['code_commission']) ? '<a href="mailto:'.$tmpUser['email_user'].'">'.$tmpUser['email_user'].'</a>' : '').'</td>
                                            <td class="small">'.$tmpUser['role_evt_join'].'</td>
                                            '
                        )
                        .'</tr>';
                }
                echo '</table>'; ?>


                <br />
                <hr />
                <h2 style="text-align:center; background:white; padding:10px">APERÇU :</h2>
                <?php
                // RESUME DE LA SORTIE
                if ($evt) {
                    include INCLUDES.'evt-resume.php';
                }
                if ($destination) {
                    include INCLUDES.'dest'.DS.'display.php';
                } ?>

            <?php
            }
            ?>
			<br style="clear:both" />
		</div>
	</div>

	<!-- partie droite -->
	<?php
    include INCLUDES.'right-type-agenda.php';
    ?>


	<br style="clear:both" />
</div>