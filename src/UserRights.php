<?php

namespace App;

use App\Entity\CafUser;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserRights
{
    private RequestStack $requestStack;
    private TokenStorageInterface $tokenStorage;
    private Connection $connection;
    private $userAllowedToCache;

    public function __construct(RequestStack $requestStack, TokenStorageInterface $tokenStorage, Connection $connection)
    {
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
        $this->connection = $connection;
    }

    public function isAdmin()
    {
        $request = $this->requestStack->getMainRequest();

        if (!$request || !$request->hasSession()) {
            return false;
        }

        return $request->getSession()->get('admin_caf', false);
    }

    public function allowed($code_userright, $param = '')
    {
        $userAllowedTo = $this->loadRights();

        if (!isset($userAllowedTo[$code_userright])) {
            return false;
        }

        if ('true' === $userAllowedTo[$code_userright]) {
            return true;
        }
        if (!$param) {
            return true;
        }
        foreach (explode('|', $userAllowedTo[$code_userright]) as $tmpParam) {
            if ($param === $tmpParam) {
                return true;
            }
            if (\array_slice(explode(':', $tmpParam), -1)[0] === $param) {
                return true;
            }
        }

        return false;
    }

    public function getAllCommissionCodes()
    {
        $sql = 'SELECT code_commission FROM caf_commission';

        $result = $this->connection->prepare($sql)->executeQuery()->fetchAllAssociative();
        $ret = [];

        foreach ($result as $row) {
            $ret[] = $row['code_commission'];
        }

        return $ret;
    }

    public function getCommissionListForRight($right)
    {
        $allowed = $this->loadRights()[$right] ?? null;

        if (!$allowed) {
            return [];
        }

        if ('true' === $allowed) {
            return $this->getAllCommissionCodes();
        }

        $commissions = [];

        foreach (explode('|', $allowed) as $a) {
            $commissions[] = \array_slice(explode(':', $a), -1)[0];
        }

        return $commissions;
    }

    public function loadRights()
    {
        if (null !== $this->userAllowedToCache) {
            return $this->userAllowedToCache;
        }

        $userAllowedTo = [];
        $user = $this->tokenStorage->getToken() ? $this->tokenStorage->getToken()->getUser() : null;

        if (!$user instanceof CafUser || $user->getDoitRenouvelerUser()) {
            $sql = 'SELECT DISTINCT code_userright '
                .'FROM caf_userright, caf_usertype_attr, caf_usertype ' // des droits au type
                ."WHERE code_usertype='visiteur' " // type visiteur
                .'AND id_usertype = type_usertype_attr ' // du type visiteur à ses attributions
                .'AND id_userright = right_usertype_attr ' // de ses attributions a ses droits
                .'LIMIT 500 '
            ;

            $result = $this->connection->prepare($sql)->executeQuery()->fetchAllAssociative();

            // ajout du droit au tableau global
            // sans paramètre, la valeur est une string 'true'
            foreach ($result as $row) {
                // les droits visteurs sont tous à true, et ne dependent jamais de parametres
                $val = 'true';
                $userAllowedTo[$row['code_userright']] = $val;

                if ($this->isAdmin()) {
                    $userAllowedTo[$row['code_userright']] = 'true';
                }
            }

            return $userAllowedTo;
        }

        $userAllowedTo = ['default' => '1']; // minimum une valeur

        $sql = 'SELECT DISTINCT code_userright, params_user_attr, limited_to_comm_usertype ' // on veut le code, et les paramètres de chaque droit, et savoir si ce droit est limité à une commission ou non
            .'FROM caf_userright, caf_usertype_attr, caf_usertype, caf_user_attr ' // dans la liste des droits > attr_droit_type > type > attr_type_user
            .'WHERE user_user_attr = :user ' // de user à user_attr
            .'AND usertype_user_attr=id_usertype ' // de user_attr à usertype
            .'AND id_usertype=type_usertype_attr ' // de usertype à usertype_attr
            .'AND right_usertype_attr=id_userright ' // de usertype_attr à userright
            .'ORDER BY params_user_attr ASC, code_userright ASC, limited_to_comm_usertype ASC LIMIT 0 , 500 ' // order by params permet d'optimiser la taille de la var globale. Si, si promis (14 lignes plus loin) !
        ;

        $result = $this->connection->prepare($sql)->executeQuery(['user' => $user->getIdUser()])->fetchAllAssociative();

        // ajout du droit, avec ses paramètres, au tableau global des droits de cet user
        // sans paramètre, la valeur est une string 'true'
        // Il est possible que le même droit prenne plusieurs paramètres (ex : vous avez le droit d'écrire un article dans
        // deux commissions auquel cas, ils sont concaténés via le caractère |
        foreach ($result as $row) {
            if ($row['params_user_attr'] && $row['limited_to_comm_usertype']) {
                $val = $row['params_user_attr'];
            } else {
                $val = 'true';
            }

            // si la valeur est true, pas besoin d'ajouter des parametres par la suite car true = "ok pour tout sans params"
            if ('true' == $val) {
                $userAllowedTo[$row['code_userright']] = $val;
            } elseif ('true' !== ($userAllowedTo[$row['code_userright']] ?? null)) {
                // écriture, ou concaténation des paramètres existant
                $userAllowedTo[$row['code_userright']] = (isset($userAllowedTo[$row['code_userright']]) ? $userAllowedTo[$row['code_userright']].'|' : '').$val;
            }

            if ($this->isAdmin()) {
                $userAllowedTo[$row['code_userright']] = 'true';
            }
        }

        // Tous les utilisateurs connectés non salariés ont le statut "adhérent"
        if (!$user->hasAttribute('Salarié')) {
            $sql = 'SELECT DISTINCT code_userright, limited_to_comm_usertype '
                .'FROM caf_userright, caf_usertype_attr, caf_usertype '
                ."WHERE code_usertype LIKE 'adherent' " // usertype adherent
                .'AND id_usertype=type_usertype_attr '
                .'AND right_usertype_attr=id_userright '
                .'ORDER BY  code_userright ASC, limited_to_comm_usertype ASC LIMIT 0 , 500 '
            ;

            $result = $this->connection->prepare($sql)->executeQuery()->fetchAllAssociative();

            // ajout du droit, avec ses paramètres, au tableau global des droits de cet user
            // sans paramètre, la valeur est une string 'true'
            // Il est possible que le même droit prenne plusieurs paramètres (ex : vous avez le droit d'écrire un article dans
            // deux commissions auquel cas, ils sont concaténés via le caractère
            foreach ($result as $row) {
                $userAllowedTo[$row['code_userright']] = 'true';
            }
        }

        return $this->userAllowedToCache = $userAllowedTo;
    }
}
