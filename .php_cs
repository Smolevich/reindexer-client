<?php


$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->notPath('vendor')
    ->name('*.php')
;

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,
        'braces' => [
            'position_after_functions_and_oop_constructs' => 'same',
        ],
        'single_import_per_statement' => false,
        'ordered_imports' => true,
    ])
    ->setFinder($finder)
    ->setUsingCache(true)
;