<?php

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

require __DIR__.'/../app/includes.php';

$log = (isset($log) ? $log : '')."\n accès à ".date('H:i:s');

if (admin()) {
    $devmode = true;
    $errTab = [];
    // console vars
    foreach ($_GET as $key => $value) {
        $log .= "\n".$key.'='.$value;
        ${$key} = $value;
    }

    if (!isset($srcImg)) {
        $errTab[] = 'no srcImg';
    }
    if (!isset($wDest)) {
        $errTab[] = 'no wDest';
    }
    if (!isset($wDestNocrop)) {
        $errTab[] = 'no wDestNocrop';
    }
    if (!isset($hDest)) {
        $errTab[] = 'no hDest';
    }
    if (!isset($hDestNocrop)) {
        $errTab[] = 'no hDestNocrop';
    }
    if (!isset($xDest)) {
        $errTab[] = 'no xDest';
    }
    if (!isset($yDest)) {
        $errTab[] = 'no yDest';
    }

    if (!isset($errTab) || 0 === count($errTab)) {
        // preview, ou finale ?
        $preview = $preview ? true : false;

        $log .= "\n\n Vars ok";
        if (!is_file($srcImg)) {
            $errTab[] = "Image introuvable : $srcImg";
        } else {
            // include APP.'redims.php';
            // utilisable ?
            // $ext=strtolower(array_pop(explode('.', $srcImg)));
            $ext = strtolower(substr(strrchr($srcImg, '.'), 1));
            if ('jpg' == $ext || 'jpeg' == $ext || 'png' == $ext) {
                // dimensions de la source
                $size = getimagesize($srcImg);
                $wSource = $size[0];
                $hSource = $size[1];
                // vars
                $dir = substr($srcImg, 0, strrpos($srcImg, '/') + 1);
                $filename = substr($srcImg, strrpos($srcImg, '/') + 1);
                $log .= "\n\n dir=$dir";
                $log .= "\n\n filename=$filename";

                // resize/crop manuel
                $phpImage = imagecreatetruecolor($wDestNocrop, $hDestNocrop);
                switch ($ext) {
                    case 'jpg':
                    case 'jpeg':
                        $runImage = imagecreatefromjpeg($dir.$filename);
                        break;
                    case 'png':
                        $runImage = imagecreatefrompng($dir.$filename);
                        break;
                }

                if (!$runImage) {
                    throw new BadRequestHttpException('Invalid image provided.');
                }

                // 1:Redimensionnement : l'image "dure" n'est pas au bon format pour le crop
                imagecopyresized($phpImage, $runImage, 0, 0, 0, 0, $wDestNocrop, $hDestNocrop, $wSource, $hSource);

                // 2:Crop, si besoin
                if ($wDestNocrop != $wDestNocrop || $hDestNocrop != $hDest) {
                    $log .= "\n\n CROP";
                    $phpImage2 = imagecreatetruecolor($wDest, $hDest);
                    // imagecopyresized($phpImage2, $runImage, 0, 0, $xDest, $yDest, $wDest, $hDest, $wSource, $hSource);
                    // imagecopyresized($phpImage2, $phpImage, 0, 0, 0, 0, $wDest, $hDest, $wSource, $hSource);
                    // imagecopyresized($phpImage2, $phpImage, 0, 0, 0, 0, $wSource, $hSource, $wDest, $hDest);
                    imagecopyresized($phpImage2, $phpImage, 0, 0, $xDest, $yDest, $wDest, $hDest, $wDest, $hDest);
                    $log .= "\n\n imagecopyresized($phpImage2, $phpImage, 0, 0, $xDest, $yDest, $wDest, $hDest, $wDest, $hDest);";
                    $phpImage = $phpImage2;
                }

                switch ($ext) {
                    case 'jpg':
                    case 'jpeg':
                        imagejpeg($phpImage, $dir.($preview ? 'preview-'.$filename : $filename), 95);
                        break;
                    case 'png':
                        imagepng($phpImage, $dir.($preview ? 'preview-'.$filename : $filename));
                        break;
                }

                $result = [
                    'success' => true,
                    'src' => $dir.($preview ? 'preview-'.$filename : $filename),
                    'width' => $wDest,
                    'height' => $hDest,
                ];

                // destImg : trigger de previex ou final
                if ($destImg) {
                    // pas preview dans version finale
                    if (!copy($dir.$filename, $destImg)) {
                        $errTab[] = "Erreur de copie de $dir $filename à $destImg";
                    }
                }

                echo htmlspecialchars(json_encode($result), \ENT_NOQUOTES);
            }
        }
    }

    if (isset($errTab) && count($errTab) > 0) {
        $log .= "\n\n".implode("\n", $errTab);
    }

    // LOG
    if ($devmode) {
        $log .= " \n \n FIN";
        $fp = fopen('dev.txt', 'w');
        fwrite($fp, $log);
        fclose($fp);
    }
}
