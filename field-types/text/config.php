<?php
/**
 * Конфигурация типа поля "Text"
 * 
 * @package FieldForm\FieldTypes\Text
 */

return [
    'name'        => __('Текстовое поле', 'fieldform-builder'),
    'category'    => 'basic',
    'icon'        => 'dashicons-editor-text',
    'has_options' => true,
    'default_args'=> [
        'placeholder' => '',
        'maxlength'   => '',
        'size'        => '',
    ],
];
