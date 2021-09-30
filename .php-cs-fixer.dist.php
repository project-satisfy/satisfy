<?php

/* This config requires PHP-CS-Fixer version ^2.9 */

$finder = (new PhpCsFixer\Finder())
    ->files()
    ->name('*.php')
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
;

return (new PhpCsFixer\Config('satis'))
    ->setCacheFile(__DIR__ . '/var/php_cs.cache')
    ->setRiskyAllowed(true)
    ->setRules([
        // default
        '@PSR2' => true,
        '@Symfony' => true,
        // additionally
        'array_syntax' => ['syntax' => 'short'],
        'concat_space' => false,
        'cast_spaces' => false,
        'no_unused_imports' => false,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'no_superfluous_phpdoc_tags' => true,
        'ordered_imports' => true,
        'phpdoc_align' => false,
        'phpdoc_order' => true,
        'phpdoc_summary' => false,
        'simplified_null_return' => false,
        'ternary_to_null_coalescing' => true,
    ])
    ->setFinder($finder)
;
