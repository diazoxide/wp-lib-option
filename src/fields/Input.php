<?php


namespace diazoxide\wp\lib\option\fields;


use diazoxide\helpers\HTML;

class Input extends Field
{

    public $type;

    public $placeholder;
    
    protected function template(): string
    {
        $attrs = array_merge(
            $this->attrs,
            [
                'placeholder' => $this->placeholder,
                'name' => $this->name,
                'data' => $this->data,
                'type' => $this->type,
                'value' => $this->value,
                $this->disabled ? 'disabled' : '',
                $this->required ? 'required' : '',
                $this->readonly ? 'readonly' : '',
            ]
        );

        $attrs = array_filter($attrs);

        if ($this->type === 'textarea') {
            unset($attrs['type']);
            return HTML::tag('textarea', $this->value, $attrs);
        }

        return HTML::tagOpen(
            'input',
            $attrs
        );
    }
}