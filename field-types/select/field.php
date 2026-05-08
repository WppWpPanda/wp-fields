<?php
/**
 * Класс поля типа "Select"
 * 
 * @package FieldForm\FieldTypes\Select
 */

if (!defined('ABSPATH')) {
    exit;
}

class FieldForm_Field_Select extends \FieldForm\Core\Abstract_Field_Type {
    
    /**
     * Получить тип поля
     * @return string
     */
    public function get_type() {
        return 'select';
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
        
        // Преобразуем опции в строку для textarea (одна опция на строку)
        $options_text = '';
        if (!empty($args['options']) && is_array($args['options'])) {
            $options_text = implode("\n", $args['options']);
        }
        
        $multiple_checked = !empty($args['multiple']) ? 'checked' : '';
        
        ob_start();
        ?>
        <div class="field-options field-options-select">
            <p>
                <label for="field_options_<?php echo esc_attr($field_id); ?>">
                    <?php _e('Опции (каждая с новой строки)', 'fieldform-builder'); ?>:
                </label>
                <textarea id="field_options_<?php echo esc_attr($field_id); ?>" 
                          name="fields[<?php echo esc_attr($field_id); ?>][options_text]" 
                          rows="5" 
                          class="large-text"><?php echo esc_textarea($options_text); ?></textarea>
                <small><?php _e('Введите каждую опцию с новой строки. Формат: label|value или просто label', 'fieldform-builder'); ?></small>
            </p>
            
            <p>
                <label for="field_placeholder_<?php echo esc_attr($field_id); ?>">
                    <?php _e('Placeholder (текст по умолчанию)', 'fieldform-builder'); ?>:
                </label>
                <input type="text" 
                       id="field_placeholder_<?php echo esc_attr($field_id); ?>" 
                       name="fields[<?php echo esc_attr($field_id); ?>][placeholder]" 
                       value="<?php echo esc_attr($args['placeholder']); ?>" 
                       class="regular-text">
            </p>
            
            <p>
                <label>
                    <input type="checkbox" 
                           name="fields[<?php echo esc_attr($field_id); ?>][multiple]" 
                           value="1" 
                           <?php echo $multiple_checked; ?>>
                    <?php _e('Разрешить множественный выбор', 'fieldform-builder'); ?>
                </label>
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
        $field_name = isset($field['name']) ? $field['name'] : 'field_' . $field['id'] . '[]';
        $label = isset($field['label']) ? $field['label'] : '';
        $required = !empty($field['required']) ? 'required' : '';
        $required_mark = $required ? '<span class="required">*</span>' : '';
        
        $args = isset($field['options']) ? $field['options'] : [];
        $options = isset($args['options']) ? $args['options'] : [];
        $placeholder = isset($args['placeholder']) ? esc_attr($args['placeholder']) : '';
        $multiple = !empty($args['multiple']) ? 'multiple' : '';
        $multiple_name = !empty($args['multiple']) ? '[]' : '';
        
        // Если значение не массив, а поле множественное, преобразуем
        if ($multiple && !is_array($value)) {
            $value = !empty($value) ? [$value] : [];
        }
        
        ob_start();
        ?>
        <div class="field-wrapper field-type-select" data-field-id="<?php echo esc_attr($field['id']); ?>">
            <?php if ($label): ?>
                <label for="<?php echo esc_attr($field_id); ?>" class="field-label">
                    <?php echo esc_html($label); ?><?php echo $required_mark; ?>
                </label>
            <?php endif; ?>
            <select id="<?php echo esc_attr($field_id); ?>" 
                    name="<?php echo esc_attr($field_name) . $multiple_name; ?>" 
                    <?php echo $multiple; ?> 
                    <?php echo $required; ?>
                    class="field-input field-select-input">
                <?php if ($placeholder): ?>
                    <option value="" <?php selected(empty($value)); ?>><?php echo esc_html($placeholder); ?></option>
                <?php endif; ?>
                <?php foreach ($options as $option): ?>
                    <?php
                    // Парсинг опции: формат "label|value" или просто "label"
                    if (strpos($option, '|') !== false) {
                        list($opt_label, $opt_value) = explode('|', $option, 2);
                    } else {
                        $opt_label = $option;
                        $opt_value = $option;
                    }
                    $selected = is_array($value) ? in_array($opt_value, $value) : selected($value, $opt_value, false);
                    ?>
                    <option value="<?php echo esc_attr($opt_value); ?>" <?php echo $selected; ?>>
                        <?php echo esc_html($opt_label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
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
        $available_options = isset($args['options']) ? $args['options'] : [];
        
        // Извлекаем допустимые значения
        $allowed_values = [];
        foreach ($available_options as $option) {
            if (strpos($option, '|') !== false) {
                list(, $opt_value) = explode('|', $option, 2);
            } else {
                $opt_value = $option;
            }
            $allowed_values[] = $opt_value;
        }
        
        // Проверка на обязательность
        if (!empty($field_settings['required'])) {
            if (empty($value) || (is_array($value) && empty($value))) {
                return new \WP_Error(
                    'field_required',
                    sprintf(__('Поле "%s" является обязательным.', 'fieldform-builder'), $field_settings['label'])
                );
            }
        }
        
        // Проверка что значение входит в список допустимых
        if (!empty($value)) {
            $values_to_check = is_array($value) ? $value : [$value];
            foreach ($values_to_check as $val) {
                if (!in_array($val, $allowed_values)) {
                    return new \WP_Error(
                        'field_invalid_option',
                        sprintf(__('Недопустимое значение в поле "%s".', 'fieldform-builder'), $field_settings['label'])
                    );
                }
            }
        }
        
        return true;
    }
    
    /**
     * Санитизация значения
     * @param mixed $value Значение
     * @param array $field_settings Настройки
     * @return mixed
     */
    public function sanitize($value, $field_settings) {
        if (is_array($value)) {
            return array_map('sanitize_text_field', $value);
        }
        return sanitize_text_field($value);
    }
}
