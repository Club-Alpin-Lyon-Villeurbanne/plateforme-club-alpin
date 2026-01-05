<?php

use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;
use App\Helper\HtmlHelper;

if (($currentPage['admin_page'] && !isGranted(SecurityConstants::ROLE_ADMIN)) || $currentPage['superadmin_page']) {
    echo 'Votre session administrateur a expiré ou vos droits ne sont pas assez élevés pour accéder à cette page';
} else {
    $req = 'SELECT * FROM  `caf_log_admin` ORDER BY date_log_admin DESC LIMIT 0 , 5000';
    $handleTab = [];
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $handleTab[] = $handle;
    } ?>
	<h2>Log admin</h2>

	<table class="dataTable">
		<thead>
			<tr>
				<th>Date</th>
				<th>Code</th>
				<th>Description</th>
				<th>@IP</th>
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
					<td><span style="display:none">' . $item['date_log_admin'] . '</span>' . date('d/m/y H:i', $item['date_log_admin']) . '</td>
					<td><img src="/img/base/' . $img . '" alt="" title="" style="vertical-align:middle" /> ' . HtmlHelper::escape($item['code_log_admin']) . '</td>
					<td>' . HtmlHelper::escape($item['desc_log_admin']) . '</td>
					<td>' . HtmlHelper::escape($item['ip_log_admin']) . '</td>
				</tr>';
            } ?>
		</tbody>
	</table>


	<!-- JS -->
	<script type="text/javascript">
	$().ready(function(){
		$('.dataTable').dataTable({
			"iDisplayLength": 100,
			"aaSorting": [[0,'desc'], [2,'asc']]
		});
	});
	</script>



	<?php
}
?>