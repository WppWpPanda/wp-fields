<?php
/**
 * Шаблон формы для фронтенда
 * 
 * @package FieldForm\Frontend\Views
 */

if (!defined('ABSPATH')) {
    exit;
}

use FieldForm\Core\Field_Types_Manager;

// $form - данные формы (переданы из шорткода)
?>

<div class="fieldform-wrapper" data-form-id="<?php echo esc_attr($form['id']); ?>">
    <form class="fieldform-form" method="post" action="">
        <?php wp_nonce_field('fieldform_submit', 'fieldform_nonce'); ?>
        <input type="hidden" name="form_id" value="<?php echo esc_attr($form['id']); ?>">
        
        <!-- Honeypot поле для защиты от спама -->
        <div class="fieldform-honeypot" style="display:none;">
            <label for="fieldform_hp_<?php echo esc_attr($form['id']); ?>">
                <?php _e('Не заполняйте это поле', 'fieldform-builder'); ?>
            </label>
            <input type="text" name="fieldform_honeypot" id="fieldform_hp_<?php echo esc_attr($form['id']); ?>" value="" tabindex="-1">
        </div>
        
        <?php if (!empty($form['fields'])): ?>
            <?php foreach ($form['fields'] as $field): ?>
                <?php
                $field_type = $field['field_type'];
                $field_instance = Field_Types_Manager::get_field_instance($field_type);
                
                if (!$field_instance) {
                    continue;
                }
                
                // Получение значения (для повторного отображения после ошибки)
                $field_name = 'field_' . $field['id'];
                $value = isset($_POST[$field_name]) ? $_POST[$field_name] : '';
                
                echo $field_instance->render_frontend($field, $value);
                ?>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="fieldform-no-fields">
                <?php _e('Эта форма не содержит полей.', 'fieldform-builder'); ?>
            </p>
        <?php endif; ?>
        
        <div class="fieldform-submit-wrapper">
            <button type="submit" class="fieldform-submit button primary">
                <?php echo esc_html(apply_filters('fieldform/submit_button_text', __('Отправить', 'fieldform-builder'), $form)); ?>
            </button>
        </div>
        
        <div class="fieldform-messages">
            <div class="fieldform-message fieldform-message-error" style="display:none;"></div>
            <div class="fieldform-message fieldform-message-success" style="display:none;"></div>
        </div>
    </form>
</div>

<style>
.fieldform-wrapper {
    max-width: 600px;
    margin: 20px 0;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 5px;
}

.fieldform-form .field-wrapper {
    margin-bottom: 20px;
}

.fieldform-form .field-label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.fieldform-form .field-label .required {
    color: #d63638;
    margin-left: 3px;
}

.fieldform-form .field-input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #8c8f94;
    border-radius: 4px;
    font-size: 14px;
}

.fieldform-form .field-input:focus {
    border-color: #2271b1;
    box-shadow: 0 0 0 1px #2271b1;
    outline: none;
}

.fieldform-form select.field-input {
    height: auto;
}

.fieldform-submit-wrapper {
    margin-top: 25px;
}

.fieldform-submit {
    padding: 10px 20px;
    font-size: 14px;
}

.fieldform-messages {
    margin-top: 15px;
}

.fieldform-message {
    padding: 10px 15px;
    border-radius: 4px;
    margin-bottom: 10px;
}

.fieldform-message-error {
    background: #fcf0f1;
    border: 1px solid #d63638;
    color: #d63638;
}

.fieldform-message-success {
    background: #edfaef;
    border: 1px solid #00a32a;
    color: #00a32a;
}

.fieldform-no-fields {
    color: #646970;
    font-style: italic;
}
</style>

<script>
jQuery(document).ready(function($) {
    var $form = $('.fieldform-form[data-form-id="<?php echo esc_js($form['id']); ?>"]');
    
    $form.on('submit', function(e) {
        e.preventDefault();
        
        var $submitBtn = $form.find('.fieldform-submit');
        $submitBtn.prop('disabled', true).text('<?php echo esc_js(__('Отправка...', 'fieldform-builder')); ?>');
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: $form.serialize() + '&action=fieldform_submit',
            success: function(response) {
                if (response.success) {
                    $form.find('.fieldform-message-success').text(response.data.message).show();
                    $form.find('.fieldform-message-error').hide();
                    $form[0].reset();
                } else {
                    var errorMsg = response.data.message;
                    if (response.data.errors) {
                        // Отображение ошибок по полям
                        $.each(response.data.errors, function(field, message) {
                            $('[name="' + field + '"]').closest('.field-wrapper').addClass('has-error');
                        });
                    }
                    $form.find('.fieldform-message-error').text(errorMsg).show();
                    $form.find('.fieldform-message-success').hide();
                }
            },
            error: function() {
                $form.find('.fieldform-message-error')
                    .text('<?php echo esc_js(__('Произошла ошибка при отправке формы.', 'fieldform-builder')); ?>')
                    .show();
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text('<?php echo esc_js(__('Отправить', 'fieldform-builder')); ?>');
            }
        });
    });
    
    // Удаление класса ошибки при вводе
    $form.on('input change', '.field-input', function() {
        $(this).closest('.field-wrapper').removeClass('has-error');
    });
});
</script>
