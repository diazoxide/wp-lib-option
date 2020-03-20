<?php


namespace diazoxide\wp\lib\option\v2;


class Option extends \diazoxide\wp\lib\option\Option
{

    /**
     * Option constructor.
     * @param array $_params
     */
    public function __construct($_params = [])
    {
        parent::__construct(
            $_params['name'] ?? null,
            $_params['default'] ?? null,
            $_params
        );
    }
}