<?php

use App\Entity\EventParticipation;

?>
<a class="agenda-evt-courant" href="/sortie/<?php echo html_utf8($evt['code_evt']) . '-' . (int) $evt['id_evt']; ?>.html?commission=<?php echo $evt['code_commission']; ?>" title="">

	<!-- picto (retiré) -->
	<div class="picto">
        <img src="<?php echo comPicto($evt['commission_evt'] ?? '', 'dark'); ?>" alt="" title="" class="picto-dark" />
	</div>

	<div class="droite">
		<!-- temoin de validité des places libres. Ajouter class ok / full -->
        <span style="padding: 10px 10px 5px 5px;float:left;">
            <span class="temoin-places-dispos"><?php if (isset($evt['temoin']) && 'full' == $evt['temoin']) {
                echo '🚫';
            } elseif (isset($evt['temoin']) && 'free' == $evt['temoin']) {
                echo '🟢';
            } elseif (isset($evt['temoin']) && 'finished' == $evt['temoin']) {
                echo '⚪';
            } elseif (isset($evt['temoin']) && 'waiting' == $evt['temoin']) {
                echo '⏳';
            } elseif (isset($evt['temoin']) && 'draft' == $evt['temoin']) {
                echo '✍️';
            }?></span>
        </span>

		<!-- titre -->
		<h2 class="tw-flex tw-items-center tw-gap-2"><?php if ($evt['cancelled_evt']) {
		    echo ' <span style="padding:1px 3px ; color:red; font-size:11px;  font-family:Arial">ANNULÉE - </span> ';
		}
echo html_utf8($evt['titre_evt'] . ($evt['jourN'] ? ' [jour ' . $evt['jourN'] . ']' : ''));

if (is_array($evt) && array_key_exists('status_evt_join', $evt) && null !== $evt['status_evt_join']) {
    if (EventParticipation::STATUS_REFUSE == $evt['status_evt_join']) {
        echo '<span class="tw-inline-flex tw-items-center tw-gap-1 tw-ml-auto tw-text-xs tw-font-medium tw-text-red-600 tw-whitespace-nowrap">
            <svg class="tw-w-3 tw-h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            Refusé
        </span>';
    } elseif (EventParticipation::STATUS_VALIDE == $evt['status_evt_join']) {
        echo '<span class="tw-inline-flex tw-items-center tw-gap-1 tw-ml-auto tw-text-xs tw-font-medium tw-text-green-600 tw-whitespace-nowrap">
            <svg class="tw-w-3 tw-h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Accepté
        </span>';
    } elseif (EventParticipation::STATUS_ABSENT == $evt['status_evt_join']) {
        echo '<span class="tw-inline-flex tw-items-center tw-gap-1 tw-ml-auto tw-text-xs tw-font-medium tw-text-gray-600 tw-whitespace-nowrap">
            <svg class="tw-w-3 tw-h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Absent
        </span>';
    } else {
        echo '<span class="tw-inline-flex tw-items-center tw-gap-1 tw-ml-auto tw-text-xs tw-font-medium tw-text-orange-600 tw-whitespace-nowrap">
            <svg class="tw-w-3 tw-h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            En attente
        </span>';
    }
}
?></h2>

	</div>
	<br style="clear:both" />

</a>
