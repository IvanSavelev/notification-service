<?php

$root = dirname(__DIR__);

$finder = PhpCsFixer\Finder::create()
    ->in([
        $root.'/app',
        $root.'/tests',
        $root.'/routes',
        $root.'/config',
        $root.'/database',
    ])
    ->name('*.php')
    ->exclude('vendor')
    ->notPath('bootstrap/cache');

$config = new PhpCsFixer\Config();

return $config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'no_superfluous_phpdoc_tags' => true,
        'phpdoc_trim' => true,
        'general_phpdoc_annotation_remove' => ['annotations' => ['inheritDoc', 'inheritdoc']],
        'no_empty_phpdoc' => true,
        'cast_spaces' => ['space' => 'single'],
        'compact_nullable_typehint' => true,
        'no_unused_imports' => true,
        'no_useless_return' => true,
        'no_blank_lines_after_class_opening' => true,
        'protected_to_private' => true,
        'no_empty_comment' => true,
        'no_extra_blank_lines' => ['tokens' => ['attribute', 'break', 'case', 'continue', 'curly_brace_block', 'default', 'extra', 'parenthesis_brace_block', 'return', 'square_brace_block', 'switch', 'throw', 'use', 'use_trait']],
        'concat_space' => ['spacing' => 'one'],
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'function_typehint_space' => true,
        'void_return' => true,
        'no_blank_lines_after_phpdoc' => true,
        'strict_param' => true,
        'fully_qualified_strict_types' => true,
        'declare_strict_types' => true,
        'no_empty_statement' => true,
        'single_quote' => true,
        'native_type_declaration_casing' => true,
        'blank_line_before_statement' => ['statements' => [
            'continue',
            'declare',
            'return',
            'throw',
            'try',
        ]],
        'declare_equal_normalize' => ['space' => 'none'],
        'global_namespace_import' => true,
        'final_class' => true,
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'constant',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property',
                'property_public',
                'property_protected',
                'property_private',
                'method_abstract',
                'construct',
                'destruct',
                'magic',
                'method',
                'method_public',
                'method_protected',
                'method_private',
            ],
            'sort_algorithm' => 'none',
        ],
    ])
    ->setFinder($finder);
