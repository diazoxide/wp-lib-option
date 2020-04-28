<?php

namespace diazoxide\wp\lib\option;

use diazoxide\helpers\Environment;
use diazoxide\helpers\HTML;
use diazoxide\helpers\URL;
use diazoxide\wp\lib\option\fields\Input;
use Exception;

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
     * @var bool
     * */
    protected static $test_form_printed;

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
        return static::getOption(
            $this->getParam('name', null),
            $this->getParam('parent', null),
            $this->getParam('default', null),
            $this->getParam('serialize', null)
        );
    }

    /**
     * Get Option value
     *
     * @param mixed $value
     * @return mixed
     */
    public function setValue($value)
    {
        return static::setOption(
            $this->getParam('name', null),
            $this->getParam('parent', null),
            $value,
            $this->getParam('serialize', null)
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
     * @param bool $serialize
     * @return mixed
     */
    public static function getOption(string $option, $parent = null, $default = null, $serialize = false)
    {
        $name = static::getOptionName($option, $parent);

        if (static::isOptionConstant($option)) {
            return constant($name);
        }

        return apply_filters(
            static::getOptionFilterName($option, $parent),
            $serialize ? get_option($name, [])[0] ?? $default : get_option($name, $default)
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
        $name = static::getOptionName($option, $parent);

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
        return defined(static::getOptionName($option, $parent));
    }

    /**
     * Set single option
     *
     * @param string $option
     * @param string|null $parent
     * @param $value
     *
     * @param bool $serialize
     * @return bool
     */
    public static function setOption(string $option, ?string $parent = null, $value = null, $serialize = false): bool
    {
        $option = static::getOptionName($option, $parent);

        if (update_option($option, $serialize ? [$value] : $value)) {
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
     * @throws Exception
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
        $nonce_field = Environment::post(static::getNonceFieldName($parent));

        $fields = wp_verify_nonce($nonce_field, $parent) ? Environment::post($parent) : null;

        if ($fields !== null) {
            $fields = Fields::decodeKeys($fields);
        }

        return $fields;
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
                static::printArrayList($v, $parent, $_route);
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
                static::arrayWalkWithRoute($val, $callback, $_route);
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
        $form_data = static::getFormData($parent);

        if ($form_data) {
            foreach ($form_data as $key => $field) {
                static::setOption($key, $parent, $field);
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
     * Print test form
     *
     * @return void
     * @since 23042020
     */
    public static function printTestForm(): void
    {
        static::$test_form_printed = true;
        Test::testFormPrint();
    }

    /**
     * Print Form for options array
     *
     * @param $parent
     * @param $options
     * @param array|null $params
     * @throws Exception
     * @see expandOptions
     * @noinspection PhpUnusedParameterInspection
     */
    public static function printForm($parent, $options, ?array $params = []): void
    {
        /**
         * Test form. Only for development
         *
         * @since 23042020
         * */
        if (
            static::$test_form_printed !== true
            && Environment::get('wp-lib-option-test') !== null
        ) {
            static::printTestForm();
            return;
        }

        static::initFormSubmit($parent, $params);

        $serialize = $params['serialize'] ?? false;

        $title = $params['title'] ?? 'Configuration';

        $wrap_params = $params['wrap_params'] ?? [];

        $title_params = $params['title_params'] ?? [];

        $form_params = $params['form_params'] ?? [];

        $form_head_params = $params['form_head_params'] ?? [];

        $_fields = [];

        $is_export = wp_verify_nonce(Environment::get($parent), 'export');

        $exported_data = $is_export ? [] : null;

        $is_import_submit = !$is_export && wp_verify_nonce(Environment::post($parent), 'import-submit');

        $imported_data = $is_import_submit ? Environment::post('data') : null;
        $imported_data = $imported_data === null ? null : base64_decode($imported_data);
        $imported_data = $imported_data === null ? null : unserialize($imported_data, [static::class]);

        $is_import = $imported_data === null && wp_verify_nonce(Environment::get($parent), 'import');

        /**
         * Setting `parent` and `name` fields
         * Then generate fields HTML
         * */
        static::arrayWalkWithRoute(
            $options,
            static function ($key, $item, $route) use (
                &$_fields,
                $parent,
                &$exported_data,
                $imported_data,
                $serialize
            ) {
                if ($item instanceof Option) {
                    $item->setParam('debug_data', [$route]);

                    if ($item->getParam('parent') === null) {
                        $item->setParam('parent', $parent);
                    }

                    if ($item->getParam('name') === null) {
                        $item->setParam('name', implode('>', $route));
                    }

                    if ($item->getParam('serialize') === null) {
                        $item->setParam('serialize', $serialize);
                    }

                    if ($exported_data !== null) {
                        $exported_data[$item->getParam('name')] =
                            [
                                md5(serialize($item)),
                                $item->getValue()
                            ];
                    }

                    if ($imported_data !== null) {
                        $imported = $imported_data[$item->getParam('name')];
                        if (($imported[0] ?? null) === md5(serialize($item))) {
                            $item->setValue($imported[1] ?? null);
                        }
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

        static::printStyle();

        HTML::addClass($wrap_params['class'], ['wrap wp-lib-option-wrap', $parent . '-wrap']);

        echo HTML::tagOpen('div', $wrap_params);

        echo HTML::tag('h2', $title, $title_params);

        if ($imported_data !== null) {
            echo HTML::tag('h3', 'Your data successfully imported.');
        }

        if ($is_export) {
            $exported_data = serialize($exported_data);
            $exported_data = base64_encode($exported_data);

            echo HTML::tag('h4', 'Your export data is ready to download');

            echo HTML::tag(
                'a',
                'Click to download',
                [
                    'download' => $parent . '-' . time() . '-export.dat',
                    'href' => 'data:application/octet-stream;charset=utf-8;base64,' . base64_encode($exported_data),
                    'class' => 'button button-primary'
                ]
            );
        }

        if ($is_import) {
            echo HTML::tagOpen('form', ['method' => 'post', 'action' => '']);
            wp_nonce_field('import-submit', $parent);
            echo HTML::tag(
                'div',
                [
                    ['p', 'Import your exported data. Just paste here your downloaded file content.'],
                    [
                        'div',
                        [
                            [
                                'textarea',
                                '',
                                ['placeholder' => 'Paste here', 'name' => 'data', 'cols' => '55', 'rows' => '6']
                            ]
                        ]
                    ],
                    ['button', 'Import', ['type' => 'submit', 'class' => 'button button-primary']]
                ]
            );

            echo HTML::tagClose('form');
        }

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

        static::printFormHead($parent, $form_head_params);

        static::printArrayList($_fields, $parent);

        wp_nonce_field($parent, static::getNonceFieldName($parent));

        echo (new Input(
            [
                'type' => 'submit',
                'name' => $parent . '-form-submit',
                'value' => 'Save Changes',
                'attrs' => ['class' => 'button button-primary']
            ]
        ))->get();

        //submit_button();

        echo HTML::tagClose('form');

        echo HTML::tagClose('div');

        static::printScript();

        self::$assets_loaded = true;
    }

    /**
     * @param string $parent
     * @param array $form_head_params
     */
    private static function printFormHead(string $parent, array $form_head_params): void
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
                        [
                            'a',
                            'Export data',
                            [
                                'href' => URL::addQueryVars(
                                    URL::getCurrent(),
                                    $parent,
                                    wp_create_nonce('export')
                                ),
                                'class' => 'button button-primary export'
                            ]
                        ],
                        [
                            'a',
                            'Import data',
                            [
                                'href' => URL::addQueryVars(
                                    URL::getCurrent(),
                                    $parent,
                                    wp_create_nonce('import')
                                ),
                                'class' => 'button button-primary export'
                            ]
                        ],

                    ],
                    ['class' => 'form-actions']
                ],
                [
                    'div',
                    [
                        [
                            'button',
                            [
                                ['span', 'Save Changes', ['class' => 'save']],
                                ['span', 'Saving...', ['class' => 'saving hidden']],
                                ['span', 'Saved', ['class' => 'saved hidden']],
                                ['span', 'Failed', ['class' => 'failed hidden']],
                                [
                                    'span',
                                    'Unsaved',
                                    ['class' => 'unsaved hidden']
                                ],
                            ],
                            ['type' => 'submit', 'class' => 'button button-default']


                        ],
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
        if (!self::$assets_loaded) {
            static::printSelect2Assets();
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
     * @param array $params : Valid params
     *                        `bool` `serialize` `false`
     * @return array
     * @see printForm
     * @noinspection PhpUnusedParameterInspection
     */
    public static function expandOptions(array $options, ?string $parent = null, array $params = []): array
    {
        static::arrayWalkWithRoute(
            $options,
            static function ($key, &$item, $route) use ($parent, $params) {
                if ($item instanceof self) {
                    if ($item->getParam('name') === null) {
                        $item->setParam('name', implode('>', $route));
                    }
                    if ($item->getParam('parent') === null) {
                        $item->setParam('parent', $parent);
                    }
                    if ($item->getParam('serialize') === null) {
                        $item->setParam('serialize', $params['serialize'] ?? false);
                    }
                    $item = $item->getValue();
                }
            }
        );

        return apply_filters('wp-lib-option/' . $parent . '/expanded-option', $options);
    }
}
