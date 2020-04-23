<?php


namespace diazoxide\wp\lib\option\fields;


use diazoxide\helpers\HTML;

class Input extends Field
{

    public $type;

    public $placeholder;


    protected function template(): string
    {
        $this->attrs = array_merge(
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

        if ($this->type === 'textarea') {
            unset($this->attrs['type']);
            return HTML::tag('textarea', $this->value, $this->attrs);
        }

        return HTML::tagOpen(
            'input',
            $this->attrs
        );
    }
}