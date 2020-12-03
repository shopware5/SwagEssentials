<?php

$finder = PhpCsFixer\Finder::create()
    ->filter(function(SplFileInfo $file) {
        $blacklistedFiles = [
            'acl-config.php',
            'test_constants.php'
        ];

        return !in_array($file->getFilename(), $blacklistedFiles, true);
    })
    ->in(__DIR__ . '/CacheMultiplexer')
    ->in(__DIR__ . '/Caching')
    ->in(__DIR__ . '/Common')
    ->in(__DIR__ . '/PrimaryReplica')
    ->in(__DIR__ . '/Redis')
    ->in(__DIR__ . '/tests')
;

return PhpCsFixer\Config::create()
   ->setRules([
       '@PSR2' => true,
       'single_blank_line_before_namespace' => true,
       'function_typehint_space' => true,
       'declare_strict_types' => true,
       'function_declaration' => true,
       'no_empty_phpdoc' => true,
       'no_empty_comment' => true,
       'no_useless_return' => true,
       'ordered_imports' => true,
       'phpdoc_add_missing_param_annotation' => [
           'only_untyped' => false,
       ],
       'phpdoc_no_empty_return' => true,
       'phpdoc_order' => true,
       'phpdoc_types' => true,
       'phpdoc_scalar' => true,
       'phpdoc_no_package' => true,
       'trailing_comma_in_multiline_array' => true,
       'single_quote' => true,
       'return_type_declaration' => true,
       'method_separation' => true,
       'no_blank_lines_after_class_opening' => true,
       'phpdoc_return_self_reference' => true,
       'phpdoc_trim' => true,
       'blank_line_before_return' => true,
       'whitespace_after_comma_in_array' => true,
       'is_null' => true,
       'no_spaces_after_function_name' => true,
       'no_trailing_whitespace' => true,
       'no_trailing_whitespace_in_comment' => true,
       'no_blank_lines_after_phpdoc' => true,
       'phpdoc_single_line_var_spacing' => true,
       'phpdoc_var_without_name' => true,
       'cast_spaces' => true,
       'class_definition' => [
           'singleLine' => true,
       ],
       'self_accessor' => false,

       'php_unit_construct' => true,
       'php_unit_test_annotation' => [
           'case' => 'camel',
       ],
       'php_unit_test_case_static_method_calls' => [
           'call_type' => 'static',
       ]
   ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);


