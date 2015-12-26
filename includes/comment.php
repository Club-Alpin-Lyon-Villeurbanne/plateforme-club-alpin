<?php
if($comment){
	?>
	<div class="comment">
		<div class="left">
			<img src="<?php echo userImg($comment['user_comment'], 'pic');?>" alt="" title="" />
		</div>
		<div class="right">
			<?php
			// SUPPRIMER CE COMMENTAIRE
			if($comment['user_comment'] == $_SESSION['user']['id_user'] or allowed('comment_delete_any')){
				?>
				<a href="includer.php?p=includes/comment-del.php&amp;id_comment=<?php echo intval($comment['id_comment']);?>" title="Supprimer ce commentaire" style="float:right" class="fancyframe">
					<img src="img/base/delete.png" alt="SUPPR" title="" />
				</a>
				<?php
			}
			?>
			<p>
				<?php
				if($comment['user_comment'] != 0) echo userlink($comment['user_comment'], $comment['nickname_user']); 
				else echo html_utf8($comment['name_comment']);
				?>
				<span class="mini" style="font-size:9px">
					&nbsp;&nbsp;&nbsp;
					le 
					<?php
					echo date('d', $comment['tsp_comment']).' '.mois(date('m', $comment['tsp_comment'])).' '.date('Y', $comment['tsp_comment']);
					?>
				</span>
			</p>
			<p class="cont_comment">	
				<?php
				echo nl2br(html_utf8($comment['cont_comment']));
				?>
			</p>
		</div>
		<br style="clear:both" />
	</div>
	<?php
}
