<?php
/**
 * Загрузчик плагина - регистрирует все хуки и компоненты
 * 
 * @package FieldForm\Includes
 */

namespace FieldForm\Includes;

use FieldForm\Core\Field_Types_Manager;

class FieldForm_Loader {
    
    /**
     * Массив хуков
     * @var array
     */
    protected $hooks = [];
    
    /**
     * Инициализация загрузчика
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Регистрация всех хуков
     */
    protected function init_hooks() {
        // Инициализация менеджера типов полей
        add_action('init', [Field_Types_Manager::class, 'init'], 1);
        
        // Подключение скриптов и стилей в админке
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Подключение скриптов и стилей на фронтенде
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        
        // Регистрация шорткода
        add_shortcode('fieldform', [$this, 'render_form_shortcode']);
        
        // Обработка отправки формы
        add_action('wp_ajax_fieldform_submit', [$this, 'handle_form_submission']);
        add_action('wp_ajax_nopriv_fieldform_submit', [$this, 'handle_form_submission']);
        
        // AJAX действия для конструктора форм
        add_action('wp_ajax_fieldform_get_fields', [$this, 'ajax_get_fields']);
        add_action('wp_ajax_fieldform_save_form_structure', [$this, 'ajax_save_form_structure']);
        add_action('wp_ajax_fieldform_delete_submission', [$this, 'ajax_delete_submission']);
        
        // Регистация post type для форм (опционально, можно использовать свои таблицы)
        add_action('init', [$this, 'register_form_post_type']);
    }
    
    /**
     * Подключение ассетов в админке
     * @param string $hook
     */
    public function enqueue_admin_assets($hook) {
        // Загружаем ассеты только на страницах плагина
        if (strpos($hook, 'fieldform') === false && $hook !== 'toplevel_page_fieldform-builder') {
            return;
        }
        
        wp_enqueue_style(
            'fieldform-admin',
            FIELDFORM_PLUGIN_URL . 'assets/css/admin.css',
            [],
            FIELDFORM_VERSION
        );
        
        wp_enqueue_script(
            'fieldform-admin',
            FIELDFORM_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable', 'wp-util'],
            FIELDFORM_VERSION,
            true
        );
        
        wp_localize_script('fieldform-admin', 'fieldformAdminData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fieldform_admin_nonce'),
            'strings' => [
                'confirmDelete' => __('Вы уверены, что хотите удалить это поле?', 'fieldform-builder'),
            ],
        ]);
    }
    
    /**
     * Подключение ассетов на фронтенде
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'fieldform-frontend',
            FIELDFORM_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            FIELDFORM_VERSION
        );
        
        wp_enqueue_script(
            'fieldform-frontend',
            FIELDFORM_PLUGIN_URL . 'assets/js/frontend.js',
            ['jquery'],
            FIELDFORM_VERSION,
            true
        );
    }
    
    /**
     * Рендер шорткода формы
     * @param array $atts Атрибуты шорткода
     * @return string HTML формы
     */
    public function render_form_shortcode($atts) {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);
        
        $form_id = intval($atts['id']);
        
        if (!$form_id) {
            return '<p class="fieldform-error">' . __('ID формы не указан.', 'fieldform-builder') . '</p>';
        }
        
        // Получение данных формы из БД
        $form = $this->get_form_data($form_id);
        
        if (!$form) {
            return '<p class="fieldform-error">' . __('Форма не найдена.', 'fieldform-builder') . '</p>';
        }
        
        ob_start();
        include FIELDFORM_PLUGIN_DIR . 'includes/frontend/views/form-template.php';
        return ob_get_clean();
    }
    
    /**
     * Обработка отправки формы
     */
    public function handle_form_submission() {
        // Проверка nonce
        if (!isset($_POST['fieldform_nonce']) || !wp_verify_nonce($_POST['fieldform_nonce'], 'fieldform_submit')) {
            wp_send_json_error(['message' => __('Ошибка безопасности.', 'fieldform-builder')]);
        }
        
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        
        if (!$form_id) {
            wp_send_json_error(['message' => __('ID формы не указан.', 'fieldform-builder')]);
        }
        
        $form = $this->get_form_data($form_id);
        
        if (!$form) {
            wp_send_json_error(['message' => __('Форма не найдена.', 'fieldform-builder')]);
        }
        
        // Валидация полей
        $errors = $this->validate_form_fields($form, $_POST);
        
        if (!empty($errors)) {
            wp_send_json_error([
                'message' => __('Пожалуйста, исправьте ошибки в форме.', 'fieldform-builder'),
                'errors' => $errors,
            ]);
        }
        
        // Сохранение данных
        $submission_id = $this->save_submission($form_id, $_POST);
        
        // Отправка email уведомления
        $this->send_notification_email($form, $_POST);
        
        wp_send_json_success([
            'message' => __('Форма успешно отправлена!', 'fieldform-builder'),
            'submission_id' => $submission_id,
        ]);
    }
    
    /**
     * Получение данных формы из БД
     * @param int $form_id
     * @return array|null
     */
    private function get_form_data($form_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fieldform_forms';
        
        $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $form_id), ARRAY_A);
        
        if (!$form) {
            return null;
        }
        
        // Получение полей формы
        $fields_table = $wpdb->prefix . 'fieldform_fields';
        $fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM $fields_table WHERE form_id = %d ORDER BY sort_order ASC", $form_id), ARRAY_A);
        
        $form['fields'] = $fields;
        
        return $form;
    }
    
    /**
     * Валидация полей формы
     * @param array $form Данные формы
     * @param array $submitted_data Отправленные данные
     * @return array Ошибки
     */
    private function validate_form_fields($form, $submitted_data) {
        $errors = [];
        
        foreach ($form['fields'] as $field) {
            $field_type = $field['field_type'];
            $field_instance = Field_Types_Manager::get_field_instance($field_type);
            
            if (!$field_instance) {
                continue;
            }
            
            $field_name = 'field_' . $field['id'];
            $value = isset($submitted_data[$field_name]) ? $submitted_data[$field_name] : '';
            
            $validation_result = $field_instance->validate($value, $field);
            
            if (is_wp_error($validation_result)) {
                $errors[$field_name] = $validation_result->get_error_message();
            }
        }
        
        return $errors;
    }
    
    /**
     * Сохранение отправленных данных
     * @param int $form_id ID формы
     * @param array $data Данные
     * @return int|false ID записи или false
     */
    private function save_submission($form_id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fieldform_submissions';
        
        // Подготовка данных для сохранения
        $submission_data = [];
        foreach ($data as $key => $value) {
            if (strpos($key, 'field_') === 0) {
                $submission_data[$key] = $value;
            }
        }
        
        $result = $wpdb->insert($table_name, [
            'form_id' => $form_id,
            'data' => json_encode($submission_data, JSON_UNESCAPED_UNICODE),
            'created_at' => current_time('mysql'),
            'ip' => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
        ]);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Отправка email уведомления
     * @param array $form Данные формы
     * @param array $data Отправленные данные
     */
    private function send_notification_email($form, $data) {
        $settings = maybe_unserialize($form['settings']);
        
        if (empty($settings['notification_email'])) {
            return;
        }
        
        $to = $settings['notification_email'];
        $subject = sprintf(__('Новая заявка с формы: %s', 'fieldform-builder'), $form['title']);
        
        $message = "<h2>" . __('Новая заявка', 'fieldform-builder') . "</h2>\n";
        $message .= "<p><strong>" . __('Форма:', 'fieldform-builder') . "</strong> " . esc_html($form['title']) . "</p>\n";
        $message .= "<table border=\"1\" cellpadding=\"5\" cellspacing=\"0\">\n";
        
        foreach ($data as $key => $value) {
            if (strpos($key, 'field_') === 0) {
                $field_id = str_replace('field_', '', $key);
                $field_label = $this->get_field_label_by_id($form['fields'], $field_id);
                
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                
                $message .= "<tr>";
                $message .= "<td><strong>" . esc_html($field_label) . "</strong></td>";
                $message .= "<td>" . esc_html($value) . "</td>";
                $message .= "</tr>\n";
            }
        }
        
        $message .= "</table>\n";
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Получение метки поля по ID
     * @param array $fields Поля формы
     * @param int $field_id ID поля
     * @return string
     */
    private function get_field_label_by_id($fields, $field_id) {
        foreach ($fields as $field) {
            if ($field['id'] == $field_id) {
                return $field['label'];
            }
        }
        return 'Field ' . $field_id;
    }
    
    /**
     * Регистрация Custom Post Type для форм
     */
    public function register_form_post_type() {
        // Опционально: если решите использовать CPT вместо своих таблиц
        /*
        register_post_type('fieldform', [
            'labels' => [
                'name' => __('Формы', 'fieldform-builder'),
                'singular_name' => __('Форма', 'fieldform-builder'),
            ],
            'public' => false,
            'show_ui' => true,
            'capability_type' => 'page',
            'capabilities' => [
                'edit_post' => 'manage_options',
                'delete_post' => 'manage_options',
            ],
            'supports' => ['title'],
            'menu_icon' => 'dashicons-feedback',
        ]);
        */
    }
    
    /**
     * AJAX обработчик получения полей формы
     */
    public function ajax_get_fields() {
        // Проверка nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fieldform_admin_nonce')) {
            wp_send_json_error(['message' => __('Ошибка безопасности.', 'fieldform-builder')]);
        }
        
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        
        if (!$form_id) {
            wp_send_json_error(['message' => __('ID формы не указан.', 'fieldform-builder')]);
        }
        
        global $wpdb;
        $fields_table = $wpdb->prefix . 'fieldform_fields';
        
        $fields = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $fields_table WHERE form_id = %d ORDER BY sort_order ASC", $form_id),
            ARRAY_A
        );
        
        if ($fields === null) {
            wp_send_json_error(['message' => __('Ошибка получения данных.', 'fieldform-builder')]);
        }
        
        // Преобразуем данные в формат, понятный JS
        $formatted_fields = [];
        foreach ($fields as $field) {
            $options = maybe_unserialize($field['options']);
            $formatted_fields[] = [
                'id' => $field['id'],
                'type' => $field['field_type'],
                'label' => $field['label'],
                'required' => (bool) $field['required'],
                'description' => $field['description'] ?? '',
                'options' => is_array($options) ? $options : [],
            ];
        }
        
        wp_send_json_success($formatted_fields);
    }
    
    /**
     * AJAX обработчик сохранения структуры формы
     */
    public function ajax_save_form_structure() {
        // Проверка nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fieldform_admin_nonce')) {
            wp_send_json_error(['message' => __('Ошибка безопасности.', 'fieldform-builder')]);
        }
        
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        
        if (!$form_id) {
            wp_send_json_error(['message' => __('ID формы не указан.', 'fieldform-builder')]);
        }
        
        $fields_json = isset($_POST['fields']) ? $_POST['fields'] : '[]';
        $fields = json_decode($fields_json, true);
        
        if (!is_array($fields)) {
            wp_send_json_error(['message' => __('Некорректные данные полей.', 'fieldform-builder')]);
        }
        
        global $wpdb;
        $fields_table = $wpdb->prefix . 'fieldform_fields';
        
        // Начинаем транзакцию
        $wpdb->query('START TRANSACTION');
        
        try {
            // Удаляем старые поля формы
            $wpdb->delete($fields_table, ['form_id' => $form_id]);
            
            // Вставляем новые поля
            foreach ($fields as $index => $field) {
                $wpdb->insert($fields_table, [
                    'form_id' => $form_id,
                    'id' => sanitize_text_field($field['id']),
                    'field_type' => sanitize_text_field($field['type']),
                    'label' => sanitize_text_field($field['label']),
                    'description' => sanitize_text_field($field['description'] ?? ''),
                    'required' => !empty($field['required']) ? 1 : 0,
                    'options' => serialize($field['options'] ?? []),
                    'sort_order' => $index,
                ]);
                
                if ($wpdb->last_error) {
                    throw new \Exception($wpdb->last_error);
                }
            }
            
            $wpdb->query('COMMIT');
            wp_send_json_success(['message' => __('Форма успешно сохранена.', 'fieldform-builder')]);
            
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(['message' => __('Ошибка сохранения: ', 'fieldform-builder') . $e->getMessage()]);
        }
    }
    
    /**
     * AJAX обработчик удаления записи
     */
    public function ajax_delete_submission() {
        // Проверка nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fieldform_delete_submission')) {
            wp_send_json_error(['message' => __('Ошибка безопасности.', 'fieldform-builder')]);
        }
        
        $submission_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if (!$submission_id) {
            wp_send_json_error(['message' => __('ID записи не указан.', 'fieldform-builder')]);
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'fieldform_submissions';
        
        $result = $wpdb->delete($table_name, ['id' => $submission_id]);
        
        if ($result) {
            wp_send_json_success(['message' => __('Запись удалена.', 'fieldform-builder')]);
        } else {
            wp_send_json_error(['message' => __('Ошибка при удалении.', 'fieldform-builder')]);
        }
    }
    
    /**
     * Запуск загрузчика
     */
    public function run() {
        // Инициализация при необходимости
    }
}
