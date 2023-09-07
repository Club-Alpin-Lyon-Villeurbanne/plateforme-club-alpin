<?php

namespace App\Utils;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class NoteDeFrais
{
	public static function getTauxKms($date)
    {
        if( $date > \DateTime::createFromFormat('Y-m-d', '2023-06-01')) {
        	$taux = '0,35';
        } else {
        	$taux = '0,32';
        }

        return $taux;
    }

	public static function getPlafondHebergement($date)
    {
        if( $date > \DateTime::createFromFormat('Y-m-d', '2023-06-01')) {
        	$plafond = '55';
        } else {
        	$plafond = '50';
        }

        return $plafond;
    }

}