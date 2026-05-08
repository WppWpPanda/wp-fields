<?php
/**
 * Класс поля типа "Text"
 * 
 * @package FieldForm\FieldTypes\Text
 */

namespace FieldForm\FieldTypes\Text;

use FieldForm\Core\Abstract_Field_Type;

if (!defined('ABSPATH')) {
    exit;
}

class Field_Text extends Abstract_Field_Type {
    
    /**
     * Получить тип поля
     * @return string
     */
    public function get_type() {
        return 'text';
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
        <div class="field-options field-options-text">
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
            
            <p>
                <label for="field_size_<?php echo esc_attr($field_id); ?>">
                    <?php _e('Размер (size)', 'fieldform-builder'); ?>:
                </label>
                <input type="number" 
                       id="field_size_<?php echo esc_attr($field_id); ?>" 
                       name="fields[<?php echo esc_attr($field_id); ?>][size]" 
                       value="<?php echo esc_attr($args['size']); ?>" 
                       class="small-text" min="1">
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
        $size = isset($args['size']) && $args['size'] > 0 ? 'size="' . intval($args['size']) . '"' : '';
        
        $template_path = $this->get_template_path();
        
        if ($template_path && file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }
        
        // Рендер по умолчанию, если шаблона нет
        ob_start();
        ?>
        <div class="field-wrapper field-type-text" data-field-id="<?php echo esc_attr($field['id']); ?>">
            <?php if ($label): ?>
                <label for="<?php echo esc_attr($field_id); ?>" class="field-label">
                    <?php echo esc_html($label); ?><?php echo $required_mark; ?>
                </label>
            <?php endif; ?>
            <input type="text" 
                   id="<?php echo esc_attr($field_id); ?>" 
                   name="<?php echo esc_attr($field_name); ?>" 
                   value="<?php echo esc_attr($value); ?>" 
                   placeholder="<?php echo $placeholder; ?>" 
                   <?php echo $maxlength; ?> 
                   <?php echo $size; ?> 
                   <?php echo $required; ?>
                   class="field-input field-text-input">
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
        $args = isset($field_settings['options']) ? $field_settings['options'] : [];
        
        // Проверка на обязательность
        if (!empty($field_settings['required']) && (empty($value) && $value !== '0')) {
            return new \WP_Error(
                'field_required',
                sprintf(__('Поле "%s" является обязательным.', 'fieldform-builder'), $field_settings['label'])
            );
        }
        
        // Проверка максимальной длины
        if (!empty($args['maxlength']) && strlen($value) > intval($args['maxlength'])) {
            return new \WP_Error(
                'field_maxlength',
                sprintf(__('Длина поля "%s" не должна превышать %d символов.', 'fieldform-builder'), $field_settings['label'], intval($args['maxlength']))
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
        return sanitize_text_field($value);
    }
}
