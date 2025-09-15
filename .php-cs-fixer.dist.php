<?php

$finder = PhpCsFixer\Finder::create()
    ->in(['src', 'tests'])
    ->exclude('var')
    ->exclude('vendor')
    ->exclude('node_modules')
    ->notPath('src/Kernel.php') // Fichier auto-généré Symfony
    ->notName('*.xml')
    ->notName('*.yml')
    ->notName('*.yaml');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@DoctrineAnnotation' => true,
        'trailing_comma_in_multiline' => false, // Désactive l'ajout de virgules en fin d'array
        'native_function_invocation' => false, // Désactive l'ajout de \ devant les fonctions natives
        'native_constant_invocation' => false, // Désactive l'ajout de \ devant les constantes natives
        'no_useless_return' => true,
        'no_useless_else' => true,
        'no_closing_tag' => true,
        'no_superfluous_elseif' => true,
        'explicit_indirect_variable' => true,
        'return_assignment' => true,
        'fopen_flags' => false,
        'strict_param' => true,
        'concat_space' => ['spacing' => 'one'],
        'ternary_operator_spaces' => true,
        'trim_array_spaces' => true,
        'unary_operator_spaces' => true,
        'whitespace_after_comma_in_array' => true,
        'space_after_semicolon' => true,
        'array_indentation' => true,
        'array_syntax' => ['syntax' => 'short'],
        'single_quote' => true,
        'lowercase_cast' => true,
        'no_extra_blank_lines' => [
            'tokens' => [
                'curly_brace_block',
                'extra',
                'throw',
                'use',
            ],
        ],
        'no_whitespace_in_blank_line' => true,
        'no_spaces_around_offset' => true,
    ])
    ->setFinder($finder)
    ->setCacheFile('.php-cs-fixer.cache')
    ->setLineEnding("\n");
