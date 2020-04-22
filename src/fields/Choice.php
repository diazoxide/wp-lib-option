<?php


namespace diazoxide\wp\lib\option\fields;


use diazoxide\helpers\HTML;
use diazoxide\wp\lib\option\Fields;

class Choice extends Field
{

    public const MARKUP_CHECKBOX = 'checkbox';
    public const MARKUP_SELECT = 'select';

    public $choices;

    public $multiple = false;

    public $markup = self::MARKUP_SELECT;

    public function validate(): bool
    {
        if ($this->multiple === true && !is_array($this->value)) {
            $this->errors[] = ['value_must_be_array', '`value` field must be array when multiple field is true.'];
            return false;
        }

        if (!is_array($this->choices)) {
            $this->errors[] = ['choices_must_be_array', '`choices` field must be array.'];
            return false;
        }

        if (!in_array(
            $this->markup,
            [self::MARKUP_SELECT, self::MARKUP_CHECKBOX],
            true
        )) {
            $this->markup = self::MARKUP_SELECT;
        }

        if ($this->multiple) {
            self::sortSelectValues($this->choices, $this->value);
        }

        return parent::validate();
    }

    protected function requiredFields(): array
    {
        return parent::requiredFields() + ['choices'];
    }

    protected function template(): string
    {
        $html = '';

        if ($this->markup === static::MARKUP_SELECT) {
            $html .= HTML::tagOpen(
                'select',
                $this->attrs + [
                    'select2' => 'true',
                    'name' => $this->name . ($this->multiple ? '[]' : ''),
                    $this->multiple ? 'multiple' : '',
                    'data' => $this->data,
                    $this->disabled ? 'disabled' : '',
                    $this->required ? 'required' : '',
                    $this->readonly ? 'readonly' : ''
                ]
            );
        }

        foreach ($this->choices as $key => $_value) {
            $selected = (!$this->multiple && $key === $this->value)
                || ($this->multiple && in_array( $key, $this->value,true) );

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
                            [
                                'type' => $this->multiple ? 'checkbox' : 'radio',
                                'name' => $this->name . ($this->multiple ? '[]' : ''),
                                'value' => $key,
                                'data' => $this->data,
                                $selected ? 'checked' : '',
                                $this->disabled ? 'disabled' : '',
                                $this->required ? 'required' : '',
                                $this->readonly ? 'readonly' : ''
                            ]
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