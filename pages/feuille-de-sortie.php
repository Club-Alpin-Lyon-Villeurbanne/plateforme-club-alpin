<?php
    if(!$evt && !$destination){ 
        include(PAGES."404.php");
        exit;
    }
?>
<?php if ($evt) {
    include (INCLUDES.'evt'.DS.'feuille_de_sortie.php');
 } elseif ($destination)  {
    include (INCLUDES.'dest'.DS.'feuille_de_sortie.php');
} ?>