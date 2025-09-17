<?php

use App\Entity\EventParticipation;

echo '<a class="agenda-evt-debut" target="_top" href="/sortie/' . html_utf8($evt['code_evt']) . '-' . (int) $evt['id_evt'] . '.html?commission=' . $evt['code_commission'];
if (allowed('evt_validate') && isset($evt['status_evt']) && 1 != $evt['status_evt']) {
    echo '&forceshow=true';
}
echo '" title="">';
?>

	<!-- picto -->
	<div class="picto">
		<img src="<?php echo comPicto($evt['commission_evt'] ?? '', 'light'); ?>" alt="" title="" class="picto-light" />
		<img src="<?php echo comPicto($evt['commission_evt'] ?? '', 'dark'); ?>" alt="" title="" class="picto-dark" />
	</div>

	<div class="droite">

		<!-- temoin de validité des places libres. Ajouter class ok / full -->
        <span title="<?php echo $evt['temoin-title'] ?? ''; ?>" style="padding: 10px 10px 5px 5px;float:left;">
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
		<h2 class="tw-flex tw-items-center tw-gap-2">
			<?php
            if (isset($evt['cancelled_evt']) && $evt['cancelled_evt']) {
                echo ' <span style="padding:1px 3px; color:red; font-family:Arial">ANNULÉE - </span>';
            }
echo html_utf8($evt['titre_evt'] . (isset($evt['jourN']) && $evt['jourN'] ? ' [jour ' . $evt['jourN'] . ']' : ''));
if (isset($evt['groupe']) && is_array($evt['groupe'])) {
    echo ' <small>(' . html_utf8($evt['groupe']['nom']) . ')</small>';
}

if (is_array($evt) && array_key_exists('status_evt_join', $evt) && null !== $evt['status_evt_join']) {
    // rôle de l'user dans cette sortie
    if (isset($evt['role_evt_join']) && $evt['role_evt_join'] && in_array($evt['role_evt_join'], [EventParticipation::ROLE_ENCADRANT, EventParticipation::ROLE_COENCADRANT, EventParticipation::ROLE_STAGIAIRE], true)) {
        $str = '<span class="tw-inline-flex tw-items-center tw-text-right tw-gap-1 tw-ml-auto tw-text-xs tw-font-medium tw-text-gray-600 tw-whitespace-nowrap">';
        if (getUser()->getId() == $evt['user_evt']) {
            $str .= '✍️';
        }
        $str .= html_utf8($evt['role_evt_join']) . '</span>';
        echo $str;
        unset($str);
    } elseif (EventParticipation::STATUS_REFUSE == $evt['status_evt_join']) {
        echo '<span class="tw-inline-flex tw-items-center tw-text-right tw-gap-1 tw-ml-auto tw-text-xs tw-font-medium tw-text-red-600 tw-whitespace-nowrap">
            <svg class="tw-w-3 tw-h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            Refusé
        </span>';
    } elseif (EventParticipation::STATUS_VALIDE == $evt['status_evt_join']) {
        echo '<span class="tw-inline-flex tw-items-center tw-text-right tw-gap-1 tw-ml-auto tw-text-xs tw-font-medium tw-text-green-600 tw-whitespace-nowrap">
            <svg class="tw-w-3 tw-h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Accepté
        </span>';
    } elseif (EventParticipation::STATUS_ABSENT == $evt['status_evt_join']) {
        echo '<span class="tw-inline-flex tw-items-center tw-text-right tw-gap-1 tw-ml-auto tw-text-xs tw-font-medium tw-text-gray-600 tw-whitespace-nowrap">
            <svg class="tw-w-3 tw-h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Absent
        </span>';
    } else {
        echo '<span class="tw-inline-flex tw-items-center tw-text-right tw-gap-1 tw-ml-auto tw-text-xs tw-font-medium tw-text-orange-600 tw-whitespace-nowrap">
            <svg class="tw-w-3 tw-h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            En attente
        </span>';
    }
}
?>
		</h2>

		<!-- infos -->
		<p>
			<?php
echo ''
    // commission
    . '<b>' . html_utf8($evt['title_commission']) . '</b>'
;
?>
		</p>
	</div>
	<br style="clear:both" />

</a>
