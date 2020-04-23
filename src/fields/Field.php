<?php


namespace diazoxide\wp\lib\option\fields;


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

    public $errors = [];

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
    public static function unmask(&$value):bool {
        return false;
    }
}