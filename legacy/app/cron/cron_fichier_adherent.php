<?php

use App\Legacy\LegacyContainer;
use App\Utils\NicknameGenerator;

include __DIR__.'/../../app/includes.php';

function mysqli_result($res, $row = 0, $col = 0)
{
    if ($row >= 0 && mysqli_num_rows($res) > $row) {
        mysqli_data_seek($res, $row);
        $resrow = mysqli_fetch_row($res);
        if (isset($resrow[$col])) {
            return $resrow[$col];
        }
    }

    return false;
}

//_________________________________________________
// cette page a pour objet d'effectuer les tâches automatisées du site

// TRIGGER CAPITAL ! ACTIVE ET DESACTIVE L'EFFICACITE DU CHRON - ENVOI DE MAIL - INSERTION BDD
$chron_sendmails = true;
$chron_savedatas = true;

// faire tourner en continu le script même en cas de fermeture du navigateur
ignore_user_abort(true);
set_time_limit(0);

// *******************************
// MISE A JOUR DE LA BASE DE DONNEE DES USERS DEPUIS LE FICHIER
echo '---------------------------- update_users '.date('Y-m-d H:i:s')." ----------------------------\n";

$fileTab = [];

// Fichiers à lire
foreach ($p_ffcam as $ffcam) {
    $fileTab[] = __DIR__.'/../../config/ffcam-ftp-folder/'.$ffcam.'.txt';
}

// pour chaque fichier...

foreach ($fileTab as $file) {
    $nb_insert = 0;
    $nb_update = 0;
    // s'il existe
    if (file_exists($file) && is_file($file)) {
        echo "lecture du fichier $file ...\n";
        // lecture de la ligne - fgets() remplace file(), en raison du poids du fichier

        echo "tous les droits de traitement\n";
        chmod($file, 0777);
        echo "tous les droits de traitement attribués\n";
        if (!$handle = @fopen($file, 'r')) {
            exit("ouverture du fichier '$file' impossible : $!");
        }
        echo "tous les droits de traitement\n";
        if ($handle) {
            echo "handle\n";
            $i = 0;
            while (($line = fgets($handle)) !== false) {
                echo "line $i\n";
                ++$i;
                $tmpErrTab = [];
                // formatage utf8, et antislashes
                $line = utf8_encode($line);
                $line = stripslashes($line);

                echo $i."\n".$line."\n";
                // pour chaque ligne, séparation des données
                $line = explode(';', $line);

                echo $i."\nligne ".__LINE__."\n";
                // vérification du format. Si cette vérif échoue, on laisse même tomber les suivantes
                if (count($line) < $p_csv_adherent_nb_colonnes) {
                    $messg = "Format invalide : la ligne ne contient pas $p_csv_adherent_nb_colonnes valeurs mais ".count($line)."\n";
                    echo $messg;
                    $tmpErrTab[] = $messg;
                } else {
                    echo $i."\nligne ".__LINE__."\n";
                    if (!is_numeric($line[0])) {
                        $tmpErrTab[] = "Numéro d'adhérent complet invalide";
                    } // numéro d'adhérent
                    if (!is_numeric($line[1])) {
                        $tmpErrTab[] = 'Numéro de club invalide';
                    }
                    echo $i."\nligne ".__LINE__."\n";
                    if (!is_numeric($line[2])) {
                        $tmpErrTab[] = "Numéro d'adhérent invalide";
                    }
                    echo $i."\nligne ".__LINE__."\n";
                    if (!preg_match('#[0-9]{4}-[0-9]{2}-[0-9]{2}#', $line[6])) {
                        $tmpErrTab[] = 'Date de naissance invalide';
                    }
                    // STOP : inutile de trop brider, laissons une marge d'erreur, corrigeable dans la base du site
                }

                // si erreur, affichée dans le log
                if (count($tmpErrTab) > 0) {
                    echo "!!! Erreurs ligne $i :\n !!! - ".implode('\n -- ', $tmpErrTab)."\n";
                    print_r($line);
                }

                // lecture des données
                else {
                    // CREATION / MAJ DE CET ADHERENT
                    echo 'formatage des variables';
                    echo $i."\nligne ".__LINE__."\n";

                    // formatage des variables
                    $tel_user_old = $line[26];
                    $tel2_user_old = $line[27];

                    $line[26] = str_ireplace('o', '0', $line[26]); // suppression des erreurs de frappe
                    $line[27] = str_ireplace('o', '0', $line[27]); // suppression des erreurs de frappe

                    $line[26] = preg_replace('/^\+*33/', '0', $line[26]); // suppression du 33
                    $line[27] = preg_replace('/^\+*33/', '0', $line[27]); // suppression du 33

                    $line[26] = preg_replace('/[^\d]+/', '', $line[26]); // suppression des espaces dans les tel
                    $line[27] = preg_replace('/[^\d]+/', '', $line[27]); // suppression des espaces dans les tel
//echo $i."\nligne ".__LINE__."\n";
                    if (10 != strlen($line[26])) {
                        // la mise en forme du numero a echouee
                        // retablissement du numero original du fichier
                        $line[26] = $tel_user_old;
                    }
                    if (10 != strlen($line[27])) {
                        // la mise en forme du numero a echouee
                        // retablissement du numero original du fichier
                        $line[27] = $tel2_user_old;
                    }
                    //echo $i."\nligne ".__LINE__."\n";
                    $line[26] = preg_replace('/[^\d]+/', '', $line[26]); // suppression des espaces dans les tel
                    $line[27] = preg_replace('/[^\d]+/', '', $line[27]); // suppression des espaces dans les tel
//echo $i."\nligne ".__LINE__."\n";
                    $cafnum_user = LegacyContainer::get('legacy_mysqli_handler')->escapeString($line[0]);
                    $firstname_user = LegacyContainer::get('legacy_mysqli_handler')->escapeString($line[10]);
                    $lastname_user = LegacyContainer::get('legacy_mysqli_handler')->escapeString($line[9]);
                    $tab = explode('-', $line[6]);
                    $birthday_user = mktime(1, 0, 0, $tab[1], $tab[2], $tab[0]);
                    $tel_user = LegacyContainer::get('legacy_mysqli_handler')->escapeString($line[27]);
                    $tel2_user = LegacyContainer::get('legacy_mysqli_handler')->escapeString($line[26]);
                    $adresse_user = LegacyContainer::get('legacy_mysqli_handler')->escapeString(trim($line[11]." \n".$line[12]." \n".$line[13]." \n".$line[14]));
                    $cp_user = LegacyContainer::get('legacy_mysqli_handler')->escapeString($line[15]);
                    $ville_user = LegacyContainer::get('legacy_mysqli_handler')->escapeString($line[16]);
                    $civ_user = LegacyContainer::get('legacy_mysqli_handler')->escapeString($line[8]);
                    $doit_renouveler_user = '0';
                    // $date_adhesion_user=''; # calculé plus loin avec des tests supplémentaires
                    $date_adhesion_user = null;
                    $alerte_renouveler_user = '0';
                    $nickname_user = NicknameGenerator::generateNickname($firstname_user, $lastname_user);

                    //echo $i."\nligne ".__LINE__."\n";
                    // formatage du nom et prénom & civilité : en minuscule, avec majuscule au début
                    $formatTab = [8, 9, 10]; // index dans le tableau, des elements à formater
                    foreach ($formatTab as $indice) {
                        // $tmpTab = explode(' ', $line[$indice]);
                        $tmpTab = preg_split("/[\s-]+/", $line[$indice]); // séparateur incluant les tirets
                        //print_r($tmpTab);
                        $line[$indice] = '';
                        foreach ($tmpTab as $str) {
                            $line[$indice] .= ($line[$indice] ? ' ' : '').mb_strtoupper(substr($str, 0, 1), 'UTF-8').mb_strtolower(substr($str, 1), 'UTF-8');
                        }
                    }
                    //echo $i."\nligne ".__LINE__."\n";
                    // FILIATION : MISE À JOUR DE LA VALEUR
                    if ((int) $line[5] > 0) {
                        // filiation existante
                        $cafnum_parent_user = LegacyContainer::get('legacy_mysqli_handler')->escapeString($line[1].$line[5]); // concaténation ligne 1 (club) pour obtenir un numéro d'adhérent complet
                    } else {
                        // filiation inexistante
                        $cafnum_parent_user = '';
                    }
                    //echo $i."\nligne ".__LINE__."\n";
                    // OBSOLESCENCE DU COMPTE : CET ADHÉRENT DOIT-IL RENOUVELER SA LICENCE ?? BASÉ SUR LA DATE EN 8e colonne
                    if ('0000-00-00' == $line[7]) {
                        // on vérifie la date, car il y a un battement entre le 25 aout (pour prendre large) et le 31 décembre
                        // où le non-renouvellement de licence est toléré
                        if (((int) (date('m')) < 8 || (8 == (int) (date('m')) && (int) (date('d')) < 25)) || ((int) (date('m')) > 10)) {
                            // avant le 25.08 - desactivation
                            $doit_renouveler_user = '1';
                            $alerte_renouveler_user = '1';
                        } else {
                            // entre le 25.08 et le 31.12 - alerte
                            $doit_renouveler_user = '0';
                            $alerte_renouveler_user = '1';
                        }
                    } else {
                        $tab = explode('-', $line[7]);
                        $date_adhesion_user = ", date_adhesion_user='".mktime(1, 0, 0, $tab[1], $tab[2], $tab[0])."'";
                        $doit_renouveler_user = '0';
                        $alerte_renouveler_user = '0';
                    }

                    //echo $i."\nligne ".__LINE__."\n";
                    // on vérifie que ce numéro d'adhérent n'existe pas déjà dans la base de donnée USER
                    $req = "SELECT id_user FROM caf_user WHERE cafnum_user LIKE '$cafnum_user'";
                    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);

                    //echo  $req."\n";

                    if (0 == mysqli_num_rows($handleSql)) {
                        echo "aucun ID trouve pour $cafnum_user";

                        // s'il n'existe pas, alors creons-le en mode desactive
                        echo "INSERT $cafnum_user\n";

                        // insertion
                        $req = "INSERT INTO caf_user(id_user, cafnum_user , firstname_user , lastname_user , created_user , birthday_user , tel_user , tel2_user , adresse_user , cp_user , ville_user , civ_user , cafnum_parent_user, valid_user, doit_renouveler_user, alerte_renouveler_user, ts_insert_user, nickname_user)
						VALUES (NULL, '$cafnum_user', '$firstname_user', '$lastname_user',  '".time()."', '$birthday_user', '$tel_user', '$tel2_user', '$adresse_user', '$cp_user', '$ville_user', '$civ_user', '$cafnum_parent_user', 0, $doit_renouveler_user, $alerte_renouveler_user, ".time()." , '$nickname_user');";
                        ++$nb_insert;
                    } elseif ($idUser = mysqli_result($handleSql, 0)) {
                        // adherent existant : mise a jour

                        echo "UPDATE $cafnum_user - $idUser \n";

                        // si l'adhérent n'a pas encore ré-adhéré, on ne met pas à jour la date d'adhésion
                        if ('0000-00-00' == $line[7]) {
                            $req = 'UPDATE caf_user SET ts_update_user='.time().", firstname_user='$firstname_user', lastname_user='$lastname_user', doit_renouveler_user='$doit_renouveler_user', birthday_user='$birthday_user', civ_user='$civ_user', cafnum_parent_user='$cafnum_parent_user', tel_user='$tel_user',	tel2_user='$tel2_user', adresse_user='$adresse_user', cp_user='$cp_user', ville_user='$ville_user', alerte_renouveler_user='$alerte_renouveler_user', nickname_user='$nickname_user', manuel_user=0, nomade_user=0 WHERE id_user=$idUser AND cafnum_user = '$cafnum_user'";
                            ++$nb_update;
                        } else {
                            $req = 'UPDATE caf_user SET ts_update_user='.time().", firstname_user='$firstname_user', lastname_user='$lastname_user', doit_renouveler_user='$doit_renouveler_user', birthday_user='$birthday_user', civ_user='$civ_user', cafnum_parent_user='$cafnum_parent_user', tel_user='$tel_user',	tel2_user='$tel2_user', adresse_user='$adresse_user', cp_user='$cp_user', ville_user='$ville_user', alerte_renouveler_user='$alerte_renouveler_user', nickname_user='$nickname_user', manuel_user=0, nomade_user=0 ".
                            $date_adhesion_user
                            ." WHERE id_user=$idUser AND cafnum_user = '$cafnum_user'";
                            ++$nb_update;
                        }
                    } else {
                        $tmpErrTab[] = "Erreur INSERT/UPDATE adherent $cafnum_user";
                    }

                    if (!($handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req))) {
                        echo wordwrap("!!! Erreur SQL lors de l'integration de la ligne $i : ".LegacyContainer::get('legacy_mysqli_handler')->lastError()." ($req)\n");
                    }
                }

                if (count($tmpErrTab) > 0) {
                    echo "!!! Erreurs ligne $i :\n !!! - ".implode('\n -- ', $tmpErrTab)."\n";
                    print_r($line);
                }
            }
            fclose($handle);
        }
        if ($nb_insert > 0 || $nb_update > 0) {
            rename($file, $file.'.'.date('Y-m-d'));
            echo "after rename\n";
            exec('gzip '.$file.'.'.date('Y-m-d'));
        }

        echo "INSERT: $nb_insert, UPDATE:$nb_update\n";

        $req = "INSERT INTO  `caf_log_admin` (`id_log_admin` ,`code_log_admin` ,`desc_log_admin` ,`ip_log_admin`,`date_log_admin`)
			VALUES (NULL , 'import-ffcam',  'INSERT: $nb_insert, UPDATE:$nb_update, fichier ".basename($file)."', '127.0.0.1', '".time()."');";
        if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
            $errTab[] = 'Erreur SQL lors du log';
        }
    } else {
        echo "!!! Erreur : le fichier n'existe pas : $file\n";

        $req = "INSERT INTO  `caf_log_admin` (`id_log_admin` ,`code_log_admin` ,`desc_log_admin` ,`ip_log_admin`,`date_log_admin`)
			VALUES (NULL , 'import-ffcam',  'fichier inexistant : $file', '127.0.0.1', '".time()."');";
        if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
            $errTab[] = 'Erreur SQL lors du log';
        }
    }
}

// blocage des comptes expires ( inscription < 31/08/Y-1 ) ou non mis ŕ jour depuis + de 10j
$req = 'UPDATE caf_user SET doit_renouveler_user=1 WHERE id_user!=1 AND nomade_user=0 AND manuel_user=0 AND (
    FROM_UNIXTIME( date_adhesion_user ) < MAKEDATE('.(date('Y') - 1).', 240 )
    OR ts_update_user < (UNIX_TIMESTAMP( ) - ( 86400 *10 ))
)';

if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
    echo 'Erreur SQL lors du log:'.LegacyContainer::get('legacy_mysqli_handler')->lastError();
}

// suppression des filiations sur comptes non mis ŕ jour depuis + de 200j
$req = "UPDATE caf_user SET cafnum_parent_user = '' WHERE
    ts_update_user < (UNIX_TIMESTAMP( ) - ( 86400 * 200 ))
";

if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
    echo 'Erreur SQL lors du log:'.LegacyContainer::get('legacy_mysqli_handler')->lastError();
}
