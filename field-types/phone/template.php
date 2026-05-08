<?php
/**
 * Шаблон поля Phone для фронтенда
 * Может быть переопределён в теме: /your-theme/fieldform/fields/phone/template.php
 */

if (!defined('ABSPATH')) {
    exit;
}
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
