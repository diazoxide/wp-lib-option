<?php

namespace diazoxide\wp\lib\option;

use diazoxide\helpers\HTML;
use diazoxide\helpers\Strings;
use diazoxide\wp\lib\option\fields\Boolean;
use diazoxide\wp\lib\option\fields\Choice;
use diazoxide\wp\lib\option\fields\EmptyField;
use diazoxide\wp\lib\option\fields\Field;
use diazoxide\wp\lib\option\fields\Input;
use diazoxide\wp\lib\option\fields\Number;
use diazoxide\wp\lib\option\fields\Text;
use Exception;
use diazoxide\wp\lib\option\v2\Option;
use InvalidArgumentException;
use RuntimeException;

class Bundle
{

    /**
     * @var Field[]
     */
    private static $fields_classes = [
        Boolean::class,
        Choice::class,
        EmptyField::class,
        Input::class,
        Number::class,
        Text::class
    ];

    public $id;
    public $level = 1;
    public $main_params = [];
    public $depends_on = [];
    public $before_field = '';
    public $after_field = '';
    public $value;
    public $default;
    public $name;
    public $relation;
    public $serialize;
    public $single_option;
    public $parent;
    public $debug_data;
    public $label;
    public $label_params = [];

    public $description;
    public $description_params = [];

    public $type;
    public $method;
    public $markup;
    public $values = [];

    public $template;
    public $template_params = [];
    public $field;
    public $field_params = [];
    public $input_params = [];

    public $disabled = false;
    public $readonly = false;
    public $required = false;

    public $data = [];

    /**
     * Fields constructor.
     *
     * @param  array  $params
     */
    public function __construct(array $params = [])
    {
        foreach ($params as $arg => $value) {
            if (property_exists(static::class, $arg)) {
                $this->{$arg} = self::maybeClosure($value, [$params]);
            } else {
                throw new InvalidArgumentException("'$arg' is not valid argument for '" . static::class . "'");
            }
        }

        $this->main_params['option_id']        = $this->id;
        $this->input_params['option_id']       = $this->id;
        $this->field_params['option_id']       = $this->id;
        $this->label_params['option_id']       = $this->id;
        $this->description_params['option_id'] = $this->id;
        $this->template_params['option_id']    = $this->id;

        HTML::addClass($this->main_params['class'], ['main', 'group']);
        HTML::addClass($this->input_params['class'], 'input');
        HTML::addClass($this->field_params['class'], 'field');
        HTML::addClass($this->template_params['class'], 'template');
        HTML::addClass($this->label_params['class'], 'label');
        HTML::addClass($this->description_params['class'], 'description');

        /**
         * Automatically select markup type when it missing
         * */
        if (! isset($this->markup)) {
            $this->markup = empty($this->values) ? Option::MARKUP_TEXT : Option::MARKUP_SELECT;
        }

        HTML::addClass($this->input_params['class'], [$this->type, $this->method]);

        if (isset($this->parent)) {
            $this->name = $this->parent . '[' . $this->name . ']';
        }


        if (! empty($this->depends_on)) {
            foreach ($this->depends_on as $item) {
                /** @var Option $option */
                $option        = $item[0] ?? null;
                $defined_value = $item[1] ?? null;
                if ($option) {
                    if ($option->getValue() != ($defined_value ?? null)) {
                        HTML::addClass($this->main_params['class'], 'hidden');
                        HTML::addClass($this->label_params['class'], 'hidden');
                        HTML::addClass($this->description_params['class'], 'hidden');
                    }
                    $option_id     = $this->id;
                    $dependency_id = $option->getParam('id');
                    Field::mask($defined_value);
                    $defined_value     = str_replace('\\', '\\\\', $defined_value);
                    $this->after_field = sprintf(
                        "<script type=\"application/javascript\">%s('%s','%s','%s');</script>",
                        'window.diazoxide.wordpress.option.registerDependencyChangeListener',
                        $option_id,
                        $dependency_id,
                        $defined_value
                    );
                }
            }
        }

        $this->data['name'] = $this->data['name'] ?? $this->name;

        $this->value = $this->value ?? $this->default;
    }

