<?php

//header("Cache-Control: max-age=10");
//header("Content-Type: text/xml");

//_________________________________________________ DEFINITION DES DOSSIERS
define ('DS', DIRECTORY_SEPARATOR );
define ('ROOT', dirname(__FILE__).DS);				// Racine
include (ROOT.'app'.DS.'includes.php');
include SCRIPTS.'connect_mysqli.php';

//_________________________________________________ PARAMS
$first=1;
$last=30;
$count=0;
$jour='';
$comm='';
$titre='';
$niveau='';
$current='';
$class='';
$jours = [
    "Mon" => "Lun",
    "Tue" => "Mar",
    "Wed" => "Mer",
    "Thu" => "Jeu",
    "Fri" => "Ven",
    "Sat" => "Sam",
    "Sun" => "Dim",
];
$mois = [
   "01" => "janvier",
   "02" => "février",
   "03" => "mars",
   "04" => "avril",
   "05" => "mai",
   "06" => "juin",
   "07" => "juillet",
   "08" => "aout",
   "09" => "septembre",
   "10" => "octobre",
   "11" => "novembre",
   "12" => "décembre"
];
echo ' <!doctype html>
<html lang="fr-FR">
<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
  <title></title>
  <style type="text/css">
	th, td { padding: 10px; }
	#imglogo { display: block; margin-left: auto; margin-right: auto; }
        </style>
        <!-- icon -->
        <link rel="shortcut icon" href="favicon.ico" />
        <link rel="stylesheet" href="css/caflv_tv.css" type="text/css" />

  <div id="main" role="main" style="">
     <div style="padding:2px 2px 2px 2px">
        <!-- H1 : TITRE PRINCIPAL DE LA PAGE EN FONCTION DE LA COMM COURANTE -->
        <h1 class="implogo">
	<img src="/img/caflv_tvlogo.png">
        </h1>
</head>
<body>
<div style="padding:2px 2px 2px 2px">
<table id="agenda">';

$out='';
$plage=$_GET['plage'];
if($plage != ''){
	// plage précisée x-y
	$first=substr($plage,0,strpos($plage,'-'));
	$last=substr($plage,strpos($plage,'-')+1);
}
//$out.='<p>first='.$first.' last='.$last.'</p>\n';

// *** SORTIES
$req="select e.tsp_evt, e.commission_evt, e.titre_evt, e.massif_evt, e.difficulte_evt, c.title_commission
        from caf_evt e
        inner join caf_commission c
        on e.commission_evt = c.id_commission
        where e.status_evt = 1 and e.tsp_evt > ".time()."
	ORDER BY  e.tsp_evt ASC LIMIT $last";

$handleSql = $mysqli->query($req);

while($handle=$handleSql->fetch_assoc()){
	$count+=1;
	if ($count < $first) continue;
	$color= $count%2 ? "#e6f2ff" : "#99ccff";
	$out.='<tr bgcolor="'.$color.'">';

	$tsp_evt = $handle['tsp_evt'];
	$jour=$jours[date('D',$tsp_evt)].date(' d ',$tsp_evt).$mois[date('m',$tsp_evt)];
	$out.='<td class="agenda-gauche ">';
	//if ($jour ==  $current) { $out.=$jour."</td>\n";}
	//else { $out.='<b>'.$jour."</b></td>\n";}
	if ($jour ==  $current) { $out.="</td>\n";}
	else { $out.=$jour."</td>\n";}
	$current=$jour;

	$comm=$handle['commission_evt'];
	if ($comm == 27 || $comm == 28 || $comm == 29) {$comm=0;}
		$out.='<td><div class="picto">
		<img src="http://www.clubalpinlyon.fr/ftp/commission/'.$comm.'/picto-dark.png" alt="" title="" class="picto-light" />
		</div></td>';
	//	}
	// pas de picto
	//else {$out='<td></td>';}
	$out.='<td>'.$handle['title_commission']."</td>\n";

	$out.='<td class="agenda-evt-debut" target="_top"><div class="droite">';
		$out.='<h2>'.$handle['titre_evt'].'</h2>';
		$out.="</div>\n";

	$out.='<td>';
	$massif=$handle['massif_evt'];
	if ($massif != '') { $out.=$massif; }
	if ($handle['difficulte_evt'] != '') {
		if ($massif != '') { $out.=' - '; }
		$out.=$handle['difficulte_evt'];
		}
	$out.="</td>\n";

	$out.='</tr>';
}


$mysqli->close();
$out.="</table></div></body>
</html>";
echo $out;
?>
