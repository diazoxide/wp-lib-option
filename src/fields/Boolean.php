<?php


namespace diazoxide\wp\lib\option\fields;


use diazoxide\helpers\HTML;

class Boolean extends Field
{
    public const MASK_BOOL_TRUE = '{~2~}';
    public const MASK_BOOL_FALSE = '{~3~}';

    protected function template(): string
    {
        $disabled_str = $this->disabled ? 'disabled' : '';

        $required_str = $this->required ? 'required' : '';

        $readonly_str = $this->readonly ? 'readonly' : '';

        $html = '';
        $html .= HTML::tagOpen(
            'input',
            ['value' => self::MASK_BOOL_FALSE, 'type' => 'hidden', 'name' => $this->name]
        );

        $html .= HTML::tagOpen(
            'input',
            $this->attrs + [
                'value' => self::MASK_BOOL_TRUE,
                'type' => 'checkbox',
                'name' => $this->name,
                'data' => $this->data,
                $this->value ? 'checked' : '',
                $readonly_str,
                $disabled_str,
                $required_str
            ]
        );

        return $html;
    }
}