<a class="agenda-evt-courant" href="/sortie/<?php echo html_utf8($evt['code_evt']) . '-' . (int) $evt['id_evt']; ?>.html?commission=<?php echo $evt['code_commission']; ?>" title="">

	<!-- picto (retiré) -->
	<div class="picto">
		<?php /*
        <img src="<?php echo comPicto($evt['comm_evt'], 'light');?>" alt="" title="" class="picto-light" />
        <img src="<?php echo comPicto($evt['comm_evt'], 'dark');?>" alt="" title="" class="picto-dark" />
        */ ?>
	</div>

	<div class="droite">
		<!-- temoin de validité des places libres. Ajouter class ok / full -->
        <span style="padding: 10px 10px 5px 5px;float:left;">
            <span class="temoin-places-dispos"></span>
        </span>

		<!-- titre -->
		<h2><?php if ($evt['cancelled_evt']) {
		    echo ' <span style="padding:1px 3px ; color:red; font-size:11px;  font-family:Arial">ANNULÉE - </span> ';
		} echo html_utf8($evt['titre_evt'] . ($evt['jourN'] ? ' [jour ' . $evt['jourN'] . ']' : '')); ?></h2>

	</div>
	<br style="clear:both" />

</a>
