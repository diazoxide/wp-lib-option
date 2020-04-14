<?php


namespace diazoxide\wp\lib\option\interfaces;


interface Option
{

    public const TYPE_BOOL = 'bool';
    public const TYPE_TEXT = 'text';
    public const TYPE_OBJECT = 'object';
    public const TYPE_GROUP = 'group';

    public const MARKUP_CHECKBOX = 'checkbox';
    public const MARKUP_TEXT = 'text';
    public const MARKUP_TEXTAREA = 'textarea';
    public const MARKUP_NUMBER = 'number';
    public const MARKUP_SELECT = 'select';

    public const METHOD_SINGLE = 'single';
    public const METHOD_MULTIPLE = 'multiple';
    
}