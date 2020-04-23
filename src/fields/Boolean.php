<?php


namespace diazoxide\wp\lib\option\fields;


use diazoxide\helpers\HTML;

class Boolean extends Field
{
    public const MASK_BOOL_TRUE = '{~2~}';
    public const MASK_BOOL_FALSE = '{~3~}';

    public $markup;

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

    /**
     * Check if form field is boolean and return
     * Real boolean value
     *
     * @param $str
     *
     * @return mixed
     */
    public static function unmask(&$str): bool
    {
        if ($str === static::MASK_BOOL_TRUE) {
            $str = true;
            return true;
        }

        if ($str === static::MASK_BOOL_FALSE) {
            $str = false;
            return true;
        }

        return parent::unmask($str);
    }
}