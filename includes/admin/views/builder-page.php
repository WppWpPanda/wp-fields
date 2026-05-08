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

// Получение всех типов полей, сгруппированных по категориям
$field_types = Field_Types_Manager::get_field_types_grouped();
?>

<div class="wrap fieldform-builder-page">
    <h1><?php _e('FieldForm Builder', 'fieldform-builder'); ?></h1>
    
    <div class="fieldform-builder-container">
        <!-- Боковая панель с типами полей -->
        <div class="fieldform-sidebar">
            <h2><?php _e('Доступные поля', 'fieldform-builder'); ?></h2>
            
            <?php foreach ($field_types as $category => $types): ?>
                <div class="field-category" data-category="<?php echo esc_attr($category); ?>">
                    <h3><?php echo esc_html(ucfirst($category)); ?></h3>
                    <ul class="field-types-list">
                        <?php foreach ($types as $type => $data): ?>
                            <li class="field-type-item" 
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
                       class="large-text">
                <button type="button" id="save-form" class="button button-primary">
                    <?php _e('Сохранить форму', 'fieldform-builder'); ?>
                </button>
            </div>
            
            <div class="fieldform-fields-container" id="fields-container">
                <p class="fieldform-placeholder">
                    <?php _e('Перетащите поля из боковой панели или нажмите на тип поля для добавления', 'fieldform-builder'); ?>
                </p>
            </div>
        </div>
        
        <!-- Панель настроек выбранного поля -->
        <div class="fieldform-settings-panel" id="settings-panel">
            <h2><?php _e('Настройки поля', 'fieldform-builder'); ?></h2>
            <div class="fieldform-settings-content">
                <p class="description">
                    <?php _e('Выберите поле для редактирования его настроек', 'fieldform-builder'); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Шаблон для нового поля (JavaScript будет клонировать) -->
<script type="text/html" id="tmpl-fieldform-field-template">
    <div class="fieldform-field-wrapper" data-field-id="{{ data.id }}" data-field-type="{{ data.type }}">
        <div class="fieldform-field-header">
            <span class="dashicons dashicons-move"></span>
            <span class="field-label-display">{{ data.label }}</span>
            <span class="field-type-badge">{{ data.type_name }}</span>
            <div class="field-actions">
                <button type="button" class="button edit-field"><?php _e('Настроить', 'fieldform-builder'); ?></button>
                <button type="button" class="button duplicate-field"><?php _e('Дублировать', 'fieldform-builder'); ?></button>
                <button type="button" class="button delete-field"><?php _e('Удалить', 'fieldform-builder'); ?></button>
            </div>
        </div>
        <div class="fieldform-field-options" style="display:none;">
            <!-- Опции будут загружены через AJAX или из шаблона -->
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

.field-type-item {
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

.field-type-item:hover {
    background: #f0f0f1;
    border-color: #2271b1;
}

.field-type-item .dashicons {
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

.fieldform-fields-container {
    min-height: 200px;
}

.fieldform-field-wrapper {
    background: #fff;
    border: 1px solid #c3c4c7;
    margin-bottom: 10px;
    padding: 10px;
}

.fieldform-field-header {
    display: flex;
    align-items: center;
    gap: 10px;
}

.fieldform-field-header .dashicons-move {
    cursor: move;
    color: #646970;
}

.field-type-badge {
    background: #f0f0f1;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    color: #646970;
}

.field-actions {
    margin-left: auto;
    display: flex;
    gap: 5px;
}

.fieldform-settings-panel {
    width: 300px;
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 15px;
    height: fit-content;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Инициализация drag-and-drop
    $('.field-type-item').draggable({
        helper: 'clone',
        revert: 'invalid',
        zIndex: 10000
    });
    
    $('#fields-container').droppable({
        accept: '.field-type-item',
        drop: function(event, ui) {
            var fieldType = ui.helper.data('type');
            var fieldName = ui.helper.find('.field-name').text();
            addNewField(fieldType, fieldName);
        }
    });
    
    function addNewField(type, name) {
        console.log('Adding field:', type, name);
        // Логика добавления нового поля
    }
    
    $('#save-form').on('click', function() {
        var title = $('#form-title').val();
        if (!title) {
            alert('<?php _e('Введите название формы', 'fieldform-builder'); ?>');
            return;
        }
        // Логика сохранения формы
        console.log('Saving form:', title);
    });
});
</script>
