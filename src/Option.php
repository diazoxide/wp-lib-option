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
        return Option::getOption(
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
        $name = Option::getOptionName($option, $parent);

        if (Option::isOptionConstant($option)) {
            return constant($name);
        }

        return apply_filters(
            Option::getOptionFilterName($option, $parent),
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
        $name = Option::getOptionName($option, $parent);

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
        return defined(Option::getOptionName($option, $parent));
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
        $option = Option::getOptionName($option, $parent);

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
        return Fields::createField(
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
                'required' => $this->getParam('required', false),
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
        $nonce_field = Environment::post(Option::getNonceFieldName($parent));

        $fields = wp_verify_nonce($nonce_field, $parent) ? Environment::post($parent) : null;

        if ($fields !== null) {
            $fields = Option::decodeKeys($fields);
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
            $key = Option::maybeDecodeKey($key);

            if (is_array($value)) {
                $value = Option::decodeKeys($value);
            } elseif (is_string($value)) {
                $value = stripslashes($value);
                $value = Option::maybeBoolean($value);
                $value = Option::maybeArray($value);
                $value = Option::maybeNull($value);
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
                Option::printArrayList($v, $parent, $_route);
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
                Option::arrayWalkWithRoute($val, $callback, $_route);
            } else {
                call_user_func_array($callback, [$key, &$val, $_route]);
            }
        }
    }

    /**
     * @param $parent
     * @param $params
     */
    private static function initFormSubmit($parent, ?array $params): void
    {
        $form_data = Option::getFormData($parent);

        if ($form_data) {
            foreach ($form_data as $key => $field) {
                Option::setOption($key, $parent, $field);
            }

            $form_saved = $params['form_saved'] ?? null;
            if (is_callable($form_saved)) {
                $form_saved($form_data);
            }

            do_action('wp-lib-option/' . $parent . '/form-saved', $form_data);

            $success_message = $params['on_save_success_message'] ?? 'Settings saved!';

            echo HTML::tag(
                'div',
                [
                    ['p', $success_message]
                ],
                ['class' => 'notice notice-success is-dismissible']
            );
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
        Option::initFormSubmit($parent, $params);

        $title = $params['title'] ?? 'Configuration';

        $_fields = [];

        /**
         * Setting `parent` and `name` fields
         * Then generate fields HTML
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

        Option::printStyle();

        $wrap_params = $params['wrap_params'] ?? [];

        $title_params = $params['title_params'] ?? [];

        $form_params = $params['form_params'] ?? [];

        $form_head_params = $params['form_head_params'] ?? [];

        HTML::addClass($wrap_params['class'], ['wrap wp-lib-option-wrap', $parent . '-wrap']);

        echo HTML::tagOpen('div', $wrap_params);

        echo HTML::tag('h2', $title, $title_params);

        echo HTML::tagOpen(
            'form',
            $form_params + [
                'method' => 'post',
                'action' => '',
                'onsubmit' => 'return window.diazoxide.wordpress.option.formSubmit(this)',
                'onchange' => 'return window.diazoxide.wordpress.option.formChange(this)',
                'data' => [
                    'ajax_submit' => $params['ajax_submit'] ?? true,
                    'auto_submit' => $params['auto_submit'] ?? false
                ]
            ]
        );

        Option::printFormHead($form_head_params);

        Option::printArrayList($_fields, $parent);

        wp_nonce_field($parent, Option::getNonceFieldName($parent));

        submit_button();

        echo HTML::tagClose('form');

        echo HTML::tagClose('div');

        Option::printScript();

        Option::$assets_loaded = true;
    }

    /**
     * @param $form_head_params
     */
    private static function printFormHead($form_head_params): void
    {
        HTML::addClass($form_head_params['class'], ['form-head']);

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
            $form_head_params
        );
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
        if (!Option::$assets_loaded) {
            Option::printSelect2Assets();
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
        if (!Option::$assets_loaded) {
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
        Option::arrayWalkWithRoute(
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