    /**
     * Create field markup static method
     *
     * @return string
     * @throws Exception
     */
    public function get(): string
    {
        $html = $this->before_field;

        /**
         * @example
         * ```php
         * [
         *  'with' => [
         *   'parent' => 'some_another_parent',
         *   'name' => 'option_name',
         *   'key' => 'name',
         *  ],
         *  'label'=>'label'
         * ]
         * ```
         * */
        if (isset($this->relation)) {
            $this->values = [];

            $relation_parent = $this->relation['parent'] ?? null;
            $relation_option = $this->relation['with'] ?? null;
            $relation_name   = $this->relation['name'] ?? null;
            $relation_key    = $this->relation['key'] ?? null;

            if (is_callable($relation_option)) {
                $relation_option = $relation_option();
            } else {
                throw new RuntimeException('Only callable accepting.');
            }

            $relation_label = $this->relation['label'] ?? null;

            Option::initOptions(
                $relation_option,
                $relation_parent,
                ['serialize' => $this->serialize, 'single_option' => $this->single_option]
            );

            if ($relation_name !== null) {
                $relation_option = $relation_option[$relation_name] ?? null;
                if ($relation_option instanceof Option) {
                    $relation_option = $relation_option->getValue();
                }
            }

            foreach ($relation_option as $relation_index => $relation_item) {
                $relation_item_key                = $relation_item[$relation_key] ?? $relation_index;
                $this->values[$relation_item_key] = $relation_item[$relation_label] ?? $relation_index;
            }
        }

//        $this->debug_data['serialize'] = $this->serialize;
//
//        $html .= '<!--' . var_export($this->debug_data, true) . '-->';

        /**
         * Fix empty values issue
         * */
        $html .= (new EmptyField(
            [
                'name'     => $this->name,
                'array'    => $this->method === Option::METHOD_MULTIPLE,
                'disabled' => $this->disabled
            ]
        ))->get();


        switch ($this->type) {
            case Option::TYPE_BOOL:
                $html .= (new Boolean(
                    [
                        'name'     => $this->name,
                        'value'    => $this->value,
                        'data'     => $this->data,
                        'readonly' => $this->readonly,
                        'disabled' => $this->disabled,
                        'required' => $this->required,
                        'attrs'    => $this->input_params,
                    ]
                ))->get();
                break;
            case Option::TYPE_NUMBER:
                $html .= (new Number(
                    [
                        'name'     => $this->name,
                        'value'    => $this->value,
                        'data'     => $this->data,
                        'readonly' => $this->readonly,
                        'disabled' => $this->disabled,
                        'required' => $this->required,
                        'attrs'    => $this->input_params,
                    ]
                ))->get();
                break;
            case Option::TYPE_OBJECT:
                $key_params = array_merge(
                    [
                        'type'        => 'text',
                        'placeholder' => $this->label,
                        'level'       => $this->level,
                        'onchange'    => 'diazoxide.wordpress.option.objectKeyChange(this)'
                    ],
                    $this->input_params
                );
                HTML::addClass($key_params['class'], ['key', 'full']);

                if (! empty($this->template) && ! empty($this->value)) {
                    foreach ($this->value as $key => $_value) {
                        $_html = '';

                        foreach ($this->template as $_key => $_field) {
                            $_field['value']         = $_value[$_key] ?? null;
                            $_field['data']['name']  = $this->name . '[{{encode_key}}]' . '[' . $_key . ']';
                            $_field['name']          = $this->name . '[' . static::encodeKey(
                                $key
                            ) . ']' . '[' . $_key . ']';
                            $_field['serialize']     = $this->serialize;
                            $_field['single_option'] = $this->single_option;
                            $_field['level']         = $this->level + 1;
                            $_html                   .= (new static($_field))->get();
                        }

                        $html .= static::group(
                            implode(
                                '',
                                [
                                    HTML::tagOpen(
                                        'input',
                                        $key_params + ['value' => $key]
                                    ),
                                    static::group($_html),
                                    static::itemButtons()
                                ]
                            ),
                            ['minimised' => 'false', 'level' => $this->level]
                        );
                    }
                } elseif (isset($this->field) && ! empty($this->field)) {
                    if (! empty($this->value)) {
                        foreach ($this->value as $key => $_value) {
                            $_field                  = $this->field;
                            $_field['value']         = $_value;
                            $_field['data']['name']  = $this->name . '[{{encode_key}}]';
                            $_field['name']          = $this->name . '[' . static::encodeKey($key) . ']';
                            $_field['serialize']     = $this->serialize;
                            $_field['single_option'] = $this->single_option;
                            $_field['level']         = $this->level + 1;

                            $key_params['value'] = $key;

                            $html .= static::group(
                                implode(
                                    '',
                                    [
                                        HTML::tagOpen(
                                            'input',
                                            $key_params + ['value' => $key]
                                        ),
                                        (new static($_field))->get(),
                                        static::itemButtons()
                                    ]
                                ),
                                ['level' => $this->level]
                            );
                        }
                    }
                }

                $_html = '';

                if (isset($this->template) && ! empty($this->template)) {
                    foreach ($this->template as $key => $_field) {
                        $_field['name']          = $this->name . '[{{encode_key}}]' . '[' . $key . ']';
                        $_field['disabled']      = true;
                        $_field['serialize']     = $this->serialize;
                        $_field['single_option'] = $this->single_option;
                        $_field['level']         = $this->level + 1;
                        $_html                   .= (new static($_field))->get();
                    }
                } elseif (isset($this->field) && ! empty($this->field)) {
                    $this->field['name']          = $this->name . '[{{encode_key}}]';
                    $this->field['disabled']      = true;
                    $this->field['serialize']     = $this->serialize;
                    $this->field['single_option'] = $this->single_option;
                    $this->field['level']         = $this->level + 1;

                    $_html .= (new static($this->field))->get();
                }

                $html .= static::group(
                    implode(
                        '',
                        [
                            HTML::tagOpen(
                                'input',
                                $key_params
                            ),
                            static::group($_html),
                            static::itemButtons(['duplicate', 'remove'])

                        ]
                    ),
                    ['new' => 'true', 'class' => 'hidden', 'level' => $this->level]
                );

                $html .= $this->addNewButton();

                break;
            case Option::TYPE_GROUP:
                if (empty($this->template)) {
                    break;
                }
                $template_description        = $this->template_params['description'] ?? null;
                $template_attrs              = $this->template_params['attrs'] ?? [];
                $template_attrs['minimised'] = 'false';

                if ($this->method === Option::METHOD_MULTIPLE) {
                    $last_key = 1;
                    if (isset($this->value)) {
                        $this->value = array_values($this->value);
                        $last_key    = count($this->value) - 1;
                        foreach ($this->value as $key => $_value) {
                            $__html = '';

                            foreach ($this->template as $_key => $_field) {
                                $_field                  = $this->template[$_key];
                                $_field['name']          = $this->name . '[' . $key . ']' . '[' . $_key . ']';
                                $_field['value']         = $_value[$_key] ?? null;
                                $_field['serialize']     = $this->serialize;
                                $_field['single_option'] = $this->single_option;
                                $_field['level']         = $this->level + 1;
                                if (! isset($_field['label']) && ! is_numeric($key)) {
                                    $_field['label'] = Strings::toLabel($key);
                                }
                                $__html .= (new static($_field))->get();
                            }

                            if (is_callable($template_description)) {
                                $template_description = $template_description($key, $_value);
                            }

                            $__html .= $template_description !== null ? HTML::tag(
                                'div',
                                $template_description,
                                ['class' => 'description']
                            ) : '';

                            $html .= static::group(
                            //Todo: Handle group duplication and minimise
                                $__html . static::itemButtons(['remove']),
                                $template_attrs
                            );
                        }
                    }

                    $__html = '';

                    foreach ($this->template as $key => $_field) {
                        $_field['name']          = $this->name . '[{{LAST_KEY}}]' . '[' . $key . ']';
                        $_field['disabled']      = true;
                        $_field['serialize']     = $this->serialize;
                        $_field['single_option'] = $this->single_option;
                        $_field['level']         = $this->level + 1;
                        if (! isset($_field['label']) && ! is_numeric($key)) {
                            $_field['label'] = Strings::toLabel($key);
                        }

                        $__html .= (new static($_field))->get();
                    }

                    if (is_callable($template_description)) {
                        $template_description = $template_description(null, null);
                    }

                    $__html .= $template_description !== null ? HTML::tag(
                        'div',
                        $template_description,
                        ['class' => 'description']
                    ) : '';

                    $template_attrs['new']   = 'true';
                    $template_attrs['class'] = 'hidden';
                    $html                    .= static::group(
                        $__html . static::itemButtons(['remove']),
                        $template_attrs
                    );


                    $html .= $this->addNewButton($last_key);
                } else {
                    foreach ($this->template as $key => $_field) {
                        $_field['name']     = $this->name . '[' . $key . ']';
                        $_field['value']    = $this->value[$key] ?? null;
                        $_field['disabled'] = $this->disabled;

                        if (! isset($_field['label']) && ! is_numeric($key)) {
                            $_field['label'] = Strings::toLabel($key);
                        }

                        $_field['serialize']     = $this->serialize;
                        $_field['single_option'] = $this->single_option;
                        $_field['level']         = $this->level + 1;
                        $html                    .= (new static($_field))->get();
                    }
                }

                break;
            default:
                if (! empty($this->values)) {
                    HTML::addClass($this->input_params['class'], 'full');
                    $html .= (new Choice(
                        [
                            'name'     => $this->name,
                            'value'    => $this->value,
                            'choices'  => $this->values,
                            'markup'   => $this->markup,
                            'multiple' => $this->method === Option::METHOD_MULTIPLE,
                            'attrs'    => $this->input_params,
                            'disabled' => $this->disabled,
                            'required' => $this->required,
                            'readonly' => $this->readonly
                        ]
                    ))->get();
                } elseif ($this->method === Option::METHOD_MULTIPLE) {
                    HTML::addClass($this->input_params['class'], 'full');
                    if (is_array($this->value)) {
                        foreach ($this->value as $key => $_value) {
                            if (! empty($_value)) {
                                $html .= static::group(
                                    (new Input(
                                        [
                                            'name'        => $this->name . '[]',
                                            'type'        => $this->markup,
                                            'placeholder' => $this->label,
                                            'value'       => $_value,
                                            'attrs'       => $this->input_params,
                                            'disabled'    => $this->disabled,
                                            'readonly'    => $this->readonly,
                                            'required'    => $this->required,
                                        ]
                                    ))->get() . static::itemButtons(['duplicate', 'remove'])
                                );
                            }
                        }
                    }

                    $html .= static::group(
                        (new Input(
                            [
                                'type'        => $this->markup,
                                'name'        => $this->name . '[]',
                                'placeholder' => $this->label,
                                'disabled'    => true,
                                'attrs'       => $this->input_params
                            ]
                        ))->get() . static::itemButtons(['remove']),
                        [
                            'class'   => 'hidden',
                            'new'     => 'true',
                            'onclick' => "var e=this.querySelector('input[name]'); e.disabled = false; e.focus()"
                        ]
                    );

                    $html .= $this->addNewButton();
                } elseif ($this->method !== Option::METHOD_MULTIPLE) {
                    HTML::addClass($this->input_params['class'], 'full');
                    if ($this->markup === Option::MARKUP_NUMBER) {
                        $html .= (new Number(
                            [
                                'name'        => $this->name,
                                'value'       => $this->value,
                                'placeholder' => $this->label,
                                'attrs'       => $this->input_params,
                                'data'        => $this->data,
                                'disabled'    => $this->disabled,
                                'readonly'    => $this->readonly,
                                'required'    => $this->required
                            ]
                        ))->get();
                    } else {
                        $html .= (new Text(
                            [
                                'name'        => $this->name,
                                'value'       => $this->value,
                                'placeholder' => $this->label,
                                'attrs'       => $this->input_params,
                                'data'        => $this->data,
                                'large'       => $this->markup === Option::MARKUP_TEXTAREA,
                                'disabled'    => $this->disabled,
                                'readonly'    => $this->readonly,
                                'required'    => $this->required
                            ]
                        ))->get();
                    }
                } else {
                    $html .= static::group('Not handled!');
                }
                break;
        }

        $html = HTML::tag('div', $html, $this->main_params);

        if (! empty($this->label)) {
            $html = HTML::tag('div', $this->label, $this->label_params) . $html;
        }

        if (! empty($this->description)) {
            $html .= HTML::tag('div', $this->description, $this->description_params);
        }

        $html .= $this->after_field;

        return $html;
    }

    /**
     * @param $value
     * @param  array|null  $params
     *
     * @return mixed
     */
    private static function maybeClosure($value, ?array $params = [])
    {
        if (! is_string($value) && is_callable($value)) {
            $value = $value(...$params);
        }

        return $value;
    }

    /**
     * Create group HTML tag
     *
     * @param  string  $content
     * @param  array  $attrs
     *
     * @return string
     */
    public static function group(string $content, array $attrs = []): string
    {
        HTML::addClass($attrs['class'], 'group');
        $html = HTML::tagOpen('div', $attrs);
        $html .= $content;
        $html .= HTML::tagClose('div');

        return $html;
    }

    /**
     * Get form item buttons
     *
     * @param  array|null  $buttons
     *
     * @return string
     * @uses minimiseButton
     * @uses duplicateButton
     *
     * @uses removeButton
     */
    private static function itemButtons(?array $buttons = null): string
    {
        $html = HTML::tagOpen('div', ['class' => 'buttons']);

        if ($buttons === null) {
            $buttons = ['duplicate', 'minimise', 'remove'];
        }
        foreach ($buttons as $button) {
            $fn_name = $button . 'Button';
            $html    .= call_user_func([static::class, $fn_name]);
        }

        $html .= HTML::tagClose('div');

        return $html;
    }

    /**
     * Remove button markup
     *
     * @return string
     */
    private static function removeButton(): string
    {
        return HTML::tag(
            'button',
            'X',
            [
                'class'   => 'item-button remove',
                'onclick' => 'diazoxide.wordpress.option.removeItem(this)',
                'type'    => 'button',
                'title'   => 'Remove item'
            ]
        );
    }

    /**
     * Minimise button markup
     *
     * @return string
     */
    private static function minimiseButton(): string
    {
        return HTML::tag(
            'button',
            '',
            [
                'class'   => 'item-button minimise',
                'onclick' => 'diazoxide.wordpress.option.minimiseItem(this)',
                'type'    => 'button',
                'title'   => 'Minimise item'
            ]
        );
    }

    /**
     * Duplicate button markup
     *
     * @return string
     */
    private static function duplicateButton(): string
    {
        return HTML::tag(
            'button',
            '&#65291;',
            [
                'type'    => 'button',
                'class'   => 'item-button duplicate',
                'onclick' => 'diazoxide.wordpress.option.duplicateItem(this)',
                'title'   => 'Duplicate item'
            ]
        );
    }

    /**
     * Add new button markup
     *
     * @param  int|null  $last_key
     *
     * @return string
     */
    private function addNewButton(?int $last_key = 0): string
    {
        $label = strtolower($this->label);

        return static::group(
            HTML::tag(
                'button',
                '+ Add ' . $label,
                [
                    'type'     => 'button',
                    'last-key' => (string)$last_key,
                    'class'    => 'button button-primary',
                    'onclick'  => 'diazoxide.wordpress.option.addNew(this)',
                    'title'    => 'Click to add new ' . $label
                ]
            )
        );
    }

    /**
     * Encode key of form field
     *
     * @param $key
     *
     * @return string
     */
    public static function encodeKey($key): string
    {
        return '{{encode_key}}' . base64_encode($key);
    }

    /**
     * Check and decode form field key
     *
     * @param $str
     *
     * @return false|string|string[]|null
     */
    private static function maybeDecodeKey($str)
    {
        if (strpos($str, '{{encode_key}}') === 0) {
            $str = preg_replace('/^{{encode_key}}/', '', $str);

            return base64_decode($str);
        }

        return $str;
    }

    /**
     * @param $value
     *
     * @deprecated
     */
    public static function unmaskFieldValue(&$value): void
    {
        foreach (static::$fields_classes as $class) {
            if ($class::unmask($value)) {
                break;
            }
        }
    }

    /**
     * Decode form keys
     *
     * @param  array  $input
     *
     * @return array
     */
    public static function decodeKeys(array $input): array
    {
        $return = array();
        foreach ($input as $key => $value) {
            $key = static::maybeDecodeKey($key);

            if (is_array($value) && ! empty($value)) {
                $value = static::decodeKeys($value);
            } elseif (is_string($value)) {
                $value = stripslashes($value);
                Field::unmask($value);
            }
            $return[$key] = $value;
        }

        return $return;
    }
}
