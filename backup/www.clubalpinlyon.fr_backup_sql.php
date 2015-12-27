<?php

// Voici un exemple de code PHP que vous pouvez utiliser afin de réaliser votre backup facilement.
// En appelant ce dernier dans un navigateur, un backup de votre base sera généré dans le répertoire ou se trouve le fichier au format Gzip.


// Personnalisez ici vos données d'accès
$host = 'mysql51-39.bdb';
$user = 'clubalpi001';
$pass = 'clublyon007';
$db =   'clubalpi001';
$site = 'www.clubalpinlyon.fr';
$date = date('Ymd_His');
$root = getenv('DOCUMENT_ROOT');

$mysqli = new mysqli($host, $user, $pass, $db);
$mysqli->set_charset('UTF8');

$req = 'SHOW TABLE STATUS WHERE Data_free > 102400';
$res = $mysqli->query($req);

while($row = $res->fetch_array(MYSQLI_ASSOC)){
    $mysqli->query('OPTIMIZE TABLE ' . $row['Name']);
}
$mysqli->close();

// Création de la sauvegarde
system('mysqldump --default-character-set=utf8 --opt --host='.$host.' --user='.$user.' --password="'.$pass.'" '.$db.' | gzip > '.$root.'/backup/DB_'.$site.'_'.$date.'.sql.gz');
echo '+DONE';
?>
