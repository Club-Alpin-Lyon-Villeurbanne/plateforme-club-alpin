<?php

    include SCRIPTS.'connect_mysqli.php';
    $lang_content_inline = trim(stripslashes($_POST['lang_content_inline']));
    $code_content_inline = trim(stripslashes($_POST['code_content_inline']));
    $contenu_content_inline = trim(stripslashes($_POST['contenu_content_inline']));
    // details
    $groupe_content_inline = (int) ($_POST['groupe_content_inline']);
    $linkedtopage_content_inline = (int) ($_POST['linkedtopage_content_inline']);

    // checks
    // $pattern="#^[a-z](-)*$#";
    // $pattern="#^([a-z]+(-)*)$#";
    $pattern = '#^([0-9a-z-]+)$#';

    if (!preg_match($pattern, $code_content_inline)) {
        $errTab[] = 'Erreur de formatage du code : celui-ci ne peut comporter que des minuscules et des tirets';
    }
    if (2 != strlen($lang_content_inline)) {
        $errTab[] = 'Erreur de langue';
    }

    if (!isset($errTab) || 0 === count($errTab)) {
        $lang_content_inline = $mysqli->real_escape_string($lang_content_inline);
        $code_content_inline = $mysqli->real_escape_string($code_content_inline);
        $contenu_content_inline = $mysqli->real_escape_string($contenu_content_inline);

        $req = 'INSERT INTO `'.$pbd."content_inline` (`id_content_inline` ,`groupe_content_inline` ,`code_content_inline` ,`lang_content_inline` ,`contenu_content_inline` ,`date_content_inline` ,`linkedtopage_content_inline`)
															VALUES (NULL , '$groupe_content_inline', '$code_content_inline', '$lang_content_inline', '$contenu_content_inline', '$p_time', '$linkedtopage_content_inline');";
        if (!$mysqli->query($req)) {
            $erreur = 'Erreur BDD<br />'.$req;
        }
    }

    $mysqli->close();
