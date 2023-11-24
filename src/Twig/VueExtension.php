<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\Environment;

class VueExtension extends AbstractExtension
{
    public function __construct(private Environment $twig) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('vueComponent', [$this, 'vueComponent'], ['is_safe' => ['html']]),
        ];
    }

    public function vueComponent(string $selector, string $componentName = null, $data = null): string
    {
        $script = $this->twig->render('vuejs/component.html.twig', [
            'selector' => $selector,
            'componentName' => $componentName,
            'data' => json_encode($data),
        ]);
        return $script;
    }
}