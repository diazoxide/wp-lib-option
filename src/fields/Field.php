<?php

namespace diazoxide\wp\lib\option\fields;

use diazoxide\helpers\Variables;
use diazoxide\wp\lib\option\Option;
use Exception;
use InvalidArgumentException;
use RuntimeException;

abstract class Field
{
    /**
     * @var mixed
     * */
    public $value;

    /**
     * Main name field
     *
     * @var string
     * */
    public $name;

    /**
     * @var Boolean
     * */
    public $disabled;

    /**
     * @var Boolean
     * */
    public $readonly;

    /**
     * @var Boolean
     * */
    public $required;

    /**
     * @var array
     * */
    public $attrs = [];

    /**
     * @var array
     * */
    public $data = [];

    /**
     * Actions
     * click|chane e.t.c.
     *
     * @var array
     * */
    public $on = [];

    /**
     * @var array
     * */
    public $errors = [];

    public $depends_on = [];

    /**
     * Field constructor.
     * @param array $args
     * @throws Exception
     */
    public function __construct(array $args)
    {
        foreach ($args as $arg => $value) {
            if (property_exists(static::class, $arg)) {
                $this->{$arg} = $value;
            } else {
                throw new InvalidArgumentException("'$arg' is not valid argument for '" . static::class . "'");
            }
        }

        if (!$this->validate()) {
            throw new RuntimeException(json_encode($this->errors));
        }
    }

    protected function requiredFields(): array
    {
        return [];
    }

    protected function validate(): bool
    {
        foreach ($this->requiredFields() as $required_field) {
            if (!isset($this->{$required_field})) {
                $this->errors[] = [
                    'required_field_not_provided',
                    sprintf('Required field `$%s` not provided.', $required_field),
                    $required_field
                ];
                return false;
            }
        }
        return true;
    }

    /**
     * Template for field
     *
     * @return string
     * */
    abstract protected function template(): string;

    public function get(): string
    {
        return $this->template();
    }

    public function print(): void
    {
        echo $this->template();
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public static function unmask(&$value): bool
    {

        if ($value === Option::MASK_BOOL_TRUE) {
            $value = true;
            return true;
        }
        if ($value === Option::MASK_BOOL_FALSE) {
            $value = false;
            return true;
        }
        if ($value === Option::MASK_NULL) {
            $value = null;
            return true;
        }
        if ($value === Option::MASK_ARRAY) {
            $value = [];
            return true;
        }

        if (Variables::compare(Variables::COMPARE_STARTS_WITH, $value, Option::MASK_INT)) {
            $value = (int)substr($value, strlen(Option::MASK_INT));
            return true;
        }
        if (Variables::compare(Variables::COMPARE_STARTS_WITH, $value, Option::MASK_FLOAT)) {
            $value = (float)substr($value, strlen(Option::MASK_FLOAT));
            return true;
        }

        return false;
    }

    /**
     * @param  mixed  $value
     * @param  string  $mask
     *
     * @return bool
     */
    public static function mask(&$value): bool
    {
        if ($value === true) {
            $value = Option::MASK_BOOL_TRUE;
        } elseif ($value === false) {
            $value = Option::MASK_BOOL_FALSE;
        } elseif ($value === null) {
            $value = Option::MASK_NULL;
        } elseif (is_array($value) && empty($value)) {
            $value = Option::MASK_ARRAY;
        } elseif (is_int($value)) {
            $value = Option::MASK_INT . $value;
        } elseif (is_float($value)) {
            $value = Option::MASK_FLOAT . $value;
        }
        return true;
    }
}
