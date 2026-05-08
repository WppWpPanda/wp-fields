<?php
/**
 * Активатор плагина - создание таблиц БД
 * 
 * @package FieldForm\Includes
 */

namespace FieldForm\Includes;

class FieldForm_Activator {
    
    /**
     * Метод активации плагина
     */
    public static function activate() {
        self::create_database_tables();
        self::set_default_options();
        
        flush_rewrite_rules();
    }
    
    /**
     * Создание таблиц базы данных
     */
    private static function create_database_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Таблица форм
        $table_forms = $wpdb->prefix . 'fieldform_forms';
        $sql_forms = "CREATE TABLE $table_forms (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            shortcode varchar(50) NOT NULL,
            settings longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY shortcode (shortcode)
        ) $charset_collate;";
        
        // Таблица полей форм
        $table_fields = $wpdb->prefix . 'fieldform_fields';
        $sql_fields = "CREATE TABLE $table_fields (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            form_id bigint(20) NOT NULL,
            field_type varchar(50) NOT NULL,
            label varchar(255) NOT NULL,
            name varchar(100) DEFAULT NULL,
            required tinyint(1) DEFAULT 0,
            sort_order int(11) DEFAULT 0,
            options longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY form_id (form_id),
            KEY sort_order (sort_order)
        ) $charset_collate;";
        
        // Таблица отправленных данных
        $table_submissions = $wpdb->prefix . 'fieldform_submissions';
        $sql_submissions = "CREATE TABLE $table_submissions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            form_id bigint(20) NOT NULL,
            data longtext,
            ip varchar(45) DEFAULT '',
            user_agent varchar(255) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY form_id (form_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        dbDelta($sql_forms);
        dbDelta($sql_fields);
        dbDelta($sql_submissions);
    }
    
    /**
     * Установка опций по умолчанию
     */
    private static function set_default_options() {
        if (!get_option('fieldform_settings')) {
            $default_settings = [
                'default_notification_email' => get_option('admin_email'),
                'enable_recaptcha' => false,
                'recaptcha_site_key' => '',
                'recaptcha_secret_key' => '',
                'enable_honeypot' => true,
            ];
            
            add_option('fieldform_settings', $default_settings);
        }
        
        // Версия плагина для отслеживания обновлений
        add_option('fieldform_version', FIELDFORM_VERSION);
    }
}
