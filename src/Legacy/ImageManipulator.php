<?php

namespace App\Legacy;

use Imagine\Exception\Exception as ImagineException;
use Imagine\Image\Box;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;

class ImageManipulator
{
    /**
     * si $maxWidth != 0 et $maxHeight != 0 : a LARGEUR maxi ET HAUTEUR maxi fixes
     * si $maxHeight != 0 et $maxWidth == 0 : image finale a HAUTEUR maxi fixe (largeur auto)
     * si $maxWidth == 0 et $maxHeight != 0 : image finale a LARGEUR maxi fixe (hauteur auto)
     * Si l'image Source est plus petite que les dimensions indiquees : PAS de redimensionnement.
     */
    public static function resizeImage(int $maxWidth, int $maxHeight, string $source, string $destination, bool $resizeOnlyIfLarger = false)
    {
        try {
            $image = LegacyContainer::get('legacy_imagine')->open($source);
            $size = $image->getSize();

            if (null !== $box = self::computeDimensions($size, $maxWidth, $maxHeight)) {
                if (!$resizeOnlyIfLarger || $size->getWidth() > $box->getWidth() || $size->getHeight() > $box->getHeight()) {
                    $image = $image->resize($box);
                }
            }

            $image
                ->usePalette(new RGB())
                ->strip()
                ->save($destination);

            return true;
        } catch (ImagineException $e) {
            return false;
        }
    }

    public static function cropImage(int $width, int $height, string $source, string $destination)
    {
        $image = LegacyContainer::get('legacy_imagine')->open($source);
        $size = $image->getSize();

        $maxWidth = $maxHeight = 0;
        if ($size->getWidth() / $width > $size->getHeight() / $height) {
            $maxHeight = $height;
        } else {
            $maxWidth = $width;
        }

        if (null !== $box = self::computeDimensions($size, $maxWidth, $maxHeight)) {
            if ($size->getWidth() > $box->getWidth() || $size->getHeight() > $box->getHeight()) {
                $image = $image->resize($box);
            }
        }

        $size = $image->getSize();

        $image = $image->crop(new Point(max(0, $size->getWidth() - $width) / 2, max(0, $size->getHeight() - $height) / 2), new Box($width, $height));

        $image
            ->usePalette(new RGB())
            ->strip()
            ->save($destination);
    }

    public static function getImageSize(string $source)
    {
        $image = LegacyContainer::get('legacy_imagine')->open($source);
        $size = $image->getSize();

        return [$size->getWidth(), $size->getHeight()];
    }

    private static function computeDimensions(Box $size, $maxWidth, $maxHeight): ?Box
    {
        $W = $H = null;

        // A- LARGEUR ET HAUTEUR maxi fixes
        if (0 !== $maxWidth && 0 !== $maxHeight) {
            $ratiox = $size->getWidth() / $maxWidth; // ratio en largeur
            $ratioy = $size->getHeight() / $maxHeight; // ratio en hauteur
            $ratio = max($ratiox, $ratioy); // le plus grand
            $W = round($size->getWidth() / $ratio);
            $H = round($size->getHeight() / $ratio);
        }
        // B- HAUTEUR maxi fixe
        if (0 === $maxWidth && 0 !== $maxHeight) {
            $H = $maxHeight;
            $W = round($H * ($size->getWidth() / $size->getHeight()));
        }
        // -------------------------------------------------------------
        // C- LARGEUR maxi fixe
        if (0 !== $maxWidth && 0 === $maxHeight) {
            $W = $maxWidth;
            $H = round($W * ($size->getHeight() / $size->getWidth()));
        }

        if (null !== $H && null !== $W) {
            return new Box($W, $H);
        }

        return null;
    }
}
