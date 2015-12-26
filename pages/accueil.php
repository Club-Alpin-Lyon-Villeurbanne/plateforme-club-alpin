<!-- MAIN -->
<div id="main" role="main" class="bigoo" style="">

	<!-- partie gauche -->
	<div id="left1">

		<!-- Slider -->
		<div id="home-slider">
			<!--
			<div id="home-slider-helper">
				SOUS CETTE PHOTO<br />SE CACHE UN ARTICLE<br />CLIQUEZ POUR LE VOIR !
			</div>
			// -->
			<!-- nav-spots -->
			<div id="home-slider-nav">
				<div id="home-slider-nav-wrapper">
					<div style="width:0px">
						<img src="img/home-slider-nav-1.png" alt="" title="" />
						<?php
						for($i=0; $i<sizeof($sliderTab); $i++) echo '<a href="javascript:void(0)" title="" class="'.($i?'':'up').'"><span>'.($i+1).'</span></a>';
						?>
						<!--
						<a href="javascript:void(0)" title="" class="up"><span>1</span></a>
						<a href="javascript:void(0)" title=""><span>2</span></a>
						<a href="javascript:void(0)" title=""><span>3</span></a>
						<a href="javascript:void(0)" title=""><span>4</span></a>
						-->
						<img src="img/home-slider-nav-2.png" alt="" title="" />
					</div>
				</div>
			</div>
			<!-- nav-fleches -->
			<div id="home-slider-nav2">
				<a href="javascript:void(0)" title="" class="arrow-left"><img src="img/arrow-left.png" alt="&lt;" title="" /></a>
				<a href="javascript:void(0)" title="" class="arrow-right"><img src="img/arrow-right.png" alt="&gt;" title="" /></a>
			</div>
			<!-- slides -->
			<div id="home-slider-wrapper">

				<?php
				for($i=0; $i<sizeof($sliderTab); $i++){
					$article=$sliderTab[$i];

					// check image
					if(is_file('ftp/articles/'.intval($article['id_article']).'/wide-figure.jpg'))
						$img='ftp/articles/'.intval($article['id_article']).'/wide-figure.jpg';
					else
						$img='ftp/articles/0/wide-figure.jpg';

					echo '<a href="article/'.html_utf8($article['code_article'].'-'.$article['id_article']).'.html" class="slide" style="background-image:url('.$img.')" title="CLIQUEZ POUR VOIR L\'ARTICLE">
						<p class="alaune">ARTICLE A LA UNE</p>
						<h2>'.html_utf8($article['titre_article']).'</h2>
					</a>';
				}
				?>

			</div>
		</div>

		<div style="padding:10px 0 20px 30px" id="home-actus">
			<?php
			// H1 : TITRE PRINCIPAL DE LA PAGE EN FONCTION DE LA COMM COURANTE
			// nom par défaut (hors commission selectionnéé) -->
			if(!$current_commission)
				echo '<h1 class="actus-h1" id="home-articles">Les actus du <b>'.$p_sitename.'</b></h1>';
			// nom en fonction de la commission
			else {
				echo '<h1 class="actus-h1 shortened" id="home-articles">Les actus <b>'.html_utf8($comTab[$current_commission]['title_commission']).'</b></h1>';
				if(sizeof($articlesTab)) echo '<a href="accueil.html" title="Afficher tous les articles sans distinction" class="lien-big" style="float:right; margin:6px 20px 0 0"><span style="color:#3C91BF">&gt;</span> Voir toutes les actus</a>';
			}


			// LISTE D'ARTICLES
			if(!sizeof($articlesTab)) echo '<p><br />Aucune actualité pour cette commission...</p>';
			for($i=0; $i<sizeof($articlesTab); $i++){
				$article=$articlesTab[$i];
				include INCLUDES.'article-lien.php';
			}

			// paginatino
			if($nbrPages>1){
				echo '<nav class="pageSelect"><br />';
				// navigation droite/gauche
				echo '<div class="navleftright"><a href="javascript:void(0)" title="" class="arrow left">&lt;&lt;</a><a href="javascript:void(0)" title="" class="arrow right">&gt;&gt;</a></div>';

				echo '<div class="pageSelectIn"><div class="pageSelectInWrapper">';
					for($i=1; $i <= $nbrPages; $i++){
						// if($i>1 && $i%10 == 1) echo '<br />';
						echo '<a href="'.$versCettePage.'?pagenum='.$i.'#home-actus" title="" class="'.($pagenum==$i?'up':'').'">P'.$i.'</a> '.($i< $nbrPages?'  ':'');
					}
				echo '</div></div>';
				echo '</nav>';



			}

			echo '<br style="clear:both" />';
			if($current_commission)
				echo '<a href="accueil.html#home-actus" title="Afficher tous les articles sans distinction" class="lien-big" style="float:right; margin:6px 20px 0 0"><span style="color:#3C91BF">&gt;</span> Voir toutes les actus</a>';

			?>
			<!-- liens vers les flux RSS -->
			<a href="rss.xml?mode=articles" title="Flux RSS de toutes les actualités du club" class="nice2">
				<img src="img/base/rss.png" alt="RSS" title="" /> &nbsp;
				actualités du club
			</a>
			<?php
			if($current_commission){
				echo '<a href="rss.xml?mode=articles-'.$current_commission.'" title="Flux RSS des actualités «'.$current_commission.'» uniquement" class="nice2">
						<img src="img/base/rss.png" alt="RSS" title="" /> &nbsp;
						actualités «'.$comTab[$current_commission]['title_commission'].'»
					</a>';
			}
			?>
			<br style="clear:both" />

		</div>

	</div>

	<!-- partie droite -->
	<?php
	include INCLUDES.'right-type-agenda.php';
	?>


	<br style="clear:both" />
</div>