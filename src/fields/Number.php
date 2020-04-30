<?php


namespace diazoxide\wp\lib\option\fields;


use diazoxide\helpers\Variables;
use diazoxide\wp\lib\option\interfaces\Option;
use Exception;

class Number extends Field
{

    public $float = false;

    public $step = 0.01;

    public $min;
    public $max;

    public const MASK_INT = Option::MASK_INT;
    public const MASK_FLOAT = Option::MASK_FLOAT;

    public $placeholder;

    protected function validate(): bool
    {
        $this->attrs['min'] = $this->min;
        $this->attrs['max'] = $this->min;

        return parent::validate(); // TODO: Change the autogenerated stub
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function template(): string
    {
        $html = '';

        $mask = $this->float ? self::MASK_FLOAT : self::MASK_INT;

        $attrs = [
            'type' => 'number',
            'name' => $this->name,
            'value' => $this->value,
            'disabled' => $this->disabled,
            'required' => $this->required,
            'readonly' => $this->readonly,
            'placeholder'=>$this->placeholder,
            'data' => $this->data,
            'attrs' => array_merge(
                $this->attrs,
                [
                    'onchange' => sprintf("this.nextSibling.value='%s'+this.value", $mask)
                ]
            )
        ];

        if ($this->float) {
            $attrs['attrs']['step'] = $this->step;
        }
        if ($this->min) {
            $attrs['attrs']['min'] = $this->min;
        }
        if ($this->max) {
            $attrs['attrs']['max'] = $this->max;
        }

        $html .= (new Input($attrs))->get();

        $attrs['type'] = 'hidden';

        unset(
            $attrs['attrs']['onchange'],
            $attrs['attrs']['step'],
            $attrs['attrs']['min'],
            $attrs['attrs']['max']
        );

        $attrs['value'] = $mask . $attrs['value'];

        $html .= (new Input($attrs))->get();

        return $html;
    }

    public static function unmask(&$value): bool
    {
        if (Variables::compare(Variables::COMPARE_STARTS_WITH, $value, self::MASK_INT)) {
            $value = (int)substr($value, strlen(self::MASK_INT));
            return true;
        }

        if (Variables::compare(Variables::COMPARE_STARTS_WITH, $value, self::MASK_FLOAT)) {
            $value = (float)substr($value, strlen(self::MASK_FLOAT));
            return true;
        }

        return parent::unmask($value); // TODO: Change the autogenerated stub
    }
}