<?php

// TO DO
// -controler le nombre d'evenements
// par rapport à la plage demadée
// -mettre le voyant rouge si complet
// -nettoyer le css
//

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

$jour=$jours[date('D')].date(' d ').$mois[date('m')];
$heure=date('  H').date(':i ');

// ../caflv_tv.php?[article=x]|[agenda[=x-y][comm=z]]
$cid='';
$qs=explode ('&',$_SERVER['QUERY_STRING']);
$fct=explode ('=',$qs[0]);
$id=$fct[1];
if ($qs[1] != '') {
	$comm=explode ('=',$qs[1]);
	if ($comm[1] != '') {$cid=' and e.commission_evt='.$comm[1].' ';}
}

//
// --- article -------------------------------------------------------
//
if ($fct[0] == 'article') {

if ($id == 0){
$req="select max(id_article) from caf_article where status_article = 1";
$handleSql = $mysqli->query($req);
if ($row=$handleSql->fetch_row()) {
	$id=$row[0];
	}
} else if ($id < 0) {
$k=$id;
$req="select id_article from caf_article where status_article = 1 order by id_article desc limit 20";
$handleSql = $mysqli->query($req);
while ($k<=0 && $row=$handleSql->fetch_row()) {
	$id=$row[0];
	$k++;
	}
}


//echo "id=[".$id."]";
if($id != ''){
$req="select a.titre_article,a.cont_article,a.commission_article,c.title_commission
        from caf_article a
	left join caf_commission c on a.commission_article = c.id_commission
        where a.id_article = $id and status_article = 1";
$handleSql = $mysqli->query($req);
if($row=$handleSql->fetch_row()){
	$titre=$row[0];
	$article=$row[1];
	$commission=$row[3];
}
$mysqli->close();
}

if ($article == '') {
	$titre='Article "'.$id.'" non accessible !!!';
	$commission='';
}

echo ' <!doctype html>
<head>
 <title></title>
 <style type="text/css">
  body { bgcolor:#e6f2ff; border-style: solid; border-color:#0000cc; border-width:thin;padding:2px 2px 2px 2px}
  th, td { padding: 5px; }
  #imglogo { display: block; margin-left: auto; margin-right: auto; }
  div.t {
   /*white-space: nowrap;
   overflow: hidden;
   text-overflow: ellipsis; */
   /*border: 1px solid #000000;*/
   width:100%;
   font-size:25px;
   bgcolor:#e6f2ff;
   }
  table.entete { width:100%; font-family:din-bold; }
 </style>
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
     <div id="main" role="main" style="">
     <table class="entete" bgcolor="#99ccff"><tr>
     <td style="font-size:20px;" align="left">A suivre sur<br>www.clubalpinlyon.fr</td>
     <td style="font-size:40px;" align="center">Les actus '.$commission.'</td>
     <td style="font-size:20px;" align="right">'.$jour.'</td>
     </tr></table></div>';

//if(filesize('ftp/articles/'.intval($id).'/figure.jpg') > 60000 )
//	$img='<img src="ftp/articles/'.intval($id).'/figure.jpg" alt="" width="30%" style="padding:2px 2px 2px 2px"';
//else
//	$img='';
//$out.=$img;

$out.='<div class="t"><article><h2 align="center">'.$titre.'</h2>'.$article.'</article></div></body></html>';
} //--- end of article ---

//
// --- agenda ------------------------------------------------------
//
else {

echo ' <!doctype html>
<html lang="fr-FR">
<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
  <title></title>
  <style type="text/css">
	* {margin:0;padding:0}
	th, td { padding: 10px; }
	#imglogo { display: block; margin-left: auto; margin-right: auto; }
	body{font-size:20px;font-family:Verdana, sans-serif;font-weight:bold;background:#f7f8fc;color:#333333;}
	h2,h3{font-family:DINBold; font-weight:400; }
	p{padding:5px 0px;}
	table#agenda{width:100%; border-collapse:collapse; border-bottom:1px solid #c9c9c7;}
	table#agenda tr td{border-top:1px solid #c9c9c7; vertical-align:top;}
	table#agenda td.agenda-gauche{border-right:1px solid #c9c9c7;}
        </style>

     <div id="main" role="main" style="">
     <table style="width:100%"><tr>
     <td align="left"><div style="padding:2px 2px 2px 2px" class="implogo">
        <img src="/img/caflv_tvlogo.png">
     </div></td>
     <td style="font-family:din-bold;font-size:30px;" align="center;">www.clubalpinlyon.fr</td>
     <td align="right"><p>'.$jour.'</p><p>'.$heure.'</p></td>
     </tr></table>
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<div style="padding:2px 2px 2px 2px">
<table id="agenda">';

$out='';
$agenda=$id;
if($agenda != ''){
	// agenda précisée x-y
	$first=substr($agenda,0,strpos($agenda,'-'));
	$last=substr($agenda,strpos($agenda,'-')+1);
}
//$out.='<p>first='.$first.' last='.$last.'</p>\n';

// *** SORTIES
$req="select e.tsp_evt, e.commission_evt, e.titre_evt, e.massif_evt, e.difficulte_evt, c.title_commission
        from caf_evt e
        left join caf_commission c on e.commission_evt = c.id_commission
        where e.status_evt = 1 and e.tsp_evt > ".time().$cid."
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
	$out.='<td><div class="picto"><img src="http://www.clubalpinlyon.fr/ftp/commission/';
	$out.=$comm.'/picto-dark.png" alt="" title="" class="picto-light" /></div></td>';
	$out.='<td>'.$handle['title_commission']."</td>\n";

	$out.='<td target="_top"><div>';
	$out.='<h2>'.$handle['titre_evt'].'</h2>'."</div>\n";

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
} //--- end of agenda ---


echo $out;
?>
