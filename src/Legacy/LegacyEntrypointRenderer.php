<?php

namespace App\Legacy;

use Pentatrion\ViteBundle\Service\EntrypointRenderer;

/**
 * @phpstan-type ViteEntryScriptTagsOptions array{
 *  absolute_url?: bool,
 *  attr?: array<string, bool|string|null>,
 *  dependency?: "react"|null
 * }
 * @phpstan-type ViteEntryLinkTagsOptions array{
 *  absolute_url?: bool,
 *  attr?: array<string, bool|string|null>,
 *  preloadDynamicImports?: bool
 * }
 */
class LegacyEntrypointRenderer
{
    public function __construct(private EntrypointRenderer $entrypointRenderer)
    {
    }

    /**
     * @param ViteEntryScriptTagsOptions $options
     */
    public function renderViteScriptTags(string $entryName, array $options = [], ?string $configName = null): string
    {
        return $this->entrypointRenderer->renderScripts($entryName, $options, $configName);
    }

    /**
     * @param ViteEntryLinkTagsOptions $options
     */
    public function renderViteLinkTags(string $entryName, array $options = [], ?string $configName = null): string
    {
        return $this->entrypointRenderer->renderLinks($entryName, $options, $configName);
    }

    public function renderViteLinkRef(string $entryName): string
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
