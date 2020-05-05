<?php

namespace diazoxide\wp\lib\option;


use diazoxide\helpers\HTML;
use diazoxide\wp\lib\option\fields\Boolean;
use diazoxide\wp\lib\option\fields\Choice;
use diazoxide\wp\lib\option\fields\EmptyField;
use diazoxide\wp\lib\option\fields\Field;
use diazoxide\wp\lib\option\fields\Input;
use diazoxide\wp\lib\option\fields\Number;
use diazoxide\wp\lib\option\fields\Text;
use Exception;

class Fields
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

    /**
     * Create field markup static method
     *
     * @param array $params
     *
     * @return string
     * @throws Exception
     */
    public static function createField($params = []): string
    {
        /**
         * Main element HTML Attributes
         * */
        $main_params = $params['main_params'] ?? [];

        $before_field = $params['before_field'] ?? '';
        if (is_callable($before_field)) {
            $before_field = $before_field($params);
        }

        $after_field = $params['after_field'] ?? '';
        if (is_callable($after_field)) {
            $after_field = $after_field($params);
        }

        $value = $params['value'] ?? null;
        $name = $params['name'] ?? null;

        $parent = $params['parent'] ?? null;

        $debug_data = $params['debug_data'] ?? null;

        /**
         * Determine Label
         * */
        $label = $params['label'] ?? null;
        $label_params = $params['label_params'] ?? null;
        HTML::addClass($label_params['class'], 'label');

        /**
         * Determine Description
         * */
        $description = $params['description'] ?? null;
        $description_params = $params['description_params'] ?? null;
        HTML::addClass($description_params['class'], 'description');

        $type = $params['type'] ?? null;
        $method = $params['method'] ?? null;
        $values = $params['values'] ?? [];
        $markup = $params['markup'] ?? null;

        /**
         * Automatically select markup type when it missing
         * */
        if ($markup === null) {
            $markup = empty($values) ? Option::MARKUP_TEXT : Option::MARKUP_SELECT;
        }

        $template = $params['template'] ?? null;
        $template_params = $params['template_params'] ?? [];
        $field = $params['field'] ?? null;


        $input_params = $params['input_params'] ?? [];
        HTML::addClass($input_params['class'], [$type, $method]);


        if ($parent !== null) {
            $name = $parent . '[' . $name . ']';
        }

        $disabled = $params['disabled'] ?? false;
        $required = $params['required'] ?? false;
        $readonly = $params['readonly'] ?? false;

        $data = $params['data'] ?? [];
        $data['name'] = $data['name'] ?? $name;


        if (is_callable($description)) {
            $description = $description($params);
        }

        $html = $before_field;

//        if (!empty($debug_data)) {
//            $html .= '<!--' . var_export($debug_data, true) . '-->';
//        }

        /**
         * Fix empty values issue
         * */
        $html .= (new EmptyField(
            [
                'name' => $name,
                'array' => $method === Option::METHOD_MULTIPLE,
                'disabled' => $disabled
            ]
        ))->get();


        switch ($type) {
            case Option::TYPE_BOOL:
                $html .= (new Boolean(
                    [
                        'name' => $name,
                        'value' => $value,
                        'data' => $data,
                        'readonly' => $readonly,
                        'disabled' => $disabled,
                        'required' => $required,
                        'attrs' => $input_params
                    ]
                ))->get();
                break;
            case Option::TYPE_NUMBER:
                $html .= (new Number(
                    [
                        'name' => $name,
                        'value' => $value,
                        'data' => $data,
                        'readonly' => $readonly,
                        'disabled' => $disabled,
                        'required' => $required,
                        'attrs' => $input_params
                    ]
                ))->get();
                break;
            case Option::TYPE_OBJECT:
                if (!empty($template) && !empty($value)) {
                    foreach ($value as $key => $_value) {
                        $_html = '';

                        foreach ($template as $_key => $_field) {
                            $_field['value'] = $_value[$_key] ?? null;
                            $_field['data']['name'] = $name . '[{{encode_key}}]' . '[' . $_key . ']';
                            $_field['name'] = $name . '[' . static::encodeKey($key) . ']' . '[' . $_key . ']';
                            $_html .= static::createField($_field);
                        }

                        $html .= static::group(
                            implode(
                                '',
                                [
                                    HTML::tagOpen(
                                        'input',
                                        [
                                            'class' => 'key full',
                                            'type' => 'text',
                                            'placeholder' => $label,
                                            'value' => $key,
                                            'onchange' => 'diazoxide.wordpress.option.objectKeyChange(this)'
                                        ]
                                    ),
                                    static::group($_html),
                                    static::itemButtons()
                                ]
                            ),
                            ['minimised' => 'false']
                        );
                    }
                } elseif ($field !== null && !empty($field)) {
                    if (!empty($value)) {
                        foreach ($value as $key => $_value) {
                            $_field = $field;
                            $_field['value'] = $_value;
                            $_field['data']['name'] = $name . '[{{encode_key}}]';
                            $_field['name'] = $name . '[' . static::encodeKey($key) . ']';
                            $html .= static::group(
                                implode(
                                    '',
                                    [

                                        HTML::tagOpen(
                                            'input',
                                            [
                                                'class' => 'key full',
                                                'type' => 'text',
                                                'placeholder' => $label,
                                                'value' => $key,
                                                'onchange' => 'diazoxide.wordpress.option.objectKeyChange(this)'
                                            ]
                                        ),
                                        static::createField($_field),
                                        static::itemButtons()
                                    ]
                                )
                            );
                        }
                    }
                }

                $_html = '';

                if ($template !== null && !empty($template)) {
                    foreach ($template as $key => $_field) {
                        $_field['name'] = $name . '[{{encode_key}}]' . '[' . $key . ']';
                        $_field['disabled'] = true;
                        $_html .= static::createField($_field);
                    }
                } elseif ($field !== null && !empty($field)) {
                    $field['name'] = $name . '[{{encode_key}}]';
                    $field['disabled'] = true;
                    $_html .= static::createField($field);
                }

                $html .= static::group(
                    implode(
                        '',
                        [
                            HTML::tagOpen(
                                'input',
                                [
                                    'class' => 'key full',
                                    'type' => 'text',
                                    'placeholder' => $label,
                                    'onchange' => 'diazoxide.wordpress.option.objectKeyChange(this)'
                                ]
                            ),
                            static::group($_html),
                            static::itemButtons(['duplicate', 'remove'])

                        ]
                    ),
                    ['new' => 'true', 'class' => 'hidden']
                );

                $html .= static::addNewButton();

                break;
            case Option::TYPE_GROUP:
                if (empty($template)) {
                    break;
                }
                $template_description = $template_params['description'] ?? null;
                $template_attrs = $template_params['attrs'] ?? [];
                $template_attrs['minimised'] = 'false';

                if ($method === Option::METHOD_MULTIPLE) {
                    $last_key = 1;
                    if ($value !== null) {
                        $value = array_values($value);
                        $last_key = count($value) - 1;
                        foreach ($value as $key => $_value) {
                            $__html = '';

                            foreach ($template as $_key => $_field) {
                                $_field = $template[$_key];
                                $_field['name'] = $name . '[' . $key . ']' . '[' . $_key . ']';
                                $_field['value'] = $_value[$_key] ?? '';
                                $__html .= static::createField($_field);
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

                    foreach ($template as $key => $_field) {
                        $_field['name'] = $name . '[{{LAST_KEY}}]' . '[' . $key . ']';
                        $_field['disabled'] = true;
                        $__html .= static::createField($_field);
                    }

                    if (is_callable($template_description)) {
                        $template_description = $template_description(null, null);
                    }

                    $__html .= $template_description !== null ? HTML::tag(
                        'div',
                        $template_description,
                        ['class' => 'description']
                    ) : '';

                    $template_attrs['new'] = 'true';
                    $template_attrs['class'] = 'hidden';
                    $html .= static::group(
                        $__html . static::itemButtons(['remove']),
                        $template_attrs
                    );

                    $html .= static::addNewButton($last_key);
                } else {
                    foreach ($template as $key => $_field) {
                        $_field['name'] = $name . '[' . $key . ']';
                        $_field['value'] = $value[$key] ?? null;
                        $_field['disabled'] = $disabled;
                        $html .= static::createField($_field);
                    }
                }

                break;
            default:
                if (!empty($values)) {
                    HTML::addClass($input_params['class'], 'full');
                    $html .= (new Choice(
                        [
                            'name' => $name,
                            'value' => $value,
                            'choices' => $values,
                            'markup' => $markup,
                            'multiple' => $method === Option::METHOD_MULTIPLE,
                            'attrs' => $input_params,
                            'disabled' => $disabled,
                            'required' => $required,
                            'readonly' => $readonly
                        ]
                    ))->get();
                } elseif ($method === Option::METHOD_MULTIPLE) {
                    HTML::addClass($input_params['class'], 'full');
                    if (is_array($value)) {
                        foreach ($value as $key => $_value) {
                            if (!empty($_value)) {
                                $html .= static::group(
                                    (new Input(
                                        [
                                            'name' => $name . '[]',
                                            'type' => $markup,
                                            'placeholder' => $label,
                                            'value' => $_value,
                                            'attrs' => $input_params,
                                            'disabled' => $disabled,
                                            'readonly' => $readonly,
                                            'required' => $required,
                                        ]
                                    ))->get() . static::itemButtons(['duplicate', 'remove'])
                                );
                            }
                        }
                    }

                    $html .= static::group(
                        (new Input(
                            [
                                'type' => $markup,
                                'name' => $name . '[]',
                                'placeholder' => $label,
                                'disabled' => true,
                                'attrs' => $input_params
                            ]
                        ))->get() . static::itemButtons(['remove']),
                        [
                            'class' => 'hidden',
                            'new' => 'true',
                            'onclick' => "var e=this.querySelector('input[name]'); e.disabled = false; e.focus()"
                        ]
                    );

                    $html .= static::addNewButton();
                } elseif ($method !== Option::METHOD_MULTIPLE) {
                    HTML::addClass($input_params['class'], 'full');
                    if ($markup === Option::MARKUP_NUMBER) {
                        $html .= (new Number(
                            [
                                'name' => $name,
                                'value' => $value,
                                'placeholder' => $label,
                                'attrs' => $input_params,
                                'data' => $data,
                                'disabled' => $disabled,
                                'readonly' => $readonly,
                                'required' => $required
                            ]
                        ))->get();
                    } else {
                        $html .= (new Text(
                            [
                                'name' => $name,
                                'value' => $value,
                                'placeholder' => $label,
                                'attrs' => $input_params,
                                'data' => $data,
                                'large' => $markup === Option::MARKUP_TEXTAREA,
                                'disabled' => $disabled,
                                'readonly' => $readonly,
                                'required' => $required
                            ]
                        ))->get();
                    }
                } else {
                    $html .= static::group('Not handled!');
                }
                break;
        }

        $main_params['class'] = $main_params['class'] ?? 'group';

        $html = HTML::tag('div', $html, $main_params);

        if (!empty($label)) {
            $html = HTML::tag('div', $label, $label_params) . $html;
        }

        if (!empty($description)) {
            $html .= HTML::tag('div', $description, $description_params);
        }

        $html .= $after_field;

        return $html;
    }

    /**
     * Create group HTML tag
     *
     * @param string $content
     * @param array $attrs
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
     * @param array|null $buttons
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
            $html .= call_user_func([static::class, $fn_name]);
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
                'class' => 'item-button remove',
                'onclick' => 'diazoxide.wordpress.option.removeItem(this)',
                'type' => 'button',
                'title' => 'Remove item'
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
                'class' => 'item-button minimise',
                'onclick' => 'diazoxide.wordpress.option.minimiseItem(this)',
                'type' => 'button',
                'title' => 'Minimise item'
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
                'type' => 'button',
                'class' => 'item-button duplicate',
                'onclick' => 'diazoxide.wordpress.option.duplicateItem(this)',
                'title' => 'Duplicate item'
            ]
        );
    }

    /**
     * Add new button markup
     *
     * @param int|null $last_key
     *
     * @return string
     */
    private static function addNewButton(?int $last_key = 0): string
    {
        return static::group(
            HTML::tag(
                'button',
                '+ Add new',
                [
                    'type' => 'button',
                    'last-key' => $last_key,
                    'class' => 'button button-primary',
                    'onclick' => 'diazoxide.wordpress.option.addNew(this)',
                    'title' => 'Add new item'
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
     * @param array $input
     *
     * @return array
     */
    public static function decodeKeys(array $input): array
    {
        $return = array();
        foreach ($input as $key => $value) {
            $key = static::maybeDecodeKey($key);

            if (is_array($value)) {
                $value = static::decodeKeys($value);
            } elseif (is_string($value)) {
                $value = stripslashes($value);
                self::unmaskFieldValue($value);
            }
            $return[$key] = $value;
        }

        return $return;
    }
}