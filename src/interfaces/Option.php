<?php


namespace diazoxide\wp\lib\option\interfaces;


use diazoxide\wp\lib\option\fields\Choice;

interface Option
{

    public const TYPE_BOOL = 'bool';
    public const TYPE_NUMBER = 'number';
    public const TYPE_TEXT = 'text';
    public const TYPE_OBJECT = 'object';
    public const TYPE_GROUP = 'group';

    public const MARKUP_CHECKBOX = Choice::MARKUP_CHECKBOX;
    public const MARKUP_SELECT = Choice::MARKUP_SELECT;
    public const MARKUP_TEXT = 'text';
    public const MARKUP_TEXTAREA = 'textarea';
    public const MARKUP_NUMBER = 'number';

    public const METHOD_SINGLE = 'single';
    public const METHOD_MULTIPLE = 'multiple';

    public const MASK_NULL = '{~0~}';
    public const MASK_ARRAY = '{~1~}';
    public const MASK_BOOL_TRUE = '{~2~}';
    public const MASK_BOOL_FALSE = '{~3~}';
    public const MASK_INT = '{~4~}';
    public const MASK_FLOAT = '{~5~}';
    
}