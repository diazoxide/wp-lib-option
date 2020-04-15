<?php

namespace diazoxide\wp\lib\option;

use diazoxide\helpers\Environment;
use diazoxide\helpers\HTML;

/**
 * Class Option
 *
 * @author Aaron Yordanyan
 * */
class Option implements interfaces\Option
{
    /**
     * Option Params
     *
     * @var array
     * */
    private $params = [];

    /**
     * Determine assets loaded status
     *
     * @var bool
     * */
    private static $assets_loaded;

    /**
     * Option constructor.
     *
     * @param null $_name
     * @param null $_default
     * @param array $_params
     */
    public function __construct($_name = null, $_default = null, $_params = [])
    {
        $_params['name'] = $_name;
        $_params['default'] = $_default;
        $this->setParams($_params);
    }

    /**
     * Get Option value
     *
     * @return mixed
     */
    public function getValue()
    {
        return self::getOption(
            $this->getParam('name', null),
            $this->getParam('parent', null),
            $this->getParam('default', null)
        );
    }

    /**
     * Generate option name by option name and parent name
     *
     * @param string $option
     * @param string|null $parent
     *
     * @return string
     */
    public static function getOptionName(string $option, ?string $parent = null): string
    {
        if ($parent !== null) {
            $option = $parent . '_' . $option;
        }

        return $option;
    }

    /**
     * Get single option value
     *
     * @param string $option
     * @param null $parent
     * @param null $default
     *
     * @return mixed
     */
    public static function getOption(string $option, $parent = null, $default = null)
    {
        $name = self::getOptionName($option, $parent);

        if (self::isOptionConstant($option)) {
            return constant($name);
        }

        return apply_filters(
            self::getOptionFilterName($option, $parent),
            get_option($name, $default)
        );
    }

    /**
     * Get option filter name
     * To define value of option programmatically
     *
     * @param $option
     * @param $parent
     * @return string
     */
    public static function getOptionFilterName(string $option, ?string $parent = null): string
    {
        $name = self::getOptionName($option, $parent);

        return 'option_value_' . $name;
    }

    /**
     * If option declared by constant
     *
     * @param string $option
     * @param string|null $parent
     *
     * @return bool
     */
    public static function isOptionConstant(string $option, ?string $parent = null): bool
    {
        return defined(self::getOptionName($option, $parent));
    }

    /**
     * Set single option
     *
     * @param string $option
     * @param string|null $parent
     * @param $value
     *
     * @return bool
     */
    public static function setOption(string $option, ?string $parent = null, $value = null): bool
    {
        $option = self::getOptionName($option, $parent);

        if (update_option($option, $value)) {
            return true;
        }

        return false;
    }

    /**
     * Get all params
     *
     * @return array
     * @see setParam
     * @see setParams
     *
     * @see getParam
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Get single parameter of option
     *
     * @param $param
     * @param null $default
     * @return mixed|null
     * @see getParams
     * @see setParams
     * @see setParam
     */
    public function getParam($param, $default = null)
    {
        return $this->params[$param] ?? $default;
    }

    /**
     * @param array $params
     * @see getParam
     * @see getParams
     *
     * @see setParam
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * @param string $key
     * @param $value
     * @see getParams
     *
     * @see setParams
     * @see getParam
     */
    public function setParam(string $key, $value): void
    {
        $this->params[$key] = $value;
    }

    /**
     * Get option form field
     *
     * @return string
     * @uses createField
     */
    public function getField(): string
    {
        return self::createField(
            [
                'main_params' => $this->getParam('main_params', false),
                'name' => $this->getParam('name', false),
                'value' => $this->getValue(),
                'default' => $this->getParam('default', null),
                'type' => $this->getParam('type', null),
                'debug_data' => $this->getParam('debug_data', null),
                /**
                 * Label
                 * */
                'label' => $this->getParam('label', $this->getParam('name', null)),
                'label_params' => $this->getParam('label_params', null),
                /**
                 * Description
                 * */
                'description' => $this->getParam('description', null),
                'description_params' => $this->getParam('description_params', null),
                /**
                 *
                 * */
                'parent' => $this->getParam('parent', null),
                'method' => $this->getParam('method', null),
                'values' => $this->getParam('values', []),
                'markup' => $this->getParam('markup', null),
                'template' => $this->getParam('template', null),
                'template_params' => $this->getParam('template_params', null),
                'field' => $this->getParam('field', null),
                'data' => $this->getParam('data', null),
                'disabled' => $this->getParam('disabled', false),
                'readonly' => $this->getParam('readonly', false),
            ]
        );
    }

