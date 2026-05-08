<?php
/**
 * Шаблон поля Text для фронтенда
 * Может быть переопределён в теме: /your-theme/fieldform/fields/text/template.php
 * 
 * @package FieldForm\FieldTypes\Text
 */

if (!defined('ABSPATH')) {
    exit;
}

// Переменные доступны из render_frontend():
// $field_id, $field_name, $label, $required, $required_mark, $placeholder, $maxlength, $size, $value, $field
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
