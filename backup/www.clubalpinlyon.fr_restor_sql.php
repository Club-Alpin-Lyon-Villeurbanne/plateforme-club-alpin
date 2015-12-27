<?php
header('Content-type: text/html; charset=UTF-8'); 

// Votre fichier doit être compressé au format Gzip et porter le nom dumpDB.sql.gz afin d’être utilisé dans le script ci-dessous.
// Vous devez également déposer le fichier compressé dans le même répertoire que le script de restauration.

  // Indiquez vos données d'accès
  $host= 'mysql51-39.bdb';
  $user= 'clubalpi001';
  $pass= 'clublyon007';
  $db=   'clubalpi001';

  // Restauration du fichier Gzip
  system(sprintf(
    'gunzip -c %s/backup/dumpDB.sql.gz | mysql --default-character-set=utf8 -h %s -u %s -p%s %s',
    getenv('DOCUMENT_ROOT'),
    $host,
    $user,
    $pass,
    $db
  ));
  echo '+DONE';
?>
