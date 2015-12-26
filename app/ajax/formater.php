<?php

$log .= "\nAcces a ".date("H:i:s")."\n";

if(admin()){

	$str=$_POST['str'];
	$type=intval($_POST['type']);
	$log.="Chaine $str et type $type \n";
	$result=array();
	$result['content']=htmlspecialchars(substr(formater($str , $type), 0, 45), ENT_NOQUOTES);
	$log.="Devient : ".$result['content']."\n";
	$result['success']=true;
	
	// to pass data through iframe you will need to encode all html tags
	echo json_encode($result);
	
	if($p_devmode){
		$log.=" \n \n FIN";
		$fp = fopen(ROOT.'dev.txt', 'w');
		fwrite($fp, $log);
		fclose($fp);
	}
}
?>