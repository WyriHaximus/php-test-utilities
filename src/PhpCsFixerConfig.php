<?php declare(strict_types=1);

namespace ApiClients\Tools\TestUtilities;

use PhpCsFixer\Config;

final class PhpCsFixerConfig
{
    /**
     * @return Config
     */
    public static function create(): Config
    {
        return Config::create()
            ->setRules([
                '@PSR2' => true,
                'ordered_imports' => true,
                'ordered_class_elements' => true,
                'single_blank_line_before_namespace' => true,
                'method_separation' => true,
                'declare_strict_types' => true,
                'strict_param' => true,
                'single_class_element_per_statement' => true,
                'no_extra_consecutive_blank_lines' => true,
                'trailing_comma_in_multiline_array' => true,
                'single_quote' => true,
                'no_whitespace_in_blank_line' => true,
                'no_whitespace_before_comma_in_array' => true,
                'no_unused_imports' => true,
                'no_short_bool_cast' => true,
                'no_leading_import_slash' => true,
                'new_with_braces' => true,
                'blank_line_before_return' => true,
                'phpdoc_align' => true,
                'phpdoc_no_empty_return' => true,
                'phpdoc_order' => true,
                'phpdoc_add_missing_param_annotation' => true,
                'phpdoc_scalar' => true,
                'phpdoc_summary' => true,
                'return_type_declaration' => true,
                'array_syntax' => ['syntax' => 'short'],
            ])
        ;
    }
}
