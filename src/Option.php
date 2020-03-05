<?php


namespace diazoxide\wp\lib\option;


class Option
{
    private $_name;
    private $_default;

    const TYPE_BOOL = 'bool';
    const TYPE_TEXT = 'text';
    const TYPE_OBJECT = 'object';
    const TYPE_GROUP = 'group';

    const MARKUP_CHECKBOX = 'checkbox';
    const MARKUP_TEXT = 'text';
    const MARKUP_TEXTAREA = 'textarea';
    const MARKUP_NUMBER = 'number';
    const MARKUP_SELECT = 'select';

    const METHOD_SINGLE = 'single';
    const METHOD_MULTIPLE = 'multiple';

    private $_params = [];

    public function __construct($_name, $_default = null, $_params = [])
    {
        $this->setName($_name);
        $this->setDefault($_default);
        $this->setParams($_params);
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->_name = $name;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->_default;
    }

    /**
     * @param mixed $default
     */
    public function setDefault($default): void
    {
        $this->_default = $default;
    }

    /**
     * @return array|mixed|void
     */
    public function getValue()
    {
        return self::getOption($this->getName(), $this->getParam('parent', null), $this->getDefault());
    }

    /**
     * @param $option
     * @param null $parent
     *
     * @return string
     */
    public static function getOptionName($option, $parent = null)
    {
        if ($parent !== null) {
            $option = $parent . '_' . $option;
        }

        return $option;
    }

    /**
     * @param string $option
     * @param null $parent
     * @param null $default
     *
     * @return array|mixed|void
     */
    public static function getOption($option, $parent = null, $default = null)
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

    public static function getOptionFilterName($option, $parent)
    {
        $name = self::getOptionName($option, $parent);

        return 'option_value_' . $name;
    }

    /**
     * @param $option
     *
     * @param null $parent
     *
     * @return bool
     */
    public static function isOptionConstant($option, $parent = null)
    {
        return defined(self::getOptionName($option, $parent));
    }

    /**
     * @param $option
     * @param null $parent
     * @param $value
     *
     * @return bool
     */
    public static function setOption($option, $parent = null, $value = null)
    {
        $option = self::getOptionName($option, $parent);

        if (update_option($option, $value)) {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->_params;
    }

    public function getParam($param, $default = null)
    {
        return $this->_params[$param] ?? $default;
    }

    /**
     * @param array $params
     */
    public function setParams(array $params): void
    {
        $this->_params = $params;
    }

    /**
     * @return string
     */
    public function getField(): string
    {
        return self::_getField(
            [
                'main_params' => $this->getParam('main_params', false),
                'name' => $this->getName(),
                'value' => $this->getValue(),
                'type' => $this->getParam('type', null),
                'label' => $this->getParam('label', $this->getName()),
                'description' => $this->getParam('description', null),
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
     * @param $key
     *
     * @return string
     */
    private static function _encodeKey($key): string
    {
        return "{{encode_key}}" . base64_encode($key);
    }

    /**
     * @param $str
     *
     * @return false|string|string[]|null
     */
    private static function _maybeDecodeKey($str)
    {
        if (strpos($str, '{{encode_key}}') === 0) {
            $str = preg_replace('/^{{encode_key}}/', '', $str);

            return base64_decode($str);
        }

        return $str;
    }

    /**
     * @param $str
     *
     * @return bool|string
     */
    private static function _maybeBoolean($str)
    {
        if ($str == '{{boolean_true}}') {
            return true;
        } elseif ($str == '{{boolean_false}}') {
            return false;
        }

        return $str;
    }

    /*private static function _getDataString(array $data): string
    {
        return implode(' ', array_map(
            function ($k, $v) {
                if (is_string($v)) {
                    $v = htmlspecialchars($v);
                }

                return 'data-' . $k . '="' . $v . '"';
            },
            array_keys($data), $data
        ));
    }*/


    /**
     * Array to html attributes string
     *
     * @param $data
     * @param string|null $parent
     *
     * @return string
     */
    private static function _getAttrsString($data, ?string $parent = null): string
    {
        return implode(
            ' ',
            array_map(
                function ($k, $v) use ($parent) {
                    if (is_string($v)) {
                        $v = htmlspecialchars($v);
                        if ($parent == null && is_int($k)) {
                            return $v;
                        }
                        $k = ($parent ? $parent . '-' : '') . $k;

                        return $k . '="' . $v . '"';
                    } elseif (is_array($v)) {
                        return self::_getAttrsString($v, $k);
                    } elseif (empty($v)) {
                        $k = ($parent ? $parent . '-' : '') . $k;

                        return $k . '=""';
                    } else {
                        $k = ($parent ? $parent . '-' : '') . $k;

                        return $k . '="' . json_encode($v) . '"';
                    }
                },
                array_keys($data),
                $data
            )
        );
    }

    /**
     * @param string $tag
     * @param array|null $attrs
     *
     * @return string
     */
    private static function _tagOpen(string $tag, ?array $attrs = null): string
    {
        if ($attrs !== null) {
            $attrs = self::_getAttrsString($attrs);
        }

        return sprintf(
            '<%s%s>',
            $tag,
            !empty($attrs) ? ' ' . $attrs : ''
        );
    }

    /**
     * @param string $tag
     *
     * @return string
     */
    private static function _tagClose(string $tag): string
    {
        return sprintf('</%s>', $tag);
    }

    /**
     * @param string $tag
     * @param string|null $content
     * @param array|null $attrs
     *
     * @return string
     */
    private static function _tag(string $tag, ?string $content = '', ?array $attrs = []): string
    {
        $html = self::_tagOpen($tag, $attrs);
        $html .= $content;
        $html .= self::_tagClose($tag);

        return $html;
    }

    /**
     * @param string $content
     * @param array $attrs
     *
     * @return string
     */
    private static function _group(string $content, array $attrs = []): string
    {
        $html = self::_tagOpen('div', ['class' => 'group'] + $attrs);
        $html .= $content;
        $html .= self::_tagClose('div');

        return $html;
    }


    /**
     * @param array|null $buttons
     *
     * @return string
     */
    private static function _itemButtons(?array $buttons = null): string
    {
        $html = self::_tagOpen('div',['class'=>'buttons']);

        if($buttons == null){
            $buttons = ['duplicate','minimise','remove'];
        }
        foreach ($buttons as $button){
            $fn_name = "_".$button.'Button';
            $html.=call_user_func([self::class,$fn_name]);
        }

        $html .= self::_tagClose('div');

        return $html;
    }

    /**
     * @return string
     */
    private static function _removeButton(): string
    {
        return self::_tag(
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
     * @return string
     */
    private static function _minimiseButton(): string
    {
        return self::_tag(
            'button',
            '-',
            [
                'class' => 'item-button minimise',
                'onclick' => 'diazoxide.wordpress.option.minimiseItem(this)',
                'type' => 'button',
                'title' => 'Minimise item'
            ]
        );
    }

    /**
     * @return string
     */
    private static function _duplicateButton(): string
    {
        return self::_tag(
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
     * @param array $params
     *
     * @return string
     */
    private static function _getField($params = []): string
    {
        $main_params = $params['main_params'] ?? [];

        $parent = $params['parent'] ?? null;
        $description = $params['description'] ?? null;
        $label = $params['label'] ?? null;
        $type = $params['type'] ?? null;
        $method = $params['method'] ?? null;
        $values = $params['values'] ?? [];
        $markup = $params['markup'] ?? null;
        /**
         * Automatically select markup type when it missing
         * */
        if ($markup == null) {
            $markup = empty($values) ? self::MARKUP_TEXT : self::MARKUP_SELECT;
        }

        $template = $params['template'] ?? null;
        $template_params = $params['template_params'] ?? [];
        $field = $params['field'] ?? null;

        $value = $params['value'] ?? null;
        $name = $params['name'] ?? null;

        $input_attrs = $params['input_attrs'] ?? [];

        if ($parent != null) {
            $name = $parent . '[' . $name . ']';
        }

        $disabled = $params['disabled'] ?? false;
        $disabled_str = $disabled ? "disabled" : "";
        $readonly = $params['readonly'] ?? false;
        $readonly_str = $readonly ? "readonly" : "";

        $data = $params['data'] ?? [];
        $data['name'] = $data['name'] ?? $name;


        if (is_callable($description)) {
            $description = call_user_func($description, $params);
        }

        $html = '';

        switch ($type) {
            case self::TYPE_BOOL:
                $html .= self::_tagOpen(
                    'input',
                    ['value' => '{{boolean_false}}', 'type' => 'hidden', 'name' => $name]
                );

                $html .= self::_tagOpen(
                    'input',
                    $input_attrs + [
                        'class' => implode(' ', [$type, $method]),
                        'value' => '{{boolean_true}}',
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

                if ($template != null && !empty($template)) {
                    foreach ($value as $key => $_value) {
                        $_html = '';

                        foreach ($template as $_key => $_field) {
                            $_field['value'] = $_value[$_key] ?? null;
                            $_field['data']['name'] = $name . '[{{encode_key}}]' . '[' . $_key . ']';
                            $_field['name'] = $name . '[' . self::_encodeKey($key) . ']' . '[' . $_key . ']';
                            $_html .= self::_getField($_field);
                        }

                        $html .= self::_group(
                            implode(
                                '',
                                [
                                    self::_tagOpen(
                                        'input',
                                        [
                                            'class' => 'key full',
                                            'type' => 'text',
                                            'placeholder' => $label,
                                            'value' => $key,
                                            'onchange' => $on_change
                                        ]
                                    ),
                                    self::_group($_html),
                                    self::_itemButtons()
                                ]
                            )
                        );
                    }
                } elseif ($field != null && !empty($field)) {
                    if (!empty($value)) {
                        foreach ($value as $key => $_value) {
                            $_field = $field;
                            $_field['value'] = $_value;
                            $_field['data']['name'] = $name . '[{{encode_key}}]';
                            $_field['name'] = $name . '[' . self::_encodeKey($key) . ']';
                            $html .= self::_group(
                                implode(
                                    '',
                                    [

                                        self::_tagOpen(
                                            'input',
                                            [
                                                'class' => 'key full',
                                                'type' => 'text',
                                                'placeholder' => $label,
                                                'value' => $key,
                                                'onchange' => $on_change
                                            ]
                                        ),
                                        self::_getField($_field),
                                        self::_itemButtons()
                                    ]
                                )
                            );
                        }
                    }
                }

                $_html = '';

                if ($template != null && !empty($template)) {
                    foreach ($template as $key => $_field) {
                        $_field['name'] = $name . '[{{encode_key}}]' . '[' . $key . ']';
                        $_field['disabled'] = true;
                        $_html .= self::_getField($_field);
                    }
                } elseif ($field != null && !empty($field)) {
                    $field['name'] = $name . '[{{encode_key}}]';
                    $field['disabled'] = true;
                    $_html .= self::_getField($field);
                }

                $html .= self::_group(
                    implode(
                        '',
                        [
                            self::_tagOpen(
                                'input',
                                [
                                    'class' => 'key full',
                                    'type' => 'text',
                                    'placeholder' => $label,
                                    'onchange' => $on_change
                                ]
                            ),
                            self::_group($_html),
                            self::_itemButtons(['duplicate','remove'])

                        ]
                    ),
                    ['new' => 'true', 'style' => 'display:none']
                );

                $html .= self::_group(
                    self::_tag(
                        'button',
                        '+ Add new',
                        [
                            'type' => 'button',
                            'class' => 'button button-primary',
                            'onclick' => "var c = this.parentElement.parentElement.querySelector(':scope>[new]').cloneNode(true); c.removeAttribute('new'); c.style.display=''; var e = c.querySelectorAll('[name]'); for( var i=0; i < e.length; i++){e[i].disabled = false;}; this.parentElement.parentElement.insertBefore(c,this.parentElement);"
                        ]
                    )
                );

                break;
            case self::TYPE_GROUP:
                if (!empty($template)) {
                    $html .= '';

                    if ($method == self::METHOD_SINGLE) {
                        foreach ($template as $key => $_field) {
                            $_field['name'] = $name . '[' . $key . ']';
                            $_field['value'] = $value[$key] ?? null;
                            $html .= self::_getField($_field);
                        }
                    } elseif ($method == self::METHOD_MULTIPLE) {
                        $last_key = 1;
                        if ($value != null) {
                            $last_key = count($value) + 1;
                            foreach ($value as $key => $_value) {
                                $__html = '';

                                foreach ($template as $_key => $_field) {
                                    $_field = $template[$_key];
                                    $_field['name'] = $name . '[' . $key . ']' . '[' . $_key . ']';
                                    $_field['value'] = $_value[$_key] ?? '';
                                    $__html .= self::_getField($_field);
                                }

                                $template_description = $template_params['description'] ?? null;
                                if (is_callable($template_description)) {
                                    $template_description = call_user_func($template_description, $key, $_value);
                                }

                                $template_description = $template_description != null ? sprintf(
                                    '<div class="description">%s</div>',
                                    $template_description
                                ) : '';
                                $__html .= $template_description;

                                $html .= self::_group(
                                    $__html . self::_itemButtons()

                                );
                            }
                        }

                        $__html = '';

                        foreach ($template as $key => $_field) {
                            $_field['name'] = $name . '[{{LAST_KEY}}]' . '[' . $key . ']';
                            $_field['disabled'] = true;
                            $__html .= self::_getField($_field);
                        }

                        $template_description = $template_params['description'] ?? null;
                        if (is_callable($template_description)) {
                            $template_description = call_user_func($template_description, null, null);
                        }

                        $template_description = $template_description != null ? sprintf(
                            '<div class="description">%s</div>',
                            $template_description
                        ) : '';
                        $__html .= $template_description;

                        $html .= self::_group(
                            $__html . self::_itemButtons(['remove']),
                            ['new' => 'true', 'style' => 'display:none']
                        );

                        $html .= self::_group(
                            self::_tag(
                                'button',
                                '+ Add new',
                                [
                                    'type' => 'button',
                                    'last-key' => $last_key,
                                    'class' => 'button button-primary',
                                    'onclick' => "var n = parseInt(this.getAttribute('last-key'))+1; this.setAttribute('last-key',n); var c = this.parentElement.parentElement.querySelector(':scope>[new]').cloneNode(true); c.removeAttribute('new'); c.style.display=''; var e = c.querySelectorAll('[name]'); for( var i=0; i < e.length; i++){e[i].disabled = false; e[i].name=(e[i].name).replace('{{LAST_KEY}}',n);}; this.parentElement.parentElement.insertBefore(c,this.parentElement);"
                                ]
                            )
                        );
                    }
                }
                break;
            default:
                if (!empty($values)) {
                    if ($markup == null || $markup == self::MARKUP_SELECT) {
                        $html .= self::_tagOpen(
                            'select',
                            $input_attrs + [
                                'class' => implode(
                                    ' ',
                                    [$type, $method, 'full']
                                ),
                                'name' => $name . ($method == self::METHOD_MULTIPLE ? '[]' : ''),
                                $method == self::METHOD_MULTIPLE ? 'multiple' : '',
                                'data' => $data,
                                $disabled_str,
                                $readonly_str
                            ]
                        );
                        $html .= self::_tag(
                            'option',
                            '-- Select --',
                            ['disabled' => true, 'selected' => true]
                        );
                        $open_tag_select = true;
                    }

                    foreach ($values as $key => $_value) {
                        if ($markup == null || $markup == self::MARKUP_SELECT) {
                            $html .= self::_tag(
                                'option',
                                $_value,
                                [
                                    'value' => $key,
                                    (($key == $value) || (is_array($value) && in_array(
                                                $key,
                                                $value
                                            ))) ? 'selected' : ''
                                ]
                            );
                        } elseif ($markup == self::MARKUP_CHECKBOX) {
                            if ($method == self::METHOD_MULTIPLE) {
                                $html .= self::_group(
                                    self::_tag(
                                        'label',
                                        self::_tagOpen(
                                            'input',
                                            [
                                                'type' => 'checkbox',
                                                'name' => $name . ($method == self::METHOD_MULTIPLE ? '[]' : ''),
                                                'value' => $key,
                                                'data' => $data,
                                                (($key == $value) || (is_array($value) && in_array(
                                                            $key,
                                                            $value
                                                        ))) ? 'checked' : '',
                                                $disabled_str,
                                                $readonly_str,
                                            ]
                                        ) . $_value
                                    )
                                );
                            } else {
                                $html .= self::_group(
                                    self::_tag(
                                        'label',
                                        self::_tagOpen(
                                            'input',
                                            [
                                                'type' => 'radio',
                                                'name' => $name,
                                                'value' => $key,
                                                (($key == $value) || (is_array($value) && in_array(
                                                            $key,
                                                            $value
                                                        ))) ? 'checked' : '',
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
                        $html .= self::_tagClose('select');
                    }
                } elseif ($method == self::METHOD_MULTIPLE) {

                    if (is_array($value)) {
                        foreach ($value as $key => $_value) {
                            if (!empty($_value)) {
                                $html .= self::_group(
                                    self::_tagOpen(
                                        'input',
                                        $input_attrs + [
                                            'name' => $name . '[]',
                                            'class' => 'full',
                                            'type' => $markup,
                                            'placeholder' => $label,
                                            'value' => $_value,
                                            $disabled_str,
                                            $readonly_str,
                                        ]
                                    ) . self::_itemButtons()
                                );
                            }
                        }
                    }

                    $html .= self::_group(
                        self::_tagOpen(
                            'input',
                            $input_attrs + [
                                'name' => $name . '[]',
                                'class' => 'full',
                                'type' => $markup,
                                'placeholder' => $label,
                                'disabled'
                            ]
                        ) . self::_itemButtons(['remove']),
                        [
                            'style' => 'display:none',
                            'new' => 'true',
                            'onclick' => "var e=this.querySelector('input[name]'); e.disabled = false; e.focus()"
                        ]
                    );

                    $html .= self::_group(
                        self::_tag(
                            'button',
                            '+ Add new',
                            [
                                'type' => 'button',
                                'class' => 'button button-primary',
                                'onclick' => "var c = this.parentElement.parentElement.querySelector('[new]').cloneNode(true); c.style.display=''; c.children[0].value=''; this.parentElement.parentElement.insertBefore(c,this.parentElement);"
                            ]
                        )
                    );
                } elseif ($method != self::METHOD_MULTIPLE) {

                    if (in_array($markup, [self::MARKUP_TEXT, self::MARKUP_NUMBER])) {
                        $html .= self::_tagOpen(
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
                    } elseif ($markup == self::MARKUP_TEXTAREA) {
                        $html .= self::_tagOpen(
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
                        $html .= self::_tagClose('textarea');
                    }
                } else {
                    $html .= self::_group("Not handled!");
                }
                break;
        }

        $main_params['class'] = $main_params['class'] ?? 'group';

        $html = self::_tag('div', $html, $main_params);

        if (!empty($label)) {
            $html = self::_tag('div', $label, ['class' => 'label']) . $html;
        }

        if (!empty($description)) {
            $html .= self::_tag('div', $description, ['class' => 'description']);;
        }

        return $html;
    }

    /**
     * @param $parent
     * @param string $method
     *
     * @return array|mixed|null
     */
    public static function getFormData($parent, $method = 'post')
    {
        if ($method == 'post') {
            $fields = $_POST[$parent] ?? null;
        } elseif ($method == 'get') {
            $fields = $_GET[$parent] ?? null;
        } else {
            $fields = null;
        }

        if ($fields !== null) {
            $fields = self::decodeKeys($fields);
        }

        return $fields;
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public static function decodeKeys(array $input): array
    {
        $return = array();
        foreach ($input as $key => $value) {
            $key = self::_maybeDecodeKey($key);

            if (is_array($value)) {
                $value = self::decodeKeys($value);
            } elseif (is_string($value)) {
                $value = stripslashes($value);
                $value = self::_maybeBoolean($value);
            }
            $return[$key] = $value;
        }

        return $return;
    }

    /**
     * @param $array
     * @param null $parent
     *
     * @return void
     */
    private static function printArrayList($array, $parent = null): void
    {
        $parent = $parent ?? 'Option';

        echo '<ul class="' . $parent . '-admin-nested-fields">';

        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $label = str_replace('_', ' ', ucfirst($k));

                echo '<li class="label">' . $label . "</li>";
                self::printArrayList($v, $parent);
                continue;
            }

            echo "<li>" . $v . "</li>";
        }

        echo "</ul>";
    }

    /**
     * @param array $arr
     * @param callable $callback
     * @param array $route
     */
    private static function arrayWalkWithRoute(
        array &$arr,
        callable $callback,
        array $route = []
    ): void
    {
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
     * @param $parent
     * @param $options
     * @param array|null $params
     */
    public static function printForm($parent, $options, ?array $params = []): void
    {
        $form_data = Option::getFormData($parent);

        if ($form_data) {
            foreach ($form_data as $key => $field) {
                self::setOption($key, $parent, $field);
            }

            $form_saved = $params['form_saved'] ?? null;
            if (is_callable($form_saved)) {
                call_user_func($form_saved, $form_data);
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

        static::arrayWalkWithRoute(
            $options,
            function ($key, $item, $route) use (&$_fields) {
                if ($item instanceof Option) {
                    array_pop($route);
                    $label = $item->getParam('label', $item->getName());
                    $description = $item->getParam('description', null);

                    if (is_callable($description)) {
                        $description = call_user_func($description, $key, $item, $route);
                    }

                    $field = $item->getField();
                    $html = '<div class="section">' . $field . '</div>';
                    $temp = &$_fields;
                    foreach ($route as $key) {
                        $temp = &$temp[$key];
                    }
                    $temp[] = $html;
                    unset($temp);
                }
            }
        );

        ?>
        <div class="wrap <?php echo $parent; ?>-wrap">
            <h2><?php echo $title; ?></h2>
            <form method="post" action="">
                <?php self::printArrayList($_fields, $parent); ?>
                <input type="hidden" name="<?php echo $parent; ?>-form" value="1">
                <?php submit_button(); ?>
            </form>
        </div>

        <?php self::printStyle($parent); ?>

        <?php self::printScript($parent); ?>

        <?php
    }

    /**
     * @param string $parent
     */
    public static function printScript($parent = ''): void
    {
        ?>
        <script type="application/javascript">
            (function () {
                let lists = document.querySelectorAll('.<?php echo $parent; ?>-admin-nested-fields>.<?php echo $parent; ?>-admin-nested-fields');
                for (let i = 0; i < lists.length; i++) {
                    let list = lists[i];
                    let label = list.previousSibling;
                    label.addEventListener("click", function () {
                        if (this.nextSibling.offsetParent === null) {
                            this.nextSibling.style.display = "block";
                            this.classList.add('open');
                        } else {
                            this.nextSibling.style.display = "none";
                            this.classList.remove('open');
                        }
                    });
                }

                if (!window.hasOwnProperty('diazoxide')) {
                    window.diazoxide = {};
                    if (!window.diazoxide.hasOwnProperty()) {
                        window.diazoxide.wordpress = {};
                        if (!window.diazoxide.wordpress.hasOwnProperty('option')) {
                            window.diazoxide.wordpress.option = {
                                removeItem: function (button) {
                                    if (confirm("Are you sure?")) {
                                        button.parentElement.parentElement.remove();
                                    }
                                },
                                duplicateItem: function (button) {
                                    let item = button.parentElement.parentElement;
                                    let clone = item.cloneNode(true);
                                    clone.classList.add('clone');
                                    item.classList.add('cloned');
                                    setTimeout(function () {
                                        clone.classList.remove('clone');
                                        item.classList.remove('cloned');
                                    }, 1000);
                                    item.parentElement.insertBefore(clone, item);
                                },
                                minimiseItem: function (button) {
                                    let item = button.parentElement.parentElement;
                                    if(item.classList.contains('minimised')){
                                        item.classList.remove('minimised');
                                        button.innerHTML = "-"
                                    } else{
                                        item.classList.add('minimised');
                                        button.innerHTML = "&#9634;"
                                    }
                                }
                            };
                        }
                    }
                }
            })();
        </script>
        <?php
    }

    public static function printStyle($parent = '')
    {
        $str = file_get_contents(__DIR__ . '/assets/admin.css');
        $str = str_replace("__PARENT_SLUG__", $parent, $str);

        echo '<style type="text/css">' . $str . '</style>';
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public static function expandOptions(array $options)
    {
        array_walk_recursive(
            $options,
            function (&$item, $key) {
                if ($item instanceof self) {
                    $item = $item->getValue();
                }
            }
        );

        return $options;
    }
}