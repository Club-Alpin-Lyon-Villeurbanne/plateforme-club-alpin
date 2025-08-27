<?php

use App\Legacy\LegacyContainer;
use App\Repository\UserRepository;
use App\Service\EmailMarketingSyncService;

$id_user = null;

if ($p2) {
    $tab = explode('-', $p2);
    $cookietoken_user = $tab[0];
    $id_user = (int) $tab[1];

    // validation user
    if ($id_user) {
        $cookietoken_user = LegacyContainer::get('legacy_mysqli_handler')->escapeString($cookietoken_user);

        $req = "UPDATE caf_user SET valid_user=1 WHERE  `id_user` = $id_user AND cookietoken_user LIKE '$cookietoken_user' LIMIT 1";
        if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
            $errTab[] = 'Erreur de requete';
        } else {
            if (!LegacyContainer::get('legacy_mysqli_handler')->affectedRows()) {
                $errTab[] = 'Activation impossible : ce compte est introuvable, ou a déjà été validé.';
            } else {
                $req = "UPDATE caf_user c1
                    JOIN caf_user c2 ON c1.cafnum_parent_user = c2.cafnum_user
                    SET	c1.email_user=c2.email_user, c1.valid_user=1
                    WHERE c2.id_user=$id_user AND c1.valid_user=0 AND (c1.email_user IS NULL OR c1.email_user='')";

                // Synchroniser l'utilisateur avec les services de marketing après activation
                try {
                    // Récupérer les données utilisateur directement depuis la base legacy
                    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT firstname_user, lastname_user, email_user FROM caf_user WHERE id_user = ?');
                    $stmt->bind_param('i', $id_user);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $userData = $result->fetch_assoc();
                    $stmt->close();

                    if ($userData && $userData['email_user']) {
                        $emailMarketingService = LegacyContainer::get(App\Service\EmailMarketingSyncService::class);
                        
                        // Créer un objet User temporaire avec les données disponibles
                        $tempUser = new App\Entity\User();
                        $tempUser->setFirstname($userData['firstname_user']);
                        $tempUser->setLastname($userData['lastname_user']);
                        $tempUser->setEmail($userData['email_user']);
                        
                        $emailMarketingService->syncUsers($tempUser);
                    }
                } catch (Exception $e) {
                    // Log l'erreur mais ne pas bloquer l'activation
                    $logger = LegacyContainer::get('logger');
                    $logger->error('Failed to sync user with email marketing services: ' . $e->getMessage());
                }
            }
        }
    } else {
        $errTab[] = 'Erreur de données (id)';
    }
} else {
    $errTab[] = 'Erreur de données (datas)';
}
