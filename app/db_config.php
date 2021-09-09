<?php

	$configParamsLocal = CONFIG.MON_DOMAINE.DS.basename(__FILE__); {
		if (file_exists($configParamsLocal)) {
			include($configParamsLocal);
		} else {
			$configParams = CONFIG.basename(__FILE__);
			if (file_exists($configParams)) {
				include($configParams);
			} else {
				die("Aucun fichier de configuration \"".CONFIG.MON_DOMAINE.DS.basename(__FILE__)."\" n'a été trouvé");
			}
		}
	}