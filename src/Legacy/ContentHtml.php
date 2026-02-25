<?php

namespace App\Legacy;

use App\Repository\ContentHtmlRepository;
use App\Security\SecurityConstants;
use App\UserRights;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class ContentHtml implements ServiceSubscriberInterface
{
    private ContainerInterface $locator;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    public static function getSubscribedServices(): array
    {
        return [
            AuthorizationCheckerInterface::class,
            UserRights::class,
            ContentHtmlRepository::class,
        ];
    }

    public function getEasyInclude($elt, $style = 'vide', $options = [])
    {
        $editVis = true;

        foreach ($options as $key => $val) {
            if ('editVis' == $key) {
                $editVis = $val;
            }
        }

        $content = $this->locator->get(ContentHtmlRepository::class)->findByCodeContent($elt);
        $ret = '';

        if ($this->locator->get(AuthorizationCheckerInterface::class)->isGranted(SecurityConstants::ROLE_CONTENT_MANAGER)) {
            $ret .= '<div id="' . $elt . '" class="contenuEditable ' . $style . '">' .
                '<div class="editHtmlTools" style="text-align:left;">' .
                '<a href="editElt.php?p=' . $elt . '&amp;class=' . $style . '" title="Modifier l\'élément ' . $elt . '" class="edit fancyframeadmin" style="color:white; font-weight:100; padding:2px 3px 2px 1px; font-family:Arial;">' .
                '<img src="/img/base/page_edit.png" id="imgEdit' . $elt . '" alt="EDIT" title="Modifier l\'élément ' . $elt . '" />' .
                'Modifier' .
                '</a>';

            if ($editVis) {
                $ret .= '<a href="javascript:void(0)" onclick="window.document.majVisBlock(this, \'' . $elt . '\')" rel="' . ($content ? $content->getVis() : '') . '" title="Activer / Masquer ce bloc de contenu" class="edit" style="color:white; font-weight:100; padding:2px 3px 2px 1px; font-family:Arial; ">
                            <img src="/img/base/page_white_key.png" alt="VIS" title="Activer / Masquer ce bloc de contenu" />Visibilité</a>';
            }

            $ret .= '</div>';
        } else {
            $ret .= '<div id="' . $elt . '" class="' . $style . '">';
        }

        if ($content) {
            $ret .= self::addHeadingIds($content->getContenu() ?? '');
        } else {
            if ($this->locator->get(AuthorizationCheckerInterface::class)->isGranted(SecurityConstants::ROLE_CONTENT_MANAGER)) {
                $ret .= '<div class="blocdesactive"><img src="/img/base/bullet_key.png" alt="" title="" /> Bloc de contenu désactivé</div>';
            }
        }

        if (!$content) {
            $ret .= '&nbsp;';
        }

        $ret .= '</div>';

        return $ret;
    }

    /**
     * Ajoute automatiquement des attributs id aux headings (h1-h6)
     * pour que les liens avec ancres fonctionnent.
     * Les headings ayant déjà un id explicite sont préservés.
     * En cas de slugs dupliqués, un suffixe numérique est ajouté (ex: "tarifs2").
     */
    public static function addHeadingIds(string $html): string
    {
        $usedIds = [];

        return preg_replace_callback(
            '/<(h[1-6])((?:\s[^>]*)?)>([\s\S]+?)<\/\1>/iu',
            function ($matches) use (&$usedIds) {
                $tag = $matches[1];
                $attrs = $matches[2];
                $content = $matches[3];

                // Ne pas écraser un id existant ((?:^|\s) évite le faux positif sur data-id, aria-id, etc.)
                if (preg_match('/(?:^|\s)id\s*=/i', $attrs)) {
                    return $matches[0];
                }

                $text = strip_tags($content);
                $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $id = self::slugify($text);

                if ('' === $id) {
                    return $matches[0];
                }

                // Dédupliquer les ids
                if (isset($usedIds[$id])) {
                    ++$usedIds[$id];
                    $id .= $usedIds[$id];
                } else {
                    $usedIds[$id] = 1;
                }

                return "<{$tag}{$attrs} id=\"{$id}\">{$content}</{$tag}>";
            },
            $html
        ) ?? $html;
    }

    /**
     * Génère un slug sans séparateur à partir d'un texte :
     * supprime accents, emojis, caractères spéciaux, met en minuscules.
     * Tronqué à 100 caractères max.
     */
    public static function slugify(string $text): string
    {
        if (function_exists('transliterator_transliterate')) {
            $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        } else {
            $text = mb_strtolower($text, 'UTF-8');
            $text = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            $text = strtolower($text);
        }

        $text = preg_replace('/[^a-z0-9]/', '', $text);

        return substr($text, 0, 100);
    }
}
