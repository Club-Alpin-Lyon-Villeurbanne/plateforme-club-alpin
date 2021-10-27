<?php

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * Easy set variables
     */

    /* Array of database columns which should be read and sent back to DataTables. Use a space where
     * you want to insert a non-database field (for example a counter or static image)
     */
    $aColumns = ['id', 'firstname', 'surname', 'zip', 'country'];

    /* Indexed column (used for fast and accurate table cardinality) */
    $sIndexColumn = 'id';

    /* DB table to use */
    $sTable = 'massive';

    /* Database connection information */
    $gaSql['user'] = '';
    $gaSql['password'] = '';
    $gaSql['db'] = '';
    $gaSql['server'] = 'localhost';

    /* REMOVE THIS LINE (it just includes my SQL connection user/pass) */
    include $_SERVER['DOCUMENT_ROOT'].'/datatables/mysql.php';

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * If you just want to use the basic configuration for DataTables with PHP server-side, there is
     * no need to edit below this line
     */

    /*
     * MySQL connection
     */
    $gaSql['link'] = mysql_pconnect($gaSql['server'], $gaSql['user'], $gaSql['password']) ||
        exit('Could not open connection to server');

    mysql_select_db($gaSql['db'], $gaSql['link']) ||
        exit('Could not select database '.$gaSql['db']);

    /*
     * Paging
     */
    $sLimit = '';
    if (isset($_GET['iDisplayStart']) && '-1' != $_GET['iDisplayLength']) {
        $sLimit = 'LIMIT '.$mysqli->real_escape_string($_GET['iDisplayStart']).', '.
            $mysqli->real_escape_string($_GET['iDisplayLength']);
    }

    /*
     * Ordering
     */
    $sOrder = '';
    if (isset($_GET['iSortCol_0'])) {
        $sOrder = 'ORDER BY  ';
        for ($i = 0; $i < (int) ($_GET['iSortingCols']); ++$i) {
            if ('true' == $_GET['bSortable_'.(int) ($_GET['iSortCol_'.$i])]) {
                $sOrder .= $aColumns[(int) ($_GET['iSortCol_'.$i])].'
				 	'.$mysqli->real_escape_string($_GET['sSortDir_'.$i]).', ';
            }
        }

        $sOrder = substr_replace($sOrder, '', -2);
        if ('ORDER BY' == $sOrder) {
            $sOrder = '';
        }
    }

    /*
     * Filtering
     * NOTE this does not match the built-in DataTables filtering which does it
     * word by word on any field. It's possible to do here, but concerned about efficiency
     * on very large tables, and MySQL's regex functionality is very limited
     */
    $sWhere = '';
    if (isset($_GET['sSearch']) && '' != $_GET['sSearch']) {
        $sWhere = 'WHERE (';
        for ($i = 0; $i < count($aColumns); ++$i) {
            $sWhere .= $aColumns[$i]." LIKE '%".$mysqli->real_escape_string($_GET['sSearch'])."%' OR ";
        }
        $sWhere = substr_replace($sWhere, '', -3);
        $sWhere .= ')';
    }

    /* Individual column filtering */
    for ($i = 0; $i < count($aColumns); ++$i) {
        if (isset($_GET['bSearchable_'.$i]) && 'true' == $_GET['bSearchable_'.$i] && '' != $_GET['sSearch_'.$i]) {
            if ('' == $sWhere) {
                $sWhere = 'WHERE ';
            } else {
                $sWhere .= ' AND ';
            }
            $sWhere .= $aColumns[$i]." LIKE '%".$mysqli->real_escape_string($_GET['sSearch_'.$i])."%' ";
        }
    }

    /*
     * SQL queries
     * Get data to display
     */
    $sQuery = '
		SELECT SQL_CALC_FOUND_ROWS '.str_replace(' , ', ' ', implode(', ', $aColumns))."
		FROM   $sTable
		$sWhere
		$sOrder
		$sLimit
	";
    if (!$rResult = $mysqli->query($sQuery, $gaSql['link'])) {
        exit($mysqli->error);
    }

    /* Data set length after filtering */
    $sQuery = '
		SELECT FOUND_ROWS()
	';
    if (!$rResultFilterTotal = $mysqli->query($sQuery, $gaSql['link'])) {
        exit($mysqli->error);
    }
    $aResultFilterTotal = $rResultFilterTotal->fetch_array(\MYSQLI_ASSOC);
    $iFilteredTotal = $aResultFilterTotal[0];

    /* Total data set length */
    $sQuery = '
		SELECT COUNT('.$sIndexColumn.")
		FROM   $sTable
	";
    if (!$rResultTotal = $mysqli->query($sQuery, $gaSql['link'])) {
        exit($mysqli->error);
    }
    $aResultTotal = $rResultTotal->fetch_array(\MYSQLI_ASSOC);
    $iTotal = $aResultTotal[0];

    /*
     * Output
     */
    $output = [
        'sEcho' => (int) ($_GET['sEcho']),
        'iTotalRecords' => $iTotal,
        'iTotalDisplayRecords' => $iFilteredTotal,
        'aaData' => [],
    ];

    while ($aRow = mysql_fetch_array($rResult)) {
        $row = [];
        for ($i = 0; $i < count($aColumns); ++$i) {
            if ('version' == $aColumns[$i]) {
                /* Special output formatting for 'version' column */
                $row[] = ('0' == $aRow[$aColumns[$i]]) ? '-' : $aRow[$aColumns[$i]];
            } elseif (' ' != $aColumns[$i]) {
                /* General output */
                $row[] = $aRow[$aColumns[$i]];
            }
        }
        $output['aaData'][] = $row;
    }

    echo json_encode($output);
