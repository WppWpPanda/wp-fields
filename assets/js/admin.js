/**
 * FieldForm Builder - Admin JavaScript
 *
 * @package FieldForm\Assets
 */

(function($) {
    'use strict';

    const FieldFormBuilder = {
        formId: null,
        fieldsContainer: null,
        paletteContainer: null,

        init: function() {
            this.formId = $('#fieldform-builder-form-id').val();
            this.fieldsContainer = $('#fieldform-fields-container');
            this.paletteContainer = $('.fieldform-palette-items');

            this.initDragAndDrop();
            this.bindEvents();
            this.loadFields(); // Загружаем существующие поля при старте
        },

        initDragAndDrop: function() {
            // Элементы палитры можно перетаскивать
            this.paletteContainer.find('.fieldform-palette-item').draggable({
                helper: 'clone',
                revert: 'invalid',
                connectToSortable: this.fieldsContainer,
                zIndex: 1000
            });

            // Контейнер полей принимает элементы и позволяет сортировать
            this.fieldsContainer.sortable({
                placeholder: 'fieldform-field-placeholder',
                forcePlaceholderSize: true,
                receive: function(event, ui) {
                    // Когда элемент брошен из палитры
                    const type = ui.item.data('type');
                    if (type) {
                        FieldFormBuilder.addNewField(type);
                    }
                }
            });
        },

        bindEvents: function() {
            // Клик по полю для открытия настроек
            $(document).on('click', '.fieldform-field-item', function(e) {
                // Игнорируем клик на кнопке удаления
                if ($(e.target).closest('.remove-field').length) return;
                
                const fieldId = $(this).data('field-id');
                FieldFormBuilder.openFieldSettings(fieldId);
            });

            // Удаление поля
            $(document).on('click', '.remove-field', function(e) {
                e.stopPropagation();
                if (confirm('Вы уверены, что хотите удалить это поле?')) {
                    $(this).closest('.fieldform-field-item').remove();
                }
            });

            // Сохранение настроек в модальном окне
            $(document).on('click', '#save-field-settings-btn', function() {
                FieldFormBuilder.saveFieldSettings();
            });

            // Закрытие модального окна
            $(document).on('click', '.fieldform-modal-close, .fieldform-modal-overlay', function() {
                FieldFormBuilder.closeModal();
            });

            // Сохранение всей формы
            $('#save-fieldform-form').on('click', function(e) {
                e.preventDefault();
                FieldFormBuilder.saveFormStructure();
            });
        },

        loadFields: function() {
            if (!this.formId) return;

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'fieldform_get_fields',
                    form_id: this.formId,
                    nonce: fieldformAdminData.nonce
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        response.data.forEach(function(field) {
                            FieldFormBuilder.renderField(field, true);
                        });
                    }
                },
                error: function(xhr) {
                    console.error('Ошибка загрузки полей:', xhr.responseText);
                }
            });
        },

        addNewField: function(type) {
            const newFieldId = 'field_' + Date.now();
            const defaultLabel = type.charAt(0).toUpperCase() + type.slice(1);
            
            const fieldData = {
                id: newFieldId,
                type: type,
                label: defaultLabel,
                required: false,
                options: {}
            };

            this.renderField(fieldData);
            // Сразу открываем настройки для нового поля
            setTimeout(() => {
                this.openFieldSettings(newFieldId);
            }, 100);
        },

        renderField: function(fieldData, isExisting = false) {
            const template = wp.template('fieldform-field-item');
            const html = template(fieldData);
            
            this.fieldsContainer.append(html);
        },

        openFieldSettings: function(fieldId) {
            const $fieldItem = $(`.fieldform-field-item[data-field-id="${fieldId}"]`);
            if (!$fieldItem.length) return;

            const type = $fieldItem.data('type');
            const label = $fieldItem.find('.field-label').text() || '';
            const required = $fieldItem.hasClass('required');
            
            const modalContent = `
                <div class="fieldform-modal-content">
                    <h3>Настройки поля: ${type}</h3>
                    <input type="hidden" id="edit-field-id" value="${fieldId}">
                    <input type="hidden" id="edit-field-type" value="${type}">
                    
                    <div class="form-group">
                        <label>Подпись (Label)</label>
                        <input type="text" id="edit-field-label" value="${label}" class="widefat">
                    </div>
                    
                    <div class="form-group">
                        <label>Описание (Help Text)</label>
                        <textarea id="edit-field-description" class="widefat"></textarea>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="edit-field-required" ${required ? 'checked' : ''}>
                            Обязательное поле
                        </label>
                    </div>

                    <div id="dynamic-field-options">
                        <!-- Сюда можно подгрузить специфичные настройки типа -->
                    </div>

                    <div class="modal-actions">
                        <button type="button" id="save-field-settings-btn" class="button button-primary">Сохранить</button>
                        <button type="button" class="button fieldform-modal-close">Отмена</button>
                    </div>
                </div>
            `;

            $('#fieldform-settings-modal .fieldform-modal-body').html(modalContent);
            $('#fieldform-settings-modal').show();
        },

        saveFieldSettings: function() {
            const fieldId = $('#edit-field-id').val();
            const label = $('#edit-field-label').val();
            const required = $('#edit-field-required').is(':checked');
            const description = $('#edit-field-description').val();

            if (!label) {
                alert('Подпись поля обязательна');
                return;
            }

            // Обновляем вид карточки в списке
            const $fieldItem = $(`.fieldform-field-item[data-field-id="${fieldId}"]`);
            $fieldItem.find('.field-label').text(label);
            
            if (required) {
                $fieldItem.addClass('required');
                $fieldItem.find('.required-badge').show();
            } else {
                $fieldItem.removeClass('required');
                $fieldItem.find('.required-badge').hide();
            }
            
            // Сохраняем данные в data-атрибуты
            $fieldItem.data('label', label);
            $fieldItem.data('required', required);
            $fieldItem.data('description', description);

            this.closeModal();
        },

        saveFormStructure: function() {
            if (!this.formId) {
                alert('Ошибка: ID формы не найден. Создайте новую форму сначала.');
                return;
            }

            const fields = [];
            this.fieldsContainer.find('.fieldform-field-item').each(function() {
                const $el = $(this);
                fields.push({
                    id: $el.data('field-id'),
                    type: $el.data('type'),
                    label: $el.data('label') || $el.find('.field-label').text(),
                    required: $el.data('required') || false,
                    description: $el.data('description') || '',
                    order: fields.length
                });
            });

            if (fields.length === 0) {
                if(!confirm('Форма пуста. Вы хотите сохранить её без полей?')) return;
            }

            const btnSave = $('#save-fieldform-form');
            const originalText = btnSave.text();
            btnSave.prop('disabled', true).text('Сохранение...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'fieldform_save_form_structure',
                    form_id: this.formId,
                    fields: JSON.stringify(fields),
                    nonce: fieldformAdminData.nonce
                },
                success: function(response) {
                    btnSave.prop('disabled', false).text(originalText);
                    if (response.success) {
                        alert('Форма успешно сохранена!');
                    } else {
                        alert('Ошибка сохранения: ' + (response.data || 'Неизвестная ошибка'));
                    }
                },
                error: function(xhr, status, error) {
                    btnSave.prop('disabled', false).text(originalText);
                    console.error(xhr.responseText);
                    alert('Произошла ошибка сети: ' + error);
                }
            });
        },

        closeModal: function() {
            $('#fieldform-settings-modal').hide();
        }
    };

    $(document).ready(function() {
        if ($('#fieldform-builder-form-id').length) {
            FieldFormBuilder.init();
        }
    });

})(jQuery);
