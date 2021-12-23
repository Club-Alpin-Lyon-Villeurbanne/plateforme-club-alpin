<?php

namespace App\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

class DumpContentInlineCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $cacheDir = $container->getParameter('kernel.cache_dir');
        $container->setParameter('content_inline_path', $cacheDir.'/content-inline.php');
        $contentInlinePath = $container->getParameter('kernel.project_dir').'/config/content-inline.yaml';
        $this->exportToPhpFile(Yaml::parseFile($contentInlinePath)['content'], $container->getParameter('content_inline_path'));
    }

    private function exportToPhpFile($data, string $path): void
    {
        file_put_contents($path, sprintf("<?php return %s;\n", var_export($data, true)));
    }
}
