<?php
/**
 * Страница конструктора форм в админке
 * 
 * @package FieldForm\Admin\Views
 */

if (!defined('ABSPATH')) {
    exit;
}

use FieldForm\Core\Field_Types_Manager;

// Получение ID формы из запроса или создание новой
$form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
$form_title = '';

if ($form_id) {
    global $wpdb;
    $forms_table = $wpdb->prefix . 'fieldform_forms';
    $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $forms_table WHERE id = %d", $form_id), ARRAY_A);
    if ($form) {
        $form_title = $form['title'];
    }
}

// Получение всех типов полей, сгруппированных по категориям
$field_types = Field_Types_Manager::get_field_types_grouped();
?>

<div class="wrap fieldform-builder-page">
    <h1><?php _e('FieldForm Builder', 'fieldform-builder'); ?></h1>
    
    <?php if (!$form_id): ?>
        <div class="notice notice-warning inline">
            <p><?php _e('Сначала создайте новую форму или выберите существующую из списка «Все формы».', 'fieldform-builder'); ?></p>
        </div>
    <?php endif; ?>
    
    <input type="hidden" id="fieldform-builder-form-id" value="<?php echo esc_attr($form_id); ?>">
    
    <div class="fieldform-builder-container">
        <!-- Боковая панель с типами полей -->
        <div class="fieldform-sidebar">
            <h2><?php _e('Доступные поля', 'fieldform-builder'); ?></h2>
            
            <?php foreach ($field_types as $category => $types): ?>
                <div class="field-category" data-category="<?php echo esc_attr($category); ?>">
                    <h3><?php echo esc_html(ucfirst($category)); ?></h3>
                    <ul class="field-types-list">
                        <?php foreach ($types as $type => $data): ?>
                            <li class="fieldform-palette-item field-type-item" 
                                data-type="<?php echo esc_attr($type); ?>" 
                                draggable="true">
                                <span class="dashicons <?php echo esc_attr($data['config']['icon']); ?>"></span>
                                <span class="field-name"><?php echo esc_html($data['config']['name']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Область конструктора форм -->
        <div class="fieldform-canvas">
            <div class="fieldform-form-header">
                <input type="text" 
                       id="form-title" 
                       placeholder="<?php _e('Название формы', 'fieldform-builder'); ?>" 
                       class="large-text"
                       value="<?php echo esc_attr($form_title); ?>">
                <button type="button" id="save-fieldform-form" class="button button-primary">
                    <?php _e('Сохранить форму', 'fieldform-builder'); ?>
                </button>
            </div>
            
            <div class="fieldform-fields-container" id="fieldform-fields-container">
                <p class="fieldform-placeholder">
                    <?php _e('Перетащите поля из боковой панели для добавления', 'fieldform-builder'); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно настроек поля -->
<div id="fieldform-settings-modal" class="fieldform-modal-overlay" style="display:none;">
    <div class="fieldform-modal">
        <div class="fieldform-modal-header">
            <h2><?php _e('Настройки поля', 'fieldform-builder'); ?></h2>
            <button class="fieldform-modal-close button-link"><span class="dashicons dashicons-no"></span></button>
        </div>
        <div class="fieldform-modal-body">
            <!-- Содержимое загружается динамически -->
        </div>
    </div>
</div>

<!-- Шаблон для поля (Underscore.js template) -->
<script type="text/html" id="tmpl-fieldform-field-item">
    <div class="fieldform-field-item" data-field-id="{{ data.id }}" data-type="{{ data.type }}">
        <div class="field-item-header">
            <span class="dashicons dashicons-move"></span>
            <span class="field-label">{{ data.label }}</span>
            <span class="required-badge" style="display: {{ data.required ? 'inline' : 'none' }};">*</span>
            <div class="field-actions">
                <button type="button" class="button remove-field"><?php _e('Удалить', 'fieldform-builder'); ?></button>
            </div>
        </div>
    </div>
</script>

<style>
.fieldform-builder-container {
    display: flex;
    gap: 20px;
    margin-top: 20px;
}

.fieldform-sidebar {
    width: 250px;
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 15px;
    height: fit-content;
}

.field-category h3 {
    font-size: 13px;
    text-transform: uppercase;
    color: #646970;
    margin: 15px 0 10px;
    padding-bottom: 5px;
    border-bottom: 1px solid #dcdcde;
}

.field-category:first-child h3 {
    margin-top: 0;
}

.field-types-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.fieldform-palette-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    margin: 5px 0;
    background: #f6f7f7;
    border: 1px solid #dcdcde;
    cursor: move;
    transition: all 0.2s;
}

.fieldform-palette-item:hover {
    background: #f0f0f1;
    border-color: #2271b1;
}

.fieldform-palette-item .dashicons {
    color: #646970;
}

.fieldform-canvas {
    flex: 1;
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    min-height: 400px;
}

.fieldform-form-header {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    align-items: center;
}

.fieldform-placeholder {
    text-align: center;
    color: #646970;
    padding: 40px;
    border: 2px dashed #dcdcde;
}

#fieldform-fields-container {
    min-height: 200px;
}

.fieldform-field-item {
    background: #fff;
    border: 1px solid #c3c4c7;
    margin-bottom: 10px;
    padding: 10px;
    cursor: pointer;
}

.fieldform-field-item:hover {
    border-color: #2271b1;
}

.field-item-header {
    display: flex;
    align-items: center;
    gap: 10px;
}

.field-item-header .dashicons-move {
    cursor: move;
    color: #646970;
}

.field-item-header .required-badge {
    color: #d63638;
    font-weight: bold;
}

.field-actions {
    margin-left: auto;
}

/* Модальное окно */
.fieldform-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.fieldform-modal {
    background: #fff;
    width: 500px;
    max-width: 90%;
    border-radius: 4px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.3);
}

.fieldform-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #dcdcde;
}

.fieldform-modal-header h2 {
    margin: 0;
    font-size: 18px;
}

.fieldform-modal-body {
    padding: 20px;
}

.modal-actions {
    margin-top: 20px;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.form-group input[type="text"],
.form-group textarea {
    width: 100%;
    max-width: 100%;
}
</style>
