<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\Environment;

class JavascriptGlobalsExtension extends AbstractExtension
{
    private $variables = [];

    public function __construct(private Environment $twig) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('javascriptGlobals', [$this, 'javascriptGlobals'], ['is_safe' => ['html']]),
        ];
    }

    public function registerGlobal(string $name, $value): void
    {
        $this->variables[$name] = $value;
    }

    public function javascriptGlobals(): void
    {
        $json = json_encode($this->variables);
        echo "<script>window.globals = $json;</script>";
    }
}