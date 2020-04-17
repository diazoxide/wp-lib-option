<?php

namespace diazoxide\wp\lib\option;


use diazoxide\helpers\HTML;

class Fields
{
    /**
     * Create field markup static method
     *
     * @param array $params
     *
     * @return string
     */
    public static function createField($params = []): string
    {
        $main_params = $params['main_params'] ?? [];

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


        $input_attrs = $params['input_attrs'] ?? [];
        HTML::addClass($input_attrs['class'], [$type, $method]);


        if ($parent !== null) {
            $name = $parent . '[' . $name . ']';
        }

        $disabled = $params['disabled'] ?? false;
        $disabled_str = $disabled ? 'disabled' : '';

        $required = $params['required'] ?? false;
        $required_str = $required ? 'required' : '';

        $readonly = $params['readonly'] ?? false;
        $readonly_str = $readonly ? 'readonly' : '';

        $data = $params['data'] ?? [];
        $data['name'] = $data['name'] ?? $name;


        if (is_callable($description)) {
            $description = $description($params);
        }

        $html = '';

        if (!empty($debug_data)) {
            $html .= '<!--' . var_export($debug_data, true) . '-->';
        }

        /**
         * Fix empty values issue
         * */
        $html .= HTML::tagOpen(
            'input',
            [
                'type' => 'hidden',
                $disabled_str,
                'name' => $name,
                'value' => $method === Option::METHOD_MULTIPLE ? Option::MASK_ARRAY : Option::MASK_NULL
            ]
        );

        switch ($type) {
            case Option::TYPE_BOOL:
                $html .= HTML::tagOpen(
                    'input',
                    ['value' => Option::MASK_BOOL_FALSE, 'type' => 'hidden', 'name' => $name]
                );

                $html .= HTML::tagOpen(
                    'input',
                    $input_attrs + [
                        'value' => Option::MASK_BOOL_TRUE,
                        'type' => 'checkbox',
                        'name' => $name,
                        'data' => $data,
                        $value ? 'checked' : '',
                        $readonly_str,
                        $disabled_str,
                        $required_str
                    ]
                );
                break;
            case Option::TYPE_OBJECT:
                $on_change = "var fields = this.parentElement.querySelectorAll('[name]'); for (var i = 0; i < fields.length; i++) { var field = fields[i]; if (this.value != null) { field.removeAttribute('disabled') }; var attr =  field.getAttribute('name'); attr = attr.replace(/{{encode_key}}.*?(?=\])/gm, '{{encode_key}}' + btoa(this.value)); fields[i].setAttribute('name', attr); }";

                if ($template !== null && !empty($template)) {
                    foreach ($value as $key => $_value) {
                        $_html = '';

                        foreach ($template as $_key => $_field) {
                            $_field['value'] = $_value[$_key] ?? null;
                            $_field['data']['name'] = $name . '[{{encode_key}}]' . '[' . $_key . ']';
                            $_field['name'] = $name . '[' . self::encodeKey($key) . ']' . '[' . $_key . ']';
                            $_html .= self::createField($_field);
                        }

                        $html .= self::group(
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
                                            'onchange' => $on_change
                                        ]
                                    ),
                                    self::group($_html),
                                    self::itemButtons()
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
                            $_field['name'] = $name . '[' . self::encodeKey($key) . ']';
                            $html .= self::group(
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
                                                'onchange' => $on_change
                                            ]
                                        ),
                                        self::createField($_field),
                                        self::itemButtons()
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
                        $_html .= self::createField($_field);
                    }
                } elseif ($field !== null && !empty($field)) {
                    $field['name'] = $name . '[{{encode_key}}]';
                    $field['disabled'] = true;
                    $_html .= self::createField($field);
                }

                $html .= self::group(
                    implode(
                        '',
                        [
                            HTML::tagOpen(
                                'input',
                                [
                                    'class' => 'key full',
                                    'type' => 'text',
                                    'placeholder' => $label,
                                    'onchange' => $on_change
                                ]
                            ),
                            self::group($_html),
                            self::itemButtons(['duplicate', 'remove'])

                        ]
                    ),
                    ['new' => 'true', 'class' => 'hidden']
                );

                $html .= self::addNewButton();

                break;
            case Option::TYPE_GROUP:
                if (!empty($template)) {
                    $template_description = $template_params['description'] ?? null;
                    $template_attrs = $template_params['attrs'] ?? [];
                    $template_attrs['minimised'] = 'false';

                    if ($method === Option::METHOD_SINGLE) {
                        foreach ($template as $key => $_field) {
                            $_field['name'] = $name . '[' . $key . ']';
                            $_field['value'] = $value[$key] ?? null;
                            $html .= self::createField($_field);
                        }
                    } elseif ($method === Option::METHOD_MULTIPLE) {
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
                                    $__html .= self::createField($_field);
                                }

                                if (is_callable($template_description)) {
                                    $template_description = $template_description($key, $_value);
                                }

                                $__html .= $template_description !== null ? HTML::tag(
                                    'div',
                                    $template_description,
                                    ['class' => 'description']
                                ) : '';

                                $html .= self::group(
                                //Todo: Handle group duplication and minimise
                                    $__html . self::itemButtons(['remove']),
                                    $template_attrs
                                );
                            }
                        }

                        $__html = '';

                        foreach ($template as $key => $_field) {
                            $_field['name'] = $name . '[{{LAST_KEY}}]' . '[' . $key . ']';
                            $_field['disabled'] = true;
                            $__html .= self::createField($_field);
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
                        $html .= self::group(
                            $__html . self::itemButtons(['remove']),
                            $template_attrs
                        );

                        $html .= self::addNewButton($last_key);
                    }
                }
                break;
            default:
                if (!empty($values)) {
                    if ($markup === null || $markup === Option::MARKUP_SELECT) {
                        HTML::addClass($input_attrs['class'], 'full');

                        $html .= HTML::tagOpen(
                            'select',
                            $input_attrs + [
                                'select2' => 'true',
                                'name' => $name . ($method === Option::METHOD_MULTIPLE ? '[]' : ''),
                                $method === Option::METHOD_MULTIPLE ? 'multiple' : '',
                                'data' => $data,
                                $disabled_str,
                                $readonly_str,
                                $required_str
                            ]
                        );
                        $open_tag_select = true;
                    }
                    $value = is_array($value) ? $value : [$value];

                    self::sortSelectValues($values, $value);

                    foreach ($values as $key => $_value) {
                        if ($markup === null || $markup === Option::MARKUP_SELECT) {
                            $html .= HTML::tag(
                                'option',
                                $_value,
                                [
                                    'value' => $key,
                                    (($key === $value) || in_array($key, $value, true)) ? 'selected' : ''
                                ]
                            );
                        } elseif ($markup === Option::MARKUP_CHECKBOX) {
                            if ($method === Option::METHOD_MULTIPLE) {
                                $html .= self::group(
                                    HTML::tag(
                                        'label',
                                        HTML::tagOpen(
                                            'input',
                                            [
                                                'type' => 'checkbox',
                                                'name' => $name . '[]',
                                                'value' => $key,
                                                'data' => $data,
                                                (($key === $value) || in_array($key, $value, true)) ? 'checked' : '',
                                                $disabled_str,
                                                $readonly_str,
                                                $required_str
                                            ]
                                        ) . $_value
                                    )
                                );
                            } else {
                                $html .= self::group(
                                    HTML::tag(
                                        'label',
                                        HTML::tagOpen(
                                            'input',
                                            [
                                                'type' => 'radio',
                                                'name' => $name,
                                                'value' => $key,
                                                (($key === $value) || in_array($key, $value, true)) ? 'checked' : '',
                                                'data' => $data,
                                                $disabled_str,
                                                $readonly_str,
                                                $required_str
                                            ]
                                        ) . $_value
                                    )
                                );
                            }
                        }
                    }

                    if (isset($open_tag_select)) {
                        $html .= HTML::tagClose('select');
                    }
                } elseif ($method === Option::METHOD_MULTIPLE) {
                    HTML::addClass($input_attrs['class'], 'full');
                    if (is_array($value)) {
                        foreach ($value as $key => $_value) {
                            if (!empty($_value)) {
                                $html .= self::group(
                                    HTML::tagOpen(
                                        'input',
                                        $input_attrs + [
                                            'name' => $name . '[]',
                                            'type' => $markup,
                                            'placeholder' => $label,
                                            'value' => $_value,
                                            $disabled_str,
                                            $readonly_str,
                                            $required_str
                                        ]
                                    ) . self::itemButtons(['duplicate', 'remove'])
                                );
                            }
                        }
                    }
                    $html .= self::group(
                        HTML::tagOpen(
                            'input',
                            $input_attrs + [
                                'name' => $name . '[]',
                                'type' => $markup,
                                'placeholder' => $label,
                                'disabled'
                            ]
                        ) . self::itemButtons(['remove']),
                        [
                            'class' => 'hidden',
                            'new' => 'true',
                            'onclick' => "var e=this.querySelector('input[name]'); e.disabled = false; e.focus()"
                        ]
                    );

                    $html .= self::addNewButton();
                } elseif ($method !== Option::METHOD_MULTIPLE) {
                    if (in_array($markup, [Option::MARKUP_TEXT, Option::MARKUP_NUMBER], true)) {
                        $html .= HTML::tagOpen(
                            'input',
                            [
                                'class' => 'full',
                                'type' => $markup,
                                'placeholder' => $label,
                                'name' => $name,
                                'value' => $value,
                                'data' => $data,
                                $disabled_str,
                                $readonly_str,
                                $required_str
                            ]
                        );
                    } elseif ($markup === Option::MARKUP_TEXTAREA) {
                        $html .= HTML::tagOpen(
                            'textarea',
                            [
                                'class' => 'full',
                                'type' => $markup,
                                'placeholder' => $label,
                                'name' => $name,
                                'data' => $data,
                                $disabled_str,
                                $readonly_str,
                                $required_str
                            ]
                        );
                        $html .= $value;
                        $html .= HTML::tagClose('textarea');
                    }
                } else {
                    $html .= self::group('Not handled!');
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

        return $html;
    }

    /**
     * Normalize `<select>` options values order
     *
     * @param array $values
     * @param array $value
     */
    private static function sortSelectValues(array &$values, array $value): void
    {
        uksort(
            $values,
            static function ($a, $b) use ($value) {
                $a_i = array_search($a, $value, true);
                $b_i = array_search($b, $value, true);

                if ($a_i === false) {
                    return 0;
                }

                if ($b_i === false) {
                    return 1;
                }

                $index = $a_i - $b_i;

                if ($index > 0) {
                    return 1;
                }

                return -1;
            }
        );
    }

    /**
     * Create group HTML tag
     *
     * @param string $content
     * @param array $attrs
     *
     * @return string
     */
    private static function group(string $content, array $attrs = []): string
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
            $html .= call_user_func([self::class, $fn_name]);
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
        return self::group(
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
     * Check if form field is boolean and return
     * Real boolean value
     *
     * @param $str
     *
     * @return bool|string
     */
    private static function maybeBoolean($str)
    {
        if ($str === Option::MASK_BOOL_TRUE) {
            return true;
        }

        if ($str === Option::MASK_BOOL_FALSE) {
            return false;
        }

        return $str;
    }

    /**
     * Check if form field is boolean and return
     * Real boolean value
     *
     * @param $str
     *
     * @return bool|string
     */
    private static function maybeNull($str)
    {
        if ($str === Option::MASK_NULL) {
            return null;
        }

        return $str;
    }

    /**
     * Check if form field is array and return
     * Real array value
     *
     * @param $str
     *
     * @return array|string
     */
    private static function maybeArray($str)
    {
        if ($str === Option::MASK_ARRAY) {
            return [];
        }

        return $str;
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
            $key = self::maybeDecodeKey($key);

            if (is_array($value)) {
                $value = self::decodeKeys($value);
            } elseif (is_string($value)) {
                $value = stripslashes($value);
                $value = self::maybeBoolean($value);
                $value = self::maybeArray($value);
                $value = self::maybeNull($value);
            }
            $return[$key] = $value;
        }

        return $return;
    }
}