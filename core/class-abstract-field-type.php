<?php
/**
 * Абстрактный класс для всех типов полей
 * 
 * @package FieldForm\Core
 */

namespace FieldForm\Core;

abstract class Abstract_Field_Type {
    
    /**
     * Конфигурация поля (из config.php)
     * @var array
     */
    protected $config = [];
    
    /**
     * Уникальный идентификатор типа поля
     * @var string
     */
    protected $type = '';
    
    /**
     * Конструктор
     */
    public function __construct() {
        $this->type = $this->get_type();
        $this->config = $this->load_config();
    }
    
    /**
     * Получить тип поля (имя папки)
     * @return string
     */
    abstract public function get_type();
    
    /**
     * Загрузить конфигурацию из config.php
     * @return array
     */
    protected function load_config() {
        $config_file = FIELDFORM_PLUGIN_DIR . 'field-types/' . $this->type . '/config.php';
        if (file_exists($config_file)) {
            return include $config_file;
        }
        return [];
    }
    
    /**
     * Получить конфигурацию поля
     * @return array
     */
    public function get_config() {
        return $this->config;
    }
    
    /**
     * Рендер настроек поля в админке (редактор форм)
     * @param int $field_id ID поля
     * @param array $saved_value Сохранённые значения настроек
     * @return string HTML разметка
     */
    abstract public function render_admin_options($field_id, $saved_value = []);
    
    /**
     * Рендер поля на фронтенде
     * @param array $field Настройки поля
     * @param mixed $value Значение поля
     * @return string HTML разметка
     */
    abstract public function render_frontend($field, $value = '');
    
    /**
     * Валидация значения поля на сервере
     * @param mixed $value Значение для проверки
     * @param array $field_settings Настройки поля
     * @return \WP_Error|true WP_Error с ошибками или true если всё ок
     */
    abstract public function validate($value, $field_settings);
    
    /**
     * Подготовка значения перед сохранением
     * @param mixed $value Значение
     * @param array $field_settings Настройки поля
     * @return mixed Подготовленное значение
     */
    public function sanitize($value, $field_settings) {
        return sanitize_text_field($value);
    }
    
    /**
     * Получить шаблон для рендера (с поддержкой переопределения в теме)
     * @param string $template_name Имя шаблона
     * @return string Путь к шаблону
     */
    protected function get_template_path($template_name = 'template.php') {
        $type = $this->get_type();
        
        // Проверка переопределения в теме
        $theme_template = get_stylesheet_directory() . '/fieldform/fields/' . $type . '/' . $template_name;
        if (file_exists($theme_template)) {
            return $theme_template;
        }
        
        // Шаблон по умолчанию
        $default_template = FIELDFORM_PLUGIN_DIR . 'field-types/' . $type . '/' . $template_name;
        if (file_exists($default_template)) {
            return $default_template;
        }
        
        return '';
    }
}
