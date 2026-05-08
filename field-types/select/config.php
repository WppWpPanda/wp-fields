<?php
/**
 * Конфигурация типа поля "Select"
 * 
 * @package FieldForm\FieldTypes\Select
 */

return [
    'name'        => __('Выпадающий список', 'fieldform-builder'),
    'category'    => 'choice',
    'icon'        => 'dashicons-menu',
    'has_options' => true,
    'default_args'=> [
        'options'     => [],
        'placeholder' => '',
        'multiple'    => false,
    ],
];
