<?php


namespace diazoxide\wp\lib\option;

use diazoxide\helpers\HTML;
use diazoxide\wp\lib\option\v2\Option;

class Test
{
    private const NAME = 'wp-lib-option-test-settings-5';

    public static function testFormPrint(): void
    {
        $settings = [

            'test_nest' => [
                'test_nest_2' => [
                    'test_nest_3' => new Option(
                        [
                            'type' => Option::TYPE_BOOL
                        ]
                    )
                ]
            ],

            'test_bool' => new Option(
                [
                    'type' => Option::TYPE_BOOL
                ],
                $test_bool
            ),

            'test_number' => new Option(
                [
                    'type'       => Option::TYPE_NUMBER,
                    'depends_on' => [
                        [$test_bool, true]
                    ]
                ],
                $test_number
            ),

            'test_number_1' => new Option(
                [
                    'type'       => Option::TYPE_NUMBER,
                    'depends_on' => [
                        [$test_number, 3]
                    ]
                ],
                $test_number
            ),

            'test_number_2' => new Option(
                [
                    'type'       => Option::TYPE_NUMBER,
                    'depends_on' => [
                        [$test_number, 4]
                    ]
                ],
                $test_number
            ),

            'test_object'             => [
                'test_object_1' => new Option(
                    [
                        'type'     => Option::TYPE_OBJECT,
                        'template' => [
                            'a_0' => [
                                'type'     => Option::TYPE_GROUP,
                                'template' => [
                                    'b1' => [],
                                    'b2' => [],
                                ]
                            ],
                            'a_1' => ['type' => Option::TYPE_TEXT],
                            'a_2' => ['values' => ['asd', 'qwe']],
                        ]
                    ], $test_object_1
                ),

                'test_number_2' => new Option(
                    [
                        'type'       => Option::TYPE_NUMBER,
                        'depends_on' => [
                            [$test_object_1, 4]
                        ]
                    ],
                    $test_number
                ),

            ],
            'test_groups'             => [
                'test_group_single'   => new Option(
                    [
                        'type'     => Option::TYPE_GROUP,
                        'template' => [
                            'a_1' => ['type' => Option::TYPE_TEXT],
                            'a_2' => [],
                            'a_3' => ['type' => Option::TYPE_BOOL],
                        ]
                    ]
                ),
                'test_group_multiple' => new Option(
                    [
                        'type'     => Option::TYPE_GROUP,
                        'method'   => Option::METHOD_MULTIPLE,
                        'template' => [
                            'a_1' => ['type' => Option::TYPE_TEXT],
                            'a_2' => ['values' => ['asd', 'qwe']],
                            'a_3' => [
                                'type'   => Option::TYPE_GROUP,
                                'method' => Option::METHOD_MULTIPLE,

                                'template' => [
                                    'k' => ['values' => ['asd', 'qwe']],
                                    'z' => ['values' => ['asd', 'qwe']],
                                ]
                            ],
                        ]
                    ]
                ),
            ],
            'test_label'              => [
                'field_1' => new Option(),
                'field_2' => new Option(),
            ],
            'text_field'              => new Option(),
            'text_field_large'        => new Option(
                [
                    'markup' => Option::MARKUP_TEXTAREA
                ]
            ),
            'select_field'            => new Option(
                [
                    'values' => ['asd', 'qwe']
                ]
            ),
            'select_field_multiple'   => new Option(
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
            'radio_field'             => new Option(
                [
                    'markup' => Option::MARKUP_CHECKBOX,
                    'values' => ['asd' => 'asd', 'qwe' => 'qwe'],
                    'method' => Option::METHOD_SINGLE,
                ],
                $radio_field
            ),

            'asd_field' => new Option(
                [
                    'depends_on' => [
                        [$radio_field, 'asd']
                    ]
                ]
            ),
            'qwe_field' => new Option(),
        ];

        Option::printForm(self::NAME, $settings, ['serialize' => true, 'single_option' => true]);

        echo HTML::tagOpen('pre');

        /** @noinspection ForgottenDebugOutputInspection */
        var_dump(
            Option::expandOptions(
                $settings,
                self::NAME,
                [
                    'serialize'     => true,
                    'single_option' => true
                ]
            )
        );

        echo HTML::tagClose('pre');
    }

}