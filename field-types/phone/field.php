<?php
/**
 * Класс поля типа "Phone"
 * 
 * @package FieldForm\FieldTypes\Phone
 */

namespace FieldForm\FieldTypes\Phone;

use FieldForm\Core\Abstract_Field_Type;

if (!defined('ABSPATH')) {
    exit;
}

class Field_Phone extends Abstract_Field_Type {
    
    /**
     * Получить тип поля
     * @return string
     */
    public function get_type() {
        return 'phone';
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
        <div class="field-options field-options-phone">
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
                <label for="field_mask_<?php echo esc_attr($field_id); ?>">
                    <?php _e('Маска ввода', 'fieldform-builder'); ?>:
                </label>
                <input type="text" 
                       id="field_mask_<?php echo esc_attr($field_id); ?>" 
                       name="fields[<?php echo esc_attr($field_id); ?>][mask]" 
                       value="<?php echo esc_attr($args['mask']); ?>" 
                       class="regular-text">
                <small><?php _e('Используйте 9 для цифры, любой другой символ остаётся как есть', 'fieldform-builder'); ?></small>
            </p>
            
            <p>
                <label for="field_country_code_<?php echo esc_attr($field_id); ?>">
                    <?php _e('Код страны по умолчанию', 'fieldform-builder'); ?>:
                </label>
                <input type="text" 
                       id="field_country_code_<?php echo esc_attr($field_id); ?>" 
                       name="fields[<?php echo esc_attr($field_id); ?>][country_code]" 
                       value="<?php echo esc_attr($args['country_code']); ?>" 
                       class="small-text">
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
        $mask = isset($args['mask']) ? esc_attr($args['mask']) : '+9 (999) 999-99-99';
        $country_code = isset($args['country_code']) ? esc_attr($args['country_code']) : '+7';
        
        // Подключение скрипта маски (если ещё не подключён)
        if (!wp_script_is('jquery-mask-plugin', 'enqueued')) {
            wp_enqueue_script(
                'jquery-mask-plugin',
                'https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js',
                ['jquery'],
                '1.14.16',
                true
            );
        }
        
        $template_path = $this->get_template_path();
        
        if ($template_path && file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }
        
        // Рендер по умолчанию, если шаблона нет
        ob_start();
        ?>
        <div class="field-wrapper field-type-phone" data-field-id="<?php echo esc_attr($field['id']); ?>">
            <?php if ($label): ?>
                <label for="<?php echo esc_attr($field_id); ?>" class="field-label">
                    <?php echo esc_html($label); ?><?php echo $required_mark; ?>
                </label>
            <?php endif; ?>
            <input type="tel" 
                   id="<?php echo esc_attr($field_id); ?>" 
                   name="<?php echo esc_attr($field_name); ?>" 
                   value="<?php echo esc_attr($value); ?>" 
                   placeholder="<?php echo $placeholder; ?>" 
                   data-mask="<?php echo $mask; ?>"
                   data-country-code="<?php echo $country_code; ?>"
                   <?php echo $required; ?>
                   class="field-input field-phone-input">
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var $phoneField = $('#<?php echo esc_js($field_id); ?>');
            var mask = $phoneField.data('mask');
            $phoneField.mask(mask);
        });
        </script>
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
        
        // Проверка формата телефона (минимум 10 цифр)
        if (!empty($value)) {
            $digits_only = preg_replace('/\D/', '', $value);
            if (strlen($digits_only) < 10) {
                return new \WP_Error(
                    'field_invalid_phone',
                    sprintf(__('Поле "%s" должно содержать корректный номер телефона.', 'fieldform-builder'), $field_settings['label'])
                );
            }
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
        // Очищаем от лишних символов, оставляем только цифры и +
        return preg_replace('/[^\d+]/', '', $value);
    }
}
