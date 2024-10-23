<?php

use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;

if (($currentPage['admin_page'] && !isGranted(SecurityConstants::ROLE_ADMIN)) || ($currentPage['superadmin_page'])) {
    echo 'Vous n\'êtes pas autorisé à accéder à cette page. Pour toute question, rapprochez-vous du service informatique de votre club. ou vos droits ne sont pas assez élevés pour accéder à cette page';
} else {
    $req = 'SELECT * FROM  `caf_log_admin` ORDER BY date_log_admin DESC LIMIT 0 , 500';
    $handleTab = [];
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $handleTab[] = $handle;
    } ?>
	<h2>Log admin</h2>

	<table class="dataTable">
		<thead>
			<tr>
				<th>Code</th>
				<th>Description</th>
				<th>Date</th>
			</tr>
		</thead>
		<tbody>
			<?php
            foreach ($handleTab as $item) {
                if (preg_match('#^login-superadmin#', $item['code_log_admin'])) {
                    $img = 'user_suit.png';
                } elseif (preg_match('#^login-success-#', $item['code_log_admin'])) {
                    $img = 'user.png';
                } elseif (preg_match('#^page-delete#', $item['code_log_admin'])) {
                    $img = 'page_delete.png';
                } elseif (preg_match('#^page-create#', $item['code_log_admin'])) {
                    $img = 'page_add.png';
                }
                // elseif(preg_match("#^edit-html#", $item['code_log_admin'])) $img='layout_edit.png';
                elseif (preg_match('#^edit-html#', $item['code_log_admin'])) {
                    $img = 'page_white_edit.png';
                } elseif (preg_match('#^user_attr_add#', $item['code_log_admin'])) {
                    $img = 'user_star.png';
                } elseif (preg_match('#^user_attr_del#', $item['code_log_admin'])) {
                    $img = 'user_star.png';
                } else {
                    $img = 'report.png';
                }

                echo '
				<tr>
					<td><img src="/img/base/' . $img . '" alt="" title="" style="vertical-align:middle" /> ' . html_utf8($item['code_log_admin']) . '</td>
					<td>' . $item['desc_log_admin'] . '</td>
					<td><span style="display:none">' . $item['date_log_admin'] . '</span>' . date('d/m/y H:i', $item['date_log_admin']) . '</td>
				</tr>';
            } ?>
		</tbody>
	</table>


	<!-- JS -->
	<script type="text/javascript">
	$().ready(function(){
		$('.dataTable').dataTable({
			"aaSorting": [[2,'desc'], [0,'asc']]
		});
	});
	</script>



	<?php
}
