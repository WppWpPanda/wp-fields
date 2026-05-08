/**
 * FieldForm Builder - Admin JavaScript
 * 
 * @package FieldForm\Assets
 */

(function($) {
    'use strict';
    
    var FieldFormBuilder = {
        
        fields: [],
        currentFieldId: null,
        
        init: function() {
            this.initDragAndDrop();
            this.initEventListeners();
            this.initSortable();
        },
        
        /**
         * Инициализация Drag-and-Drop
         */
        initDragAndDrop: function() {
            $('.field-type-item').draggable({
                helper: 'clone',
                revert: 'invalid',
                zIndex: 10000,
                appendTo: 'body'
            });
            
            $('#fields-container').droppable({
                accept: '.field-type-item',
                activeClass: 'ui-droppable-active',
                hoverClass: 'ui-droppable-hover',
                drop: $.proxy(this.handleFieldDrop, this)
            });
        },
        
        /**
         * Обработка падения поля
         */
        handleFieldDrop: function(event, ui) {
            var fieldType = ui.helper.data('type');
            var fieldName = ui.helper.find('.field-name').text();
            this.addNewField(fieldType, fieldName);
        },
        
        /**
         * Добавление нового поля
         */
        addNewField: function(type, name) {
            var fieldId = 'field_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            
            var fieldData = {
                id: fieldId,
                type: type,
                label: name || 'Новое поле',
                required: false,
                options: {}
            };
            
            this.fields.push(fieldData);
            this.renderField(fieldData);
            this.hidePlaceholder();
        },
        
        /**
         * Рендер поля в конструкторе
         */
        renderField: function(fieldData) {
            var template = wp.template('fieldform-field-template');
            var html = template(fieldData);
            
            var $fieldWrapper = $(html);
            $('#fields-container').append($fieldWrapper);
            
            this.bindFieldEvents($fieldWrapper);
        },
        
        /**
         * Привязка событий к полю
         */
        bindFieldEvents: function($field) {
            var self = this;
            
            // Редактирование поля
            $field.find('.edit-field').on('click', function() {
                self.openFieldSettings($(this).closest('.fieldform-field-wrapper'));
            });
            
            // Дублирование поля
            $field.find('.duplicate-field').on('click', function() {
                self.duplicateField($(this).closest('.fieldform-field-wrapper'));
            });
            
            // Удаление поля
            $field.find('.delete-field').on('click', function() {
                self.deleteField($(this).closest('.fieldform-field-wrapper'));
            });
        },
        
        /**
         * Открытие настроек поля
         */
        openFieldSettings: function($field) {
            this.currentFieldId = $field.data('field-id');
            var fieldType = $field.data('field-type');
            
            $('#settings-panel .fieldform-settings-content').html(
                '<p>Загрузка настроек...</p>'
            );
            
            // Загрузка настроек через AJAX
            $.post(fieldformAdmin.ajaxUrl, {
                action: 'fieldform_get_field_settings',
                field_type: fieldType,
                field_id: this.currentFieldId,
                nonce: fieldformAdmin.nonce
            }, function(response) {
                if (response.success) {
                    $('#settings-panel .fieldform-settings-content').html(response.data.html);
                }
            });
        },
        
        /**
         * Дублирование поля
         */
        duplicateField: function($field) {
            var $clone = $field.clone();
            var newId = 'field_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            
            $clone.attr('data-field-id', newId);
            $clone.find('.fieldform-field-options').hide();
            
            $field.after($clone);
            this.bindFieldEvents($clone);
        },
        
        /**
         * Удаление поля
         */
        deleteField: function($field) {
            if (!confirm(fieldformAdmin.strings.confirmDelete)) {
                return;
            }
            
            var fieldId = $field.data('field-id');
            
            // Удаление из массива
            this.fields = this.fields.filter(function(f) {
                return f.id !== fieldId;
            });
            
            $field.fadeOut(function() {
                $(this).remove();
                
                // Показать placeholder если нет полей
                if ($('.fieldform-field-wrapper').length === 0) {
                    $('#fields-container').html(
                        '<p class="fieldform-placeholder">Перетащите поля из боковой панели или нажмите на тип поля для добавления</p>'
                    );
                    FieldFormBuilder.initDragAndDrop();
                }
            });
        },
        
        /**
         * Скрытие placeholder
         */
        hidePlaceholder: function() {
            $('.fieldform-placeholder').hide();
        },
        
        /**
         * Инициализация сортировки
         */
        initSortable: function() {
            $('#fields-container').sortable({
                handle: '.dashicons-move',
                placeholder: 'fieldform-sortable-placeholder',
                opacity: 0.7,
                cursor: 'move'
            });
        },
        
        /**
         * Инициализация обработчиков событий
         */
        initEventListeners: function() {
            var self = this;
            
            // Сохранение формы
            $('#save-form').on('click', function() {
                self.saveForm();
            });
            
            // Обновление метки поля при изменении
            $(document).on('change', '#field_label', function() {
                if (self.currentFieldId) {
                    $('[data-field-id="' + self.currentFieldId + '"] .field-label-display')
                        .text($(this).val() || 'Поле без названия');
                }
            });
        },
        
        /**
         * Сохранение формы
         */
        saveForm: function() {
            var title = $('#form-title').val().trim();
            
            if (!title) {
                alert('Введите название формы');
                $('#form-title').focus();
                return;
            }
            
            // Сбор данных полей
            var fieldsData = [];
            $('.fieldform-field-wrapper').each(function(index) {
                var $field = $(this);
                fieldsData.push({
                    id: $field.data('field-id'),
                    type: $field.data('field-type'),
                    sort_order: index
                });
            });
            
            var $btn = $('#save-form');
            $btn.prop('disabled', true).text('Сохранение...');
            
            $.post(fieldformAdmin.ajaxUrl, {
                action: 'fieldform_save_form',
                title: title,
                fields: fieldsData,
                nonce: fieldformAdmin.nonce
            }, function(response) {
                if (response.success) {
                    alert('Форма успешно сохранена! ID формы: ' + response.data.form_id);
                    
                    // Обновление шорткода
                    var shortcode = '[fieldform id="' + response.data.form_id + '"]';
                    console.log('Shortcode:', shortcode);
                } else {
                    alert('Ошибка при сохранении: ' + (response.data.message || 'Неизвестная ошибка'));
                }
                $btn.prop('disabled', false).text('Сохранить форму');
            }).fail(function() {
                alert('Произошла ошибка при сохранении формы');
                $btn.prop('disabled', false).text('Сохранить форму');
            });
        }
    };
    
    // Инициализация при загрузке документа
    $(document).ready(function() {
        FieldFormBuilder.init();
    });
    
})(jQuery);
