<?php

namespace App\Twig;

use Pentatrion\ViteBundle\Service\EntrypointRenderer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ViteExtension extends AbstractExtension
{
    public function __construct(private EntrypointRenderer $entrypointRenderer)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('vite_entry_link_ref', [$this, 'getViteEntryLinkRef']),
        ];
    }

    public function getViteEntryLinkRef(string $entryName): string
    {
        $tags = $this->entrypointRenderer->getRenderedTags();
        foreach ($tags as $tag) {
            if ($entryName === $tag->getOrigin()) {
                $attributes = $tag->getAttributes();
                if (\array_key_exists('href', $attributes)) {
                    return $attributes['href'];
                }
            }
        }

        return '';
    }
}
