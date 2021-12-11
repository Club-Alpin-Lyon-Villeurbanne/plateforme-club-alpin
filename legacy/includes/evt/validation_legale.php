<?php

use App\Legacy\LegacyContainer;

$MAX_TIMESTAMP_FOR_LEGAL_VALIDATION = LegacyContainer::getParameter('legacy_env_MAX_TIMESTAMP_FOR_LEGAL_VALIDATION');

if ($evt['tsp_evt'] < $MAX_TIMESTAMP_FOR_LEGAL_VALIDATION && $evt['tsp_evt'] > time()) {
    inclure('status-legal-'.(int) ($evt['status_legal_evt']), 'status-legal');
    echo '<br />';

    if (allowed('evt_legal_accept') && 0 == $evt['status_legal_evt'] && 1 == $evt['status_evt']) {
        ?>
        <div class="status-legal noprint">
            <h2>Validation de la sortie :</h2>
            <p>
                Pour valider cette sortie en tant que sortie officielle du CAF,
                ou refuser d'associer cette sortie au <?php echo $p_sitename; ?>,
                cliquez sur un des boutons ci-dessous.
            </p>
            <p>
                <b>Attention !</b> Cette opération est définitive !
            </p>

            <form action="<?php echo $versCettePage; ?>" method="post" class="loading" style="display:inline">
                <input type="hidden" name="operation" value="evt_legal_update" />
                <input type="hidden" name="status_legal_evt" value="2" />
                <input type="hidden" name="id_evt" value="<?php echo $id_evt; ?>" />
                <input type="submit" class="nice2 red" value="Refuser la validation" />
            </form>
            <form action="<?php echo $versCettePage; ?>" method="post" class="loading" style="display:inline">
                <input type="hidden" name="operation" value="evt_legal_update" />
                <input type="hidden" name="status_legal_evt" value="1" />
                <input type="hidden" name="id_evt" value="<?php echo $id_evt; ?>" />
                <input type="submit" class="nice2 green" value="Valider" />
            </form>

            <br />&nbsp;
        </div>
        <br />
        <?php
    }
}
