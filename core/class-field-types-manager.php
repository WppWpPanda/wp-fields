<?php
/**
 * Менеджер типов полей - обнаружение и регистрация всех доступных типов
 * 
 * @package FieldForm\Core
 */

namespace FieldForm\Core;

class Field_Types_Manager {
    
    /**
     * Зарегистрированные типы полей
     * @var array
     */
    private static $field_types = [];
    
    /**
     * Инициализация менеджера
     */
    public static function init() {
        self::scan_and_register_field_types();
        
        // Хук для программной регистрации типов полей
        add_action('fieldform/register_custom_field_type', [__CLASS__, 'register_field_type'], 10, 2);
    }
    
    /**
     * Сканирование папки field-types и регистрация найденных типов
     */
    private static function scan_and_register_field_types() {
        $field_types_dir = FIELDFORM_PLUGIN_DIR . 'field-types/';
        
        if (!is_dir($field_types_dir)) {
            return;
        }
        
        $directories = glob($field_types_dir . '*', GLOB_ONLYDIR);
        
        if (empty($directories)) {
            return;
        }
        
        foreach ($directories as $dir) {
            $type_name = basename($dir);
            
            // Проверка наличия обязательных файлов
            $config_file = $dir . '/config.php';
            $field_file = $dir . '/field.php';
            
            if (!file_exists($config_file) || !file_exists($field_file)) {
                continue;
            }
            
            // Загрузка класса поля
            require_once $field_file;
            
            // Получение имени класса из config
            $config = include $config_file;
            
            // Попытка найти класс поля (имя класса должно быть Field_{Type})
            $class_name = 'FieldForm_Field_' . ucfirst($type_name);
            
            if (class_exists($class_name)) {
                $field_instance = new $class_name();
                
                if ($field_instance instanceof Abstract_Field_Type) {
                    self::$field_types[$type_name] = [
                        'type' => $type_name,
                        'class' => $class_name,
                        'instance' => $field_instance,
                        'config' => $config,
                    ];
                    
                    // Хук после регистрации типа поля
                    do_action('fieldform/after_field_registered', $type_name, $field_instance);
                }
            }
        }
    }
    
    /**
     * Программная регистрация типа поля
     * @param string $type Тип поля
     * @param Abstract_Field_Type $instance Экземпляр класса поля
     */
    public static function register_field_type($type, $instance) {
        if (!$instance instanceof Abstract_Field_Type) {
            return;
        }
        
        $config = $instance->get_config();
        
        self::$field_types[$type] = [
            'type' => $type,
            'class' => get_class($instance),
            'instance' => $instance,
            'config' => $config,
        ];
    }
    
    /**
     * Получить все зарегистрированные типы полей
     * @return array
     */
    public static function get_all_field_types() {
        return apply_filters('fieldform/allowed_field_types', self::$field_types);
    }
    
    /**
     * Получить конкретный тип поля
     * @param string $type Тип поля
     * @return array|null
     */
    public static function get_field_type($type) {
        if (isset(self::$field_types[$type])) {
            return self::$field_types[$type];
        }
        return null;
    }
    
    /**
     * Получить экземпляр класса поля
     * @param string $type Тип поля
     * @return Abstract_Field_Type|null
     */
    public static function get_field_instance($type) {
        $field_type = self::get_field_type($type);
        
        if ($field_type && isset($field_type['instance'])) {
            return $field_type['instance'];
        }
        
        return null;
    }
    
    /**
     * Получить типы полей, сгруппированные по категориям
     * @return array
     */
    public static function get_field_types_grouped() {
        $grouped = [];
        $field_types = self::get_all_field_types();
        
        foreach ($field_types as $type => $data) {
            $category = isset($data['config']['category']) ? $data['config']['category'] : 'basic';
            
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            
            $grouped[$category][$type] = $data;
        }
        
        return $grouped;
    }
}
