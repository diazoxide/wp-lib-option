<?php

namespace diazoxide\wp\lib\option\fields;

use diazoxide\helpers\HTML;
use diazoxide\wp\lib\option\Fields;

class Choice extends Field
{

    public const MARKUP_CHECKBOX = 'checkbox';
    public const MARKUP_SELECT = 'select';

    /**
     * @var array
     */
    public $choices;

    /**
     * @var bool
     */
    public $multiple = false;

    /**
     * @var string
     */
    public $markup = self::MARKUP_SELECT;

    /**
     * @return bool
     */
    public function validate(): bool
    {
        if (
            $this->multiple === true
            && !is_array($this->value)
        ) {
            if ($this->value === null) {
                $this->value = [];
            } else {
                $this->errors[] = ['value_must_be_array', '`value` field must be array when multiple field is true.'];
                return false;
            }
        }

        if (!is_array($this->choices)) {
            $this->errors[] = ['choices_must_be_array', '`choices` field must be array.'];
            return false;
        }

        if (!in_array($this->markup, [self::MARKUP_SELECT, self::MARKUP_CHECKBOX], true)) {
            $this->markup = self::MARKUP_SELECT;
        }

        if ($this->multiple) {
            self::sortSelectValues($this->choices, $this->value);
        }

        return parent::validate();
    }

    /**
     * @return array
     */
    protected function requiredFields(): array
    {
        return array_merge(parent::requiredFields(), ['choices']);
    }

    /**
     * @return string
     */
    protected function template(): string
    {
        $html = '';

        $attrs = array_merge(
            $this->attrs,
            [
                'data' => $this->data,
                'name' => $this->name . ($this->multiple ? '[]' : ''),
                $this->disabled ? 'disabled' : '',
                $this->required ? 'required' : '',
                $this->readonly ? 'readonly' : '',
            ]
        );

        if ($this->markup === static::MARKUP_SELECT) {
            $html .= HTML::tagOpen(
                'select',
                array_merge(
                    $attrs,
                    [
                        'select2' => 'true',
                        $this->multiple ? 'multiple' : '',
                    ]
                )
            );
        }

        $this->choices = [null => 'Select value'] + $this->choices;

        foreach ($this->choices as $key => $_value) {
            $selected = (!$this->multiple && $key == $this->value)
                || ($this->multiple && in_array($key, $this->value));

            if ($this->markup === static::MARKUP_SELECT) {
                $html .= HTML::tag(
                    'option',
                    $_value,
                    [
                        'value' => $key,
                        $selected ? 'selected' : ''
                    ]
                );
            } elseif ($this->markup === static::MARKUP_CHECKBOX) {
                $html .= Fields::group(
                    HTML::tag(
                        'label',
                        HTML::tagOpen(
                            'input',
                            array_merge(
                                $attrs,
                                [
                                    'type' => $this->multiple ? 'checkbox' : 'radio',
                                    'value' => $key,
                                    $selected ? 'checked' : ''
                                ]
                            )
                        ) . $_value
                    )
                );
            }
        }

        if ($this->markup === static::MARKUP_SELECT) {
            $html .= HTML::tagClose('select');
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
}
