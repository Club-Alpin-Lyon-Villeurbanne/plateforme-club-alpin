<!-- MAIN -->
<div id="main" role="main" class="bigoo" style="">
	
	<!-- partie gauche -->
	<div id="left1">
		<div class="main-type">
			<?php
			inclure($p1, 'vide');
			
			// liste des commissions visibles par ordre alphabétique
			ksort($comTab);
			
			// la requete se fait ds la boucle
			foreach($comTab as $code=>$data){
				$dejaVus=array(); // IDs des users déja mis en responsable dans cette commsision (evite les doublons pour qqn à la fois resp. de comm' et encadrant...)
				
				echo '<h2><a id="'.$data['code_commission'].'">&gt; '.html_utf8($data['title_commission']).'</a></h2>';
				$req=" SELECT
						id_user, civ_user, firstname_user, lastname_user, nickname_user, tel_user, tel2_user, email_user
						, title_usertype
					FROM
						caf_user
						, caf_usertype
						, caf_user_attr
					WHERE
						(
							code_usertype LIKE 'responsable-commission' 
							|| code_usertype LIKE 'encadrant' 
							|| code_usertype LIKE 'coencadrant'  
							|| code_usertype LIKE 'benevole'  
						) 
					AND usertype_user_attr = id_usertype
					AND user_user_attr = id_user
					AND params_user_attr LIKE 'commission:".$code."'
					ORDER BY code_usertype DESC, lastname_user ASC
					";
				$result=$mysqli->query($req);
				
				echo '<table class="big-lines-table"><tbody>';
				while($row=$result->fetch_assoc()){
					if(!in_array($row['id_user'], $dejaVus))
						echo '<tr>
								<td style="text-align:center; width:60px;"><img src="'.userImg($row['id_user'], 'pic').'" alt="" title="" style="max-height:40px; max-width:60px;" /></td>
								<td>'.userlink($row['id_user'], $row['nickname_user']).'</td>
								<td>'.$row['title_usertype'].'</td>
							</tr>';
					$dejaVus[] = $row['id_user'];
				}
				echo '</tbody></table><hr /><br />';
			}
			$mysqli->close;
			?>
		</div>
	</div>

	<!-- partie droite -->
	<?php
	include INCLUDES.'right-type-agenda.php';
	?>
	
	<br style="clear:both" />

</div>
