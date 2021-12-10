<?php

require __DIR__.'/app/includes.php';

$p = $_GET['p'];
$pageAdmin = $_GET['admin'];

// verification d'inclusion
if ('pages/' != substr($p, 0, 6) && 'includes/' != substr($p, 0, 9)) {
    echo 'err 1';
} elseif (strpos($p, '../')) {
    echo 'err 2';
} elseif (strpos($p, 'https://')) {
    echo 'err 3';
} else {
    // lien vers cette page (pour formulaires, ou ancres)
    $versCettePage = 'includer.php?null=0';
    foreach ($_GET as $key => $val) {
        $versCettePage .= '&'.$key.'='.$val;
    }

    //_________________________________________________ HEADER AU CHOIX (inclut le doctype)
    if ($pageAdmin) {
        require __DIR__.'/pages/header-admin.php';
    } else {
        require __DIR__.'/pages/header.php';
    }

    echo '<div id="includer-stuff">';
    if (file_exists(__DIR__.'/'.$p)) {
        require __DIR__.'/'.$p;
    } else {
        echo 'Fichier introuvable : '.__DIR__.'/'.$p;
    }
    echo '</div>
	<!-- Waiters -->
	<div id="loading1" class="mybox-down"></div>
	<div id="loading2" class="mybox-up">
		<p>Opération en cours<br /><br /><img src="/img/base/loading.gif" alt="" title="" /></p>
	</div>
	';
}