    /**
     * Encode key of form field
     *
     * @param $key
     *
     * @return string
     */
    private static function encodeKey($key): string
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
        if ($str === '{{{boolean_true}}}') {
            return true;
        }

        if ($str === '{{{boolean_false}}}') {
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
        if ($str === '{{{null}}}') {
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
        if ($str === '{{{array}}}') {
            return [];
        }

        return $str;
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
     * Create field markup static method
     *
     * @param array $params
     *
     * @return string
     */
    private static function createField($params = []): string
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
            $markup = empty($values) ? self::MARKUP_TEXT : self::MARKUP_SELECT;
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

        switch ($type) {
            case self::TYPE_BOOL:
                $html .= HTML::tagOpen(
                    'input',
                    ['value' => '{{{boolean_false}}}', 'type' => 'hidden', 'name' => $name]
                );

                $html .= HTML::tagOpen(
                    'input',
                    $input_attrs + [
                        'value' => '{{{boolean_true}}}',
                        'type' => 'checkbox',
                        'name' => $name,
                        'data' => $data,
                        $value ? 'checked' : '',
                        $readonly_str,
                        $disabled_str
                    ]
                );
                break;
            case self::TYPE_OBJECT:
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
            case self::TYPE_GROUP:
                if (!empty($template)) {
                    $template_description = $template_params['description'] ?? null;
                    $template_attrs = $template_params['attrs'] ?? [];
                    $template_attrs['minimised'] = 'false';

                    if ($method === self::METHOD_SINGLE) {
                        foreach ($template as $key => $_field) {
                            $_field['name'] = $name . '[' . $key . ']';
                            $_field['value'] = $value[$key] ?? null;
                            $html .= self::createField($_field);
                        }
                    } elseif ($method === self::METHOD_MULTIPLE) {
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
                    if ($markup === null || $markup === self::MARKUP_SELECT) {
                        HTML::addClass($input_attrs['class'], 'full');

                        /**
                         * Handle empty null select value
                         * TODO: handle for all select cases
                         * */
                        $html .= HTML::tagOpen(
                            'input',
                            [
                                'type' => 'hidden',
                                'data' => $data,
                                $disabled_str,
                                'name' => $name,
                                'value' => $method === self::METHOD_MULTIPLE ? '{{{array}}}' : '{{{null}}}'
                            ]
                        );

                        $html .= HTML::tagOpen(
                            'select',
                            $input_attrs + [
                                'select2' => 'true',
                                'name' => $name . ($method === self::METHOD_MULTIPLE ? '[]' : ''),
                                $method === self::METHOD_MULTIPLE ? 'multiple' : '',
                                'data' => $data,
                                $disabled_str,
                                $readonly_str
                            ]
                        );
                        $open_tag_select = true;
                    }
                    $value = is_array($value) ? $value : [$value];

                    self::sortSelectValues($values, $value);

                    foreach ($values as $key => $_value) {
                        if ($markup === null || $markup === self::MARKUP_SELECT) {
                            $html .= HTML::tag(
                                'option',
                                $_value,
                                [
                                    'value' => $key,
                                    (($key === $value) || in_array($key, $value, true)) ? 'selected' : ''
                                ]
                            );
                        } elseif ($markup === self::MARKUP_CHECKBOX) {
                            if ($method === self::METHOD_MULTIPLE) {
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
                } elseif ($method === self::METHOD_MULTIPLE) {
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
                } elseif ($method !== self::METHOD_MULTIPLE) {
                    if (in_array($markup, [self::MARKUP_TEXT, self::MARKUP_NUMBER], true)) {
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
                                $readonly_str
                            ]
                        );
                    } elseif ($markup === self::MARKUP_TEXTAREA) {
                        $html .= HTML::tagOpen(
                            'textarea',
                            [
                                'class' => 'full',
                                'type' => $markup,
                                'placeholder' => $label,
                                'name' => $name,
                                'data' => $data,
                                $disabled_str,
                                $readonly_str
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
     * Dynamically get nonce field name by parent
     *
     * @param $parent
     *
     * @return string
     */
    private static function getNonceFieldName($parent): string
    {
        return $parent . '-save-form';
    }

    /**
     * Get form data from request
     *
     * @param $parent
     *
     * @return array|null
     */
    public static function getFormData(?string $parent = null): ?array
    {
        $nonce_field = Environment::post(self::getNonceFieldName($parent));

        $fields = wp_verify_nonce($nonce_field, $parent) ? Environment::post($parent) : null;

        if ($fields !== null) {
            $fields = self::decodeKeys($fields);
        }

        return $fields;
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

    /**
     * Print form nested elements
     *
     * @param $array
     * @param null $parent
     * @param string $route
     *
     * @return void
     */
    private static function printArrayList($array, $parent = null, $route = ''): void
    {
        $parent = $parent ?? 'Option';


        echo '<ul class="wp-lib-option-nested-fields ' . $parent . '-nested-fields">';

        $before = apply_filters('wp-lib-option/' . $parent . '/form-before-nested-fields', null, $route, $parent);
        echo empty($before) ? '' : '<li class="before">' . $before . '</li>';

        foreach ($array as $k => $v) {
            $_route = $route;
            $_route .= empty($_route) ? $k : '>' . $k;

            if (is_array($v)) {
                $label = apply_filters('wp-lib-option/' . $parent . '/form-nested-label', $k, $route, $parent);
                $label = str_replace('_', ' ', ucfirst($label));

                echo sprintf(
                    '<li route="%s" onclick="window.diazoxide.wordpress.option.toggleLabel(this, true)" class="label">%s</li>',
                    $_route,
                    $label
                );
                self::printArrayList($v, $parent, $_route);
                continue;
            }

            $content = apply_filters('wp-lib-option/' . $parent . '/form-nested-content', $v, $route, $parent);

            echo '<li>' . $content . '</li>';
        }

        $before = apply_filters('wp-lib-option/' . $parent . '/form-after-nested-fields', null, $route, $parent);
        echo empty($before) ? '' : '<li class="after">' . $before . '</li>';

        echo '</ul>';
    }

    /**
     * Array walk with route
     *
     * @param array $arr
     * @param callable $callback
     * @param array $route
     */
    private static function arrayWalkWithRoute(
        array &$arr,
        callable $callback,
        array $route = []
    ): void {
        foreach ($arr as $key => &$val) {
            $_route = $route;
            $_route[] = $key;
            if (is_array($val)) {
                self::arrayWalkWithRoute($val, $callback, $_route);
            } else {
                call_user_func_array($callback, [$key, &$val, $_route]);
            }
        }
    }

    /**
     * Print Form for options array
     *
     * @param $parent
     * @param $options
     * @param array|null $params
     * @see expandOptions
     *
     */
    public static function printForm($parent, $options, ?array $params = []): void
    {
        $form_data = self::getFormData($parent);

        if ($form_data) {
            foreach ($form_data as $key => $field) {
                self::setOption($key, $parent, $field);
            }

            $form_saved = $params['form_saved'] ?? null;
            if (is_callable($form_saved)) {
                $form_saved($form_data);
            }

            do_action('wp-lib-option/' . $parent . '/form-saved', $form_data);

            $success_message = $params['on_save_success_message'] ?? 'Settings saved!';

            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo $success_message; ?></p>
            </div>
            <?php
        }

        $title = $params['title'] ?? 'Configuration';

        $_fields = [];

        /**
         * Setting `parent` and `name` fields
         * Then generating fields HTML
         * */
        static::arrayWalkWithRoute(
            $options,
            static function ($key, $item, $route) use (&$_fields, $parent) {
                if ($item instanceof Option) {
                    $item->setParam('debug_data', [$route]);

                    if ($item->getParam('parent') === null) {
                        $item->setParam('parent', $parent);
                    }

                    if ($item->getParam('name') === null) {
                        $item->setParam('name', implode('>', $route));
                    }

                    $field = $item->getField();
                    $html = '<div class="section">' . $field . '</div>';
                    $temp = &$_fields;

                    foreach ($route as $_key) {
                        $temp = &$temp[$_key];
                    }
                    $temp[] = $html;
                    unset($temp);
                }
            }
        );

        self::printStyle();

        ?>
        <div class="wrap wp-lib-option-wrap <?php echo $parent; ?>-wrap">
            <h2><?php echo $title; ?></h2>
            <?php echo HTML::tagOpen(
                'form',
                [
                    'method' => 'post',
                    'action' => '',
                    'onsubmit' => 'return window.diazoxide.wordpress.option.formSubmit(this)',
                    'onchange' => 'return window.diazoxide.wordpress.option.formChange(this)',
                    'data' => [
                        'ajax_submit' => $params['ajax_submit'] ?? true,
                        'auto_submit' => $params['auto_submit'] ?? false
                    ]
                ]
            ); ?>


            <?php
            echo HTML::tag(
                'div',
                [
                    [
                        'div',
                        [
                            [
                                'a',
                                '&#8853; Expand all',
                                [
                                    'onclick' => 'window.diazoxide.wordpress.option.expandAll(this)',
                                    'class' => 'button button-default expand'
                                ]
                            ],
                            [
                                'a',
                                '&#8854; Collapse all',
                                [
                                    'onclick' => 'window.diazoxide.wordpress.option.collapseAll(this)',
                                    'class' => 'button button-default expand'
                                ]
                            ],
                        ],
                        ['class' => 'form-actions']
                    ],
                    [
                        'div',
                        [
                            ['span', 'Saving...', ['class' => 'saving hidden']],
                            ['span', 'Saved', ['class' => 'saved hidden']],
                            ['span', 'Failed', ['class' => 'failed hidden']],
                            ['span', 'Unsaved', ['class' => 'unsaved hidden']],
                        ],
                        ['class' => 'form-status']
                    ],
                ],
                ['class' => 'form-head']
            );
            ?>

            <!--<div class="form-actions">
                <button class="expand">Expand all</button>
            </div>-->
            <?php self::printArrayList($_fields, $parent); ?>
            <?php wp_nonce_field($parent, self::getNonceFieldName($parent)); ?>
            <?php submit_button(); ?>
            <?php echo HTML::tagClose('form'); ?>
        </div>

        <?php

        self::printScript();

        self::$assets_loaded = true;
    }

    /**
     * Print Select2 assets
     * From CDN
     *
     * @return void
     */
    private static function printSelect2Assets(): void
    {
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-sortable');
        ?>
        <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet"/>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
        <?php
    }

    /**
     * Print inline scripts
     *
     * @return void
     */
    private static function printScript(): void
    {
        if (!self::$assets_loaded) {
            self::printSelect2Assets();
            echo '<script type="application/javascript">' . file_get_contents(
                    __DIR__ . '/assets/script.js'
                ) . '</script>';
        }
    }

    /**
     * Print inline style
     *
     * @return void
     */
    private static function printStyle(): void
    {
        if (!self::$assets_loaded) {
            echo '<style type="text/css">' . file_get_contents(__DIR__ . '/assets/admin.css') . '</style>';
        }
    }

    /**
     * Retrieve array with options and return values
     *
     * @param array $options
     * @param string|null $parent
     * @return array
     * @see printForm
     */
    public static function expandOptions(array $options, ?string $parent = null): array
    {
        self::arrayWalkWithRoute(
            $options,
            static function ($key, &$item, $route) use ($parent) {
                if ($item instanceof self) {
                    if ($item->getParam('name') === null) {
                        $item->setParam('name', implode('>', $route));
                    }
                    if ($item->getParam('parent') === null) {
                        $item->setParam('parent', $parent);
                    }
                    $item = $item->getValue();
                }
            }
        );

        return $options;
    }
}
