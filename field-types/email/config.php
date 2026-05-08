<?php
/**
 * Конфигурация типа поля "Email"
 * 
 * @package FieldForm\FieldTypes\Email
 */

return [
    'name'        => __('Email', 'fieldform-builder'),
    'category'    => 'basic',
    'icon'        => 'dashicons-email',
    'has_options' => true,
    'default_args'=> [
        'placeholder' => '',
        'maxlength'   => '',
    ],
];
