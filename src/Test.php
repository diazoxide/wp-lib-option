<?php


namespace diazoxide\wp\lib\option;

use diazoxide\wp\lib\option\v2\Option;

class Test
{

    public static function testFormPrint(): void
    {
        $settings = [
            'test_object' => [
                'test_object_1' => new Option(
                    [
                        'type' => Option::TYPE_OBJECT,
                        'template' => [
                            'a_1' => ['type' => Option::TYPE_TEXT],
                            'a_2' => ['values'=>['asd','qwe']],
                        ]
                    ]
                )
            ],
            'test_groups' => [
                'test_group_single' => new Option(
                    [
                        'type' => Option::TYPE_GROUP,
                        'template' => [
                            'a_1' => ['type' => Option::TYPE_TEXT],
                            'a_2' => [],
                        ]
                    ]
                ),
                'test_group_multiple' => new Option(
                    [
                        'type' => Option::TYPE_GROUP,
                        'method' => Option::METHOD_MULTIPLE,
                        'template' => [
                            'a_1' => ['type' => Option::TYPE_TEXT],
                            'a_2' => [],
                        ]
                    ]
                ),
            ],
            'test_label' => [
                'field_1' => new Option(),
                'field_2' => new Option(),
            ],
            'text_field' => new Option(),
            'text_field_large' => new Option(
                [
                    'markup' => Option::MARKUP_TEXTAREA
                ]
            ),
            'select_field' => new Option(
                [
                    'values' => ['asd', 'qwe']
                ]
            ),
            'select_field_multiple' => new Option(
                [
                    'values' => ['asd', 'qwe'],
                    'method' => Option::METHOD_MULTIPLE,
                ]
            ),
            'checkbox_field_multiple' => new Option(
                [
                    'markup' => Option::MARKUP_CHECKBOX,
                    'values' => ['asd', 'qwe'],
                    'method' => Option::METHOD_MULTIPLE,
                ]
            ),
            'checkbox_field' => new Option(
                [
                    'markup' => Option::MARKUP_CHECKBOX,
                    'values' => ['asd', 'qwe'],
                    'method' => Option::METHOD_SINGLE,
                ]
            )
        ];

        Option::printForm('wp-lib-option-test-settings-3', $settings);
    }

}