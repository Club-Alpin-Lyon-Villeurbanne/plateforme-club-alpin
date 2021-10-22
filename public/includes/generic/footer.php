<footer id="mainfooter">
	<!-- My footer -->
	<div class="tiers-footer" style="margin-right:40px; width:300px; font-size:9px; line-height:14px">
		<?php
        inclure('mainfooter-1', 'tiers-footer');

        // liste des commissions visibles par ordre alphabétique
        echo '<div style="width:47%; float:left; white-space:nowrap; overflow:hidden;">';
        ksort($comTab);
        $i = 0;
        $break = ceil(count($comTab) / 2) - 1;
        foreach ($comTab as $code => $data) {
            echo '<a href="/accueil/'.html_utf8($code).'.html" title="">&gt; '.html_utf8($data['title_commission']).'</a>'
                .($i != $break ? '<br />' : '</div><div style="width:47%; float:right; white-space:nowrap; overflow:hidden;">');
            ++$i;
        }

        echo '</div>';
        ?>

		<!-- lien vers la page dédiée aux responsables -->
		<div class="tiers-footer mini" style="padding-top:10px">
			<a style="color:gray" href="responsables.html" title="">&gt; Voir les responsables par commission</a>
		</div>
	</div>

	<div class="tiers-footer">
		<?php
        inclure('mainfooter-2', 'tiers-footer');
        ?>
	</div>

	<div class="tiers-footer" style="float:right">
		<?php
        inclure('mainfooter-3', 'tiers-footer');
        ?>
	</div>

	<br style="clear:both" />

	<a style="display: none;" onClick="document.body.scrollTop = document.documentElement.scrollTop = 0;" class="scrollup">Remonter</a>

</footer>
