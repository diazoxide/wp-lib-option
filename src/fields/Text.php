<?php


namespace diazoxide\wp\lib\option\fields;


class Text extends Input
{

    public $large = false;

    protected function validate(): bool
    {
        $this->type = $this->large ? 'textarea' : 'text';
        return parent::validate();
    }
}