<?php


namespace diazoxide\wp\lib\option\fields;


use diazoxide\wp\lib\option\interfaces\Option;

class EmptyField extends Input
{
    public const MASK_NULL = Option::MASK_NULL;
    public const MASK_ARRAY = Option::MASK_ARRAY;

    public $array = false;

    public function validate(): bool
    {
        $this->type = 'hidden';

        $this->value = $this->array ? static::MASK_ARRAY : static::MASK_NULL;

        return parent::validate();
    }

    /**
     * Check if form field is boolean or array and return
     * Real boolean value
     *
     * @param $str
     *
     * @return mixed
     */
    public static function unmask(&$str):bool
    {
        if ($str === static::MASK_NULL) {
            $str = null;
            return true;
        }

        if ($str === static::MASK_ARRAY) {
            $str = [];
            return true;
        }

        return parent::unmask($str);
    }
}