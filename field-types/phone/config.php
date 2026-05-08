<?php
/**
 * Конфигурация типа поля "Phone"
 * 
 * @package FieldForm\FieldTypes\Phone
 */

return [
    'name'        => __('Телефон', 'fieldform-builder'),
    'category'    => 'basic',
    'icon'        => 'dashicons-phone',
    'has_options' => true,
    'default_args'=> [
        'placeholder'   => '+7 (___) ___-__-__',
        'mask'          => '+9 (999) 999-99-99',
        'country_code'  => '+7',
    ],
];
