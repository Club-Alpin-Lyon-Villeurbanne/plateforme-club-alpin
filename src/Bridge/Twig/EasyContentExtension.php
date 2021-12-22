<?php

namespace App\Bridge\Twig;

use App\Repository\ContentHtmlRepository;
use App\UserRights;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EasyContentExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $locator;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    public static function getSubscribedServices()
    {
        return [
            AuthorizationCheckerInterface::class,
            ContentHtmlRepository::class,
            UserRights::class,
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('easy_include', [$this, 'getEasyInclude'], ['is_safe' => ['html']]),
            new TwigFunction('allowed', [$this, 'isAllowed']),
        ];
    }

    public function isAllowed($code_userright, $param = '')
    {
        return $this->locator->get(UserRights::class)->allowed($code_userright, $param);
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

        if ($this->locator->get(AuthorizationCheckerInterface::class)->isGranted('ROLE_ADMIN')) {
            // fancybox
            $ret .= '<div id="'.$elt.'" class="contenuEditable '.$style.'">'.
                '<div class="editHtmlTools" style="text-align:left;">'.
                    '<a href="editElt.php?p='.$elt.'&amp;class='.$style.'" title="Modifier l\'élément '.$elt.'" class="edit fancyframeadmin" style="color:white; font-weight:100; padding:2px 3px 2px 1px; font-size:11px; font-family:Arial;">'.
                        '<img src="/img/base/page_edit.png" id="imgEdit'.$elt.'" alt="EDIT" title="Modifier l\'élément '.$elt.'" />'.
                        'Modifier'.
                    '</a>';

            if ($editVis) {
                $ret .= '<a href="javascript:void(0)" onclick="window.document.majVisBlock(this, \''.$elt.'\')" rel="'.($content ? $content->getVis() : '').'" title="Activer / Masquer ce bloc de contenu" class="edit" style="color:white; font-weight:100; padding:2px 3px 2px 1px; font-size:11px; font-family:Arial; ">
                            <img src="/img/base/page_white_key.png" alt="VIS" title="Activer / Masquer ce bloc de contenu" />Visibilité</a>';
            }

            $ret .= '</div>';
        } else {
            $ret .= '<div id="'.$elt.'" class="'.$style.'">';
        }

        if ($content) {
            $ret .= $content->getContenu();
        } else {
            if ($this->locator->get(AuthorizationCheckerInterface::class)->isGranted('ROLE_ADMIN')) {
                $ret .= '<div class="blocdesactive"><img src="/img/base/bullet_key.png" alt="" title="" /> Bloc de contenu désactivé</div>';
            }
        }

        if (!$content) {
            $ret .= '&nbsp;';
        }

        $ret .= '</div>';

        return $ret;
    }
}
