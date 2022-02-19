<?php


$finder = PhpCsFixer\Finder::create()
    ->ignoreDotFiles(false)
    ->ignoreVCSIgnored(true)
    ->in(__DIR__)
    ->notPath('vendor')
    ->name('*.php')
;

$config = new PhpCsFixer\Config();

$config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR2' => true,
        'braces' => [
            'position_after_functions_and_oop_constructs' => 'same',
        ],
        'single_import_per_statement' => false,
        'ordered_imports' => true,
    ])
    ->setFinder($finder)
; ;
