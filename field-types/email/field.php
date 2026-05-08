<?php
/**
 * Класс поля типа "Email"
 * 
 * @package FieldForm\FieldTypes\Email
 */

namespace FieldForm\FieldTypes\Email;

use FieldForm\Core\Abstract_Field_Type;

if (!defined('ABSPATH')) {
    exit;
}

class Field_Email extends Abstract_Field_Type {
    
    /**
     * Получить тип поля
     * @return string
     */
    public function get_type() {
        return 'email';
    }
    
    /**
     * Рендер настроек поля в админке
     * @param int $field_id ID поля
     * @param array $saved_value Сохранённые значения
     * @return string HTML
     */
    public function render_admin_options($field_id, $saved_value = []) {
        $defaults = $this->config['default_args'];
        $args = wp_parse_args($saved_value, $defaults);
        
        ob_start();
        ?>
        <div class="field-options field-options-email">
            <p>
                <label for="field_placeholder_<?php echo esc_attr($field_id); ?>">
                    <?php _e('Placeholder', 'fieldform-builder'); ?>:
                </label>
                <input type="text" 
                       id="field_placeholder_<?php echo esc_attr($field_id); ?>" 
                       name="fields[<?php echo esc_attr($field_id); ?>][placeholder]" 
                       value="<?php echo esc_attr($args['placeholder']); ?>" 
                       class="regular-text">
            </p>
            
            <p>
                <label for="field_maxlength_<?php echo esc_attr($field_id); ?>">
                    <?php _e('Максимальная длина', 'fieldform-builder'); ?>:
                </label>
                <input type="number" 
                       id="field_maxlength_<?php echo esc_attr($field_id); ?>" 
                       name="fields[<?php echo esc_attr($field_id); ?>][maxlength]" 
                       value="<?php echo esc_attr($args['maxlength']); ?>" 
                       class="small-text" min="0">
            </p>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Рендер поля на фронтенде
     * @param array $field Настройки поля
     * @param mixed $value Значение
     * @return string HTML
     */
    public function render_frontend($field, $value = '') {
        $field_id = isset($field['id']) ? $field['id'] : 'field_' . uniqid();
        $field_name = isset($field['name']) ? $field['name'] : 'field_' . $field['id'];
        $label = isset($field['label']) ? $field['label'] : '';
        $required = !empty($field['required']) ? 'required' : '';
        $required_mark = $required ? '<span class="required">*</span>' : '';
        
        $args = isset($field['options']) ? $field['options'] : [];
        $placeholder = isset($args['placeholder']) ? esc_attr($args['placeholder']) : '';
        $maxlength = isset($args['maxlength']) && $args['maxlength'] > 0 ? 'maxlength="' . intval($args['maxlength']) . '"' : '';
        
        ob_start();
        ?>
        <div class="field-wrapper field-type-email" data-field-id="<?php echo esc_attr($field['id']); ?>">
            <?php if ($label): ?>
                <label for="<?php echo esc_attr($field_id); ?>" class="field-label">
                    <?php echo esc_html($label); ?><?php echo $required_mark; ?>
                </label>
            <?php endif; ?>
            <input type="email" 
                   id="<?php echo esc_attr($field_id); ?>" 
                   name="<?php echo esc_attr($field_name); ?>" 
                   value="<?php echo esc_attr($value); ?>" 
                   placeholder="<?php echo $placeholder; ?>" 
                   <?php echo $maxlength; ?> 
                   <?php echo $required; ?>
                   class="field-input field-email-input">
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Валидация значения
     * @param mixed $value Значение
     * @param array $field_settings Настройки поля
     * @return \WP_Error|true
     */
    public function validate($value, $field_settings) {
        // Проверка на обязательность
        if (!empty($field_settings['required']) && (empty($value) && $value !== '0')) {
            return new \WP_Error(
                'field_required',
                sprintf(__('Поле "%s" является обязательным.', 'fieldform-builder'), $field_settings['label'])
            );
        }
        
        // Проверка формата email
        if (!empty($value) && !is_email($value)) {
            return new \WP_Error(
                'field_invalid_email',
                sprintf(__('Поле "%s" должно содержать корректный email адрес.', 'fieldform-builder'), $field_settings['label'])
            );
        }
        
        return true;
    }
    
    /**
     * Санитизация значения
     * @param mixed $value Значение
     * @param array $field_settings Настройки
     * @return string
     */
    public function sanitize($value, $field_settings) {
        return sanitize_email($value);
    }
}
