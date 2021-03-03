<?php


namespace diazoxide\wp\lib\option\v2;


class Option extends \diazoxide\wp\lib\option\Option
{

    /**
     * Option constructor.
     *
     * @param  array  $_params
     * @param  Option  $reference
     */
    public function __construct($_params = [], self &$reference = null)
    {
        $reference = $this;

        parent::__construct(
            $_params['name'] ?? null,
            $_params['default'] ?? null,
            $_params
        );
    }
}