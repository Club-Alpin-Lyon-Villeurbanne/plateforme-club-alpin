<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__.'/bin',
        __DIR__.'/legacy',
        __DIR__.'/public',
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
;

return (new PhpCsFixer\Config())
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setRules(array(
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@DoctrineAnnotation' => true,
        'no_useless_return' => true,
        'no_useless_else' => true,
        'no_closing_tag' => true,
        'no_superfluous_elseif' => true,
        'explicit_indirect_variable' => true,
        'return_assignment' => true,
        'fopen_flags' => false,
        'strict_param' => true,
    ))
;
