<?php

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/src', __DIR__ . '/tests'])
    ->exclude(['var', 'vendor'])
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,

        'declare_strict_types' => true,
        'phpdoc_to_param_type' => true,
        'phpdoc_to_return_type' => true,

        'no_unused_imports' => true,
        'no_superfluous_phpdoc_tags' => true,
        'phpdoc_order' => true,
        'no_useless_return' => true,

        'final_class' => true,
        'ordered_class_elements' => true,
        'native_function_invocation' => false,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_functions' => false,
            'import_constants' => false,
        ],

        'array_syntax' => ['syntax' => 'short'],
        'yoda_style' => [
            'equal' => false,
            'identical' => false,
            'less_and_greater' => false,
        ],
        'binary_operator_spaces' => ['default' => 'single_space'],
        'single_quote' => true,
        'class_attributes_separation' => ['elements' => ['method' => 'one']],
        'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
        'method_chaining_indentation' => true,
        'function_declaration' => [
            'closure_function_spacing' => 'one',
        ],
        'single_line_throw' => false,
        'blank_line_before_statement' => [
            'statements' => ['return', 'throw', 'if', 'foreach', 'for', 'while', 'try'],
        ],
    ])
    ->setFinder($finder);
