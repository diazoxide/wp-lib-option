<?php

namespace diazoxide\wp\lib\option\fields;

use diazoxide\helpers\HTML;
use diazoxide\wp\lib\option\interfaces\Option;

class Boolean extends Field
{
    private const MASK_BOOL_TRUE = Option::MASK_BOOL_TRUE;
    private const MASK_BOOL_FALSE = Option::MASK_BOOL_FALSE;

    public $markup;

    protected function template(): string
    {
        $disabled_str = $this->disabled ? 'disabled' : '';
        $required_str = $this->required ? 'required' : '';
        $readonly_str = $this->readonly ? 'readonly' : '';

        $html = '';
        $html .= HTML::tagOpen(
            'input',
            [
                'value' => self::MASK_BOOL_FALSE,
                'type' => 'hidden',
                'name' => $this->name,
                $readonly_str,
                $disabled_str,
                $required_str
            ]
        );

        $html .= HTML::tagOpen(
            'input',
            array_merge(
                $this->attrs,
                [
                    'value' => self::MASK_BOOL_TRUE,
                    'type' => 'checkbox',
                    'name' => $this->name,
                    'data' => $this->data,
                    $this->value ? 'checked' : '',
                    $readonly_str,
                    $disabled_str,
                    $required_str
                ]
            )
        );

        return $html;
    }
}
