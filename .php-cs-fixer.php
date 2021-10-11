<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
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

return (new Config())
   ->setRules([
       '@PSR12' => true,
       '@Symfony' => true,

       'single_blank_line_before_namespace' => true,
       'function_typehint_space' => true,
       'declare_strict_types' => true,
       'function_declaration' => true,
       'no_empty_phpdoc' => true,
       'no_empty_comment' => true,
       'no_useless_return' => true,
       'ordered_imports' => true,
       'phpdoc_add_missing_param_annotation' => true,
       'concat_space' => ['spacing' => 'one'],
       'phpdoc_no_empty_return' => true,
       'phpdoc_order' => true,
       'phpdoc_types' => true,
       'phpdoc_scalar' => true,
       'phpdoc_no_package' => true,
       'phpdoc_summary' => false,
       'phpdoc_line_span' => true,
       'trailing_comma_in_multiline' => ['elements' => ['arrays']],
       'single_quote' => true,
       'return_type_declaration' => true,
       'class_attributes_separation' => ['elements' => ['method' => 'one', 'property' => 'one']],
       'no_blank_lines_after_class_opening' => true,
       'phpdoc_return_self_reference' => true,
       'phpdoc_trim' => true,
       'blank_line_before_statement' => true,
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
           'single_line' => true,
       ],
       'self_accessor' => false,

       'php_unit_construct' => true,
       'php_unit_test_case_static_method_calls' => [
           'call_type' => 'static',
       ],
       'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
   ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
