<?php

use App\Entity\UserAttr;
use App\Legacy\LegacyContainer;
use App\Repository\CommissionRepository;

if (user()) {
    $em = $this->getRegistry();
    $commissionRepository = new CommissionRepository($em);
    ?>
    <div class="main-type">
        <h1>Mon profil - Gestion de mon compte</h1>

        <?php
        inclure('profil-infos', 'vide'); ?>

        <!-- suppression de l'image (lightbox) -->
        <div id="confirm-delete" style="display:none">
            <form action="<?php echo $versCettePage; ?>#user_update" method="post">
                <input type="hidden" name="operation" value="user_profil_img_delete" />
                Voulez-vous vraiment supprimer cette photo de profil ?<br />
                <br />
                <input type="submit" class="nice red" value="Confirmer">
                <input type="button" class="nice" value="Annuler" onclick="$.fancybox.close()">
            </form>
        </div>
        <hr />

        <!-- Infos : statuts -->
        <?php if (getUser()->hasAttribute()) { ?>
            <h2><span class="bleucaf">&gt;</span> Vos responsabilités :</h2>
            <?php inclure('infos-profil-statuts', 'vide'); ?>
                <br><br>
            <?php
            $clubRoles = [];
            $commissionRoles = [];
            $attributes = getUser()->getAttributes();
            foreach ($attributes as $attr) {
                if (in_array($attr->getCode(), UserAttr::COMMISSION_RELATED, true)) {
                    if (!isset($commissionRoles[$attr->getParams()])) {
                        $commissionRoles[$attr->getParams()] = $attr;
                    }
                } else {
                    if (!isset($clubRoles[$attr->getParams()])) {
                        $clubRoles[$attr->getParams()] = $attr;
                    }
                }
            }
            ?>
            <h3>Responsabilité sur le site :</h3>
            <ul class="nice-list">
                <?php
                if (!empty($clubRoles)) {
                    foreach ($clubRoles as $attr) {
                        echo '<li>' . $attr->getTitle();
                        if (!empty($attr->getDescription())) {
                            echo ' <img src="/img/base/info.png" title="' . $attr->getDescription() . '" />';
                        }
                        echo '&nbsp;<a href="' . LegacyContainer::get('router')->generate('user_right_auto_remove', ['type' => $attr->getUserType()->getCode()]) . '" onclick="return confirm(\'Êtes-vous sûr de vouloir vous retirer cette responsabilité ? Les responsables du site seront prévenus\')">';
                        echo '<img src="/img/base/delete.png" alt="Enlever" title="Me retirer cette responsabilité" /></a>';
                        echo '</li>';
                    }
                } else {
                    echo '<li>N/A</li>';
                }
            ?>
            </ul>
            <br style="clear:both" />

            <h3>Responsabilité dans les commissions :</h3>
            <ul class="nice-list">
                <?php
            if (!empty($commissionRoles)) {
                foreach ($commissionRoles as $attr) {
                    echo '<li>' . $commissionRepository->getCommissionNameByCode($attr->getCommission()) . ' : ' . $attr->getTitle();
                    if (!empty($attr->getDescription())) {
                        echo ' <img src="/img/base/info.png" title="' . $attr->getDescription() . '" />';
                    }
                    echo '&nbsp;<a href="' . LegacyContainer::get('router')->generate('user_right_auto_remove', ['type' => $attr->getUserType()->getCode(), 'commission' => $attr->getCommission()]) . '" onclick="return confirm(\'Êtes-vous sûr de vouloir vous retirer cette responsabilité ? Les responsables de la commission seront prévenus\')">';
                    echo '<img src="/img/base/delete.png" alt="Enlever" title="Me retirer cette responsabilité" /></a>';
                    echo '</li>';
                }
            } else {
                echo '<li>N/A</li>';
            }
            ?>
            </ul>
            <br style="clear:both" />
            <hr />
        <?php } ?>

        <!-- Infos : filiations (enfants) -->
        <?php if (count($tmpUser['enfants'] ?? [])) { ?>
            <h2><span class="bleucaf">&gt;</span> Filiation :</h2>
            <?php inclure('infos-profil-filiation-enfants', 'vide'); ?>
            <ul class="nice-list">
                <?php
            foreach ($tmpUser['enfants'] as $enfant) {
                echo '<li>' . userlink($enfant['id_user'], $enfant['nickname_user'], '', $enfant['firstname_user'], $enfant['lastname_user'], $style = 'full') . '</li>';
            }
            ?>
            </ul>
            <br style="clear:both" />
            <hr />
        <?php } ?>


        <!-- Infos : filiations (parent) -->
        <?php if (!empty($tmpUser['parent']) && count($tmpUser['parent']) > 0) { ?>
            <h2><span class="bleucaf">&gt;</span> Filiation :</h2>
            <?php inclure('infos-profil-filiation-parent', 'vide'); ?>
            <ul class="nice-list">
                <?php
            $parent = $tmpUser['parent'];
            echo '<li>' . userlink($parent['id_user'], $parent['nickname_user'], '', $parent['firstname_user'], $parent['lastname_user'], $style = 'full') . '</li>';
            ?>
            </ul>
            <br style="clear:both" />
            <hr />
        <?php } ?>


        <!-- Données profil -->
        <form id="user_update" class="contenutype2 loading" action="<?php echo $versCettePage; ?>#user_update" method="post" enctype="multipart/form-data">
            <input type="hidden" name="operation" value="user_update" />

            <?php
            // TABLEAU
            if (isset($_POST['operation']) && 'user_update' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
                echo '<div class="erreur">Erreur : <ul><li>' . implode('</li><li>', $errTab) . '</li></ul></div>';
            }
    if (isset($_POST['operation']) && 'user_update' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
        echo '<p class="info">Mise à jour effectuée à ' . date('H:i:s', time()) . '.</p>';
    } ?>
            &nbsp;

            <h2 id="public"><span class="bleucaf">&gt;</span>Infos publiques :</h2>
            <br />
            <div id="edit_profil_image">
                <?php
                $image = '/ftp/user/' . $tmpUser['id_user'] . '/min-profil.jpg';
    // pas d'image
    if (!is_file(__DIR__ . '/../../public' . $image)) {
        $image = '/ftp/user/0/min-profil.jpg';
    }
    // image custom
    else {
        // bouton de suppression
        echo '<span class="delete"><img src="/img/base/delete.png" alt="DELETE" title="Supprimer cette image" /></span>';
    }
    echo '<img class="imgprofil" src="' . $image . '?ac=' . time() . '" alt="Photo de profil" title="Envoyez votre propre photo" />'; ?>
            </div>

            <b>Votre pseudonyme :</b>
            <br />
            <a href="/includer.php?p=includes/fiche-profil.php&id_user=<?php echo getUser()->getId(); ?>" class="fancyframe" title="Aperçu de votre fiche"><?php echo html_utf8($tmpUser['nickname_user']); ?></a><br />

            <br />
            <b>Modifier votre photo :</b> <span class="mini">Format .jpg, 5Mo maximum !</span><br />
            <input type="file" name="photo" />
            <br />
            <br style="clear:both" />

            <hr style="margin: 20px 0" />
            <h2 id="edit-email"><span class="bleucaf">&gt;</span>Modifier mon e-mail</h2>
            <p>
                Laissez ce champ vide si vous ne voulez pas modifier votre adresse e-mail.
            </p>
            <input type="text" name="email_user_mailchange" class="type1" style="width:300px" value="<?php echo inputVal('email_user_mailchange'); ?>" placeholder="<?php echo html_utf8($tmpUser['email_user']); ?>" />

        <div class="alerte info-container" style="width: 90%; margin-top: 10px;">
            ⚠️
            <div class="text-container">
                Nous avons identifié des problèmes de non réception avec certains fournisseurs de messagerie, notamment Wanadoo, Orange et Free (même si cela fonctionne chez certains utilisateurs).<br>
                Après investigation et signalement du problème aux services concernés, nous ne sommes malheureusement pas en mesure d'y remédier de notre côté.<br>
                Nous vous recommandons de vérifier si nous ne sommes pas dans vos spams sinon, de changer d'hébergeur.
            </div>
        </div>

            <hr style="margin: 20px 0" />
            <h2 id="edit-password"><span class="bleucaf">&gt;</span>Modifier mon mot de passe</h2>
            <p>
                Vous pouvez modifier votre mot de passe <a href="<?php echo generateRoute('account_change_password'); ?>">sur cette page</a>.
            </p>


            <hr style="margin: 20px 0" />
            <h2 id="private"><span class="bleucaf">&gt;</span>Infos privées</h2>

            <div>
                <?php inclure('infos-profil-coordonnees-perso-ffcam', 'vide'); ?>
            </div>

            <br />

            <div style="float:left; width:90%; padding-right:5%">
                Votre numéro de licence FFCAM : <b><?php echo html_utf8($tmpUser['cafnum_user']); ?></b>
            </div>

            <br /><br />

        <div style="float:left; width:90%; padding-right:5%">
                Votre date d'adhésion ou de renouvellement :
                <b>
                <?php
                        // notification d'alerte si l'user doit renouveler sa licence

                        if ($tmpUser['alerte_renouveler_user']) {
                            echo '<span class="alerte">';
                        }
    if (!empty($tmpUser['join_date'])) {
        echo (new \DateTimeImmutable($tmpUser['join_date']))?->format('d/m/Y');
    } else {
        echo 'aucune date connue.';
    }
    if ($tmpUser['alerte_renouveler_user']) {
        echo '</span>';
    } ?>
                </b>
            </div>

            <br style="clear:both" /><br style="clear:both" />

            <div style="float:left; width:45%; padding-right:5%">
                Votre numéro de téléphone personnel :<br />
                <!-- <input type="text" name="tel_user" class="type1" value="<?php echo html_utf8($tmpUser['tel_user']); ?>" placeholder="Tél. portable de préférence" /> -->
                <b><?php echo html_utf8($tmpUser['tel_user']); ?></b>
            </div>

            <div style="float:left; width:45%; padding-right:5%">
                Numéro de téléphone de sécurité :<br />
                <!-- <input type="text" name="tel2_user" class="type1" value="<?php echo html_utf8($tmpUser['tel2_user']); ?>" placeholder="" /> -->
                <b><?php echo html_utf8($tmpUser['tel2_user']); ?></b>
            </div>

            <div style="float:left; width:45%; padding-right:5%; margin-top:10px">
                Votre date de naissance :<br />
                <b><?php echo (new \DateTimeImmutable($tmpUser['birthdate']))?->format('d/m/Y'); ?></b>
            </div>

            <br style="clear:both" /><br style="clear:both" />
            Adresse <span class="mini">- N° &amp; rue - code postal - ville - pays</span><br />
            <b><?php
                echo html_utf8($tmpUser['adresse_user']);
    echo '<br style="clear:both" />';
    echo html_utf8($tmpUser['cp_user']);
    echo '&nbsp;&nbsp;&nbsp;';
    echo html_utf8($tmpUser['ville_user']);
    echo '&nbsp;&nbsp;&nbsp;';
    echo html_utf8($tmpUser['pays_user']); ?></b>

            <br /><br />
            <div style="text-align:center">
                <a class="biglink" href="javascript:void(0)" title="Enregistrer" onclick="$(this).parents('form').submit()">
                    <span class="bleucaf">&gt;</span>
                    ENREGISTRER MES INFORMATIONS / VALIDER MA PHOTO
                </a>
            </div>
            <br />
            <br />
        </form>

        <!-- JS -->
        <script type="text/javascript">
        $().ready(function(){

            // action au clic sur le bouton
            $('#edit_profil_image .delete').bind('click', function(){
                $.fancybox($('#confirm-delete').html());
                return false;
            });

        });
        </script>

    </div>
    <?php
}
