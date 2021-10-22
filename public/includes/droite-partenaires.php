<?php
/*
    AFFICHE LE SLIDER "PARTENAIRES" SUR LA PAGE PRINCIPALE

    Charge les infos partenaires depuis la base de donnee (caf_partenaires) et affiche le slider "partenaires" dans la colonne de droite
*/
if ($p_showPartenairesSlider) {
    include $scriptsDir.'connect_mysqli.php';

    $partenairesTab = [];
    $partenairesNb = 0;
    $req = 'SELECT UPPER(part_name) as part_name, part_image, part_url, part_id FROM caf_partenaires WHERE part_enable=1 AND part_name IS NOT NULL AND part_image IS NOT NULL ORDER BY part_order';
    $result = $mysqli->query($req);

    if (!$mysqli->query($req)) {
        $errTab = 'Erreur SQL : '.$mysqli->error;
        error_log($mysqli->error);
    } else {
        while ($row = $result->fetch_array(\MYSQLI_ASSOC)) {
            if (file_exists('ftp/partenaires/'.$row['part_image'])) {
                $partenairesTab[] = $row;
            } else {
                error_log("l'image partenaire n'existe pas : ".'ftp/partenaires/'.$row['part_image']);
                //mylog("partenaires", "l'image partenaire n'existe pas : ".'ftp/partenaires/'.$row['part_image'], false);
            }
        }
        $partenairesNb = count($partenairesTab);

        mysqli_free_result($result);
        if ($partenairesNb > 0) {
            ?>

			<script type="text/javascript" src="/js/slidesjs/jquery.slides.min.js"></script>

			<div class="right-light-in">
				<h1 class="partenaires-h1"><a href="/pages/nos-partenaires-prives.html" title="nos partenaires">nos partenaires</a></h1>
				<div id="slider-partenaires">

						<div id="slides">
							<?php
                                foreach ($partenairesTab as $partenaire) {
                                    echo '<a target="_blank" href="/goto/partenaire/'.$partenaire['part_id'].'/'.formater($partenaire['part_name'], 3).'.html"><img src="/ftp/partenaires/'.$partenaire['part_image'].'" alt="'.$partenaire['part_name'].'"></a>';
                                } ?>
						</div>

						<script type="text/javascript">

							<!-- // enable slideshow only if we have more than one slide -->
							$(function(){
								if ($("#slides > a").length > 1) {
									$("#slides").slidesjs({
										start: <?php echo rand(1, $partenairesNb); ?>,
										width: 270,
										height: 110,
										navigation: {active: false},
										pagination: {active: false},
										play: {active: false,effect: "slide",auto: true}
									});
								} else {
									$("#slides").show();
								}
							});

						</script>


				</div>
			</div>

<?php
        }
    }

    $mysqli->close;
}
?>


