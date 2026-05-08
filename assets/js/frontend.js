/**
 * FieldForm Builder - Frontend JavaScript
 * 
 * @package FieldForm\Assets
 */

(function($) {
    'use strict';
    
    var FieldFormFrontend = {
        
        init: function() {
            this.initFormSubmission();
            this.initFieldValidation();
        },
        
        /**
         * Инициализация отправки форм через AJAX
         */
        initFormSubmission: function() {
            $('.fieldform-form').on('submit', $.proxy(this.handleFormSubmit, this));
        },
        
        /**
         * Обработка отправки формы
         */
        handleFormSubmit: function(e) {
            e.preventDefault();
            
            var $form = $(e.currentTarget);
            var $submitBtn = $form.find('.fieldform-submit');
            var $messageError = $form.find('.fieldform-message-error');
            var $messageSuccess = $form.find('.fieldform-message-success');
            
            // Скрытие предыдущих сообщений
            $messageError.hide().text('');
            $messageSuccess.hide().text('');
            
            // Проверка honeypot (защита от спама)
            var honeypotValue = $form.find('[name="fieldform_honeypot"]').val();
            if (honeypotValue) {
                // Honeypot заполнен - это спам, просто скрываем форму
                $form.hide();
                return;
            }
            
            // Блокировка кнопки отправки
            $submitBtn.prop('disabled', true).text($submitBtn.data('processing-text') || 'Отправка...');
            
            // Отправка данных
            $.ajax({
                url: fieldformFrontend.ajaxUrl || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: $form.serialize() + '&action=fieldform_submit',
                dataType: 'json'
            })
            .done($.proxy(function(response) {
                if (response.success) {
                    // Успешная отправка
                    $messageSuccess.text(response.data.message).fadeIn();
                    $form[0].reset();
                    
                    // Удаление классов ошибок
                    $form.find('.has-error').removeClass('has-error');
                    
                    // Событие после успешной отправки
                    $(document).trigger('fieldform:submit:success', [response, $form]);
                } else {
                    // Ошибка валидации
                    this.showErrors($form, response.data);
                    $(document).trigger('fieldform:submit:error', [response, $form]);
                }
            }, this))
            .fail($.proxy(function(xhr, status, error) {
                // Ошибка сервера
                $messageError
                    .text(fieldformFrontend.strings.submitError || 'Произошла ошибка при отправке формы.')
                    .fadeIn();
                $(document).trigger('fieldform:submit:fail', [xhr, status, error, $form]);
            }, this))
            .always(function() {
                // Разблокировка кнопки
                $submitBtn.prop('disabled', false).text($submitBtn.data('original-text') || 'Отправить');
            });
        },
        
        /**
         * Отображение ошибок валидации
         */
        showErrors: function($form, errorData) {
            var $messageError = $form.find('.fieldform-message-error');
            var messages = [];
            
            // Ошибки по полям
            if (errorData.errors) {
                var self = this;
                
                $.each(errorData.errors, function(fieldName, message) {
                    var $field = $form.find('[name="' + fieldName + '"]');
                    
                    if ($field.length) {
                        $field.closest('.field-wrapper').addClass('has-error');
                        
                        // Плавная прокрутка к первому ошибочному полю
                        if (!self.firstErrorScrolled) {
                            $('html, body').animate({
                                scrollTop: $field.closest('.field-wrapper').offset().top - 100
                            }, 500);
                            self.firstErrorScrolled = true;
                        }
                    }
                    
                    messages.push(message);
                });
            }
            
            // Общее сообщение об ошибке
            if (errorData.message) {
                messages.unshift(errorData.message);
            }
            
            if (messages.length) {
                $messageError.html(messages.join('<br>')).fadeIn();
            }
        },
        
        /**
         * Инициализация валидации полей на клиенте
         */
        initFieldValidation: function() {
            // Удаление класса ошибки при вводе
            $('.fieldform-form').on('input change', '.field-input', function() {
                $(this).closest('.field-wrapper').removeClass('has-error');
                $(this).closest('.fieldform-form').find('.fieldform-message-error').hide();
            });
            
            // Валидация email полей
            $('.fieldform-form').on('blur', 'input[type="email"]', function() {
                var $this = $(this);
                var value = $this.val();
                
                if (value && !self.isValidEmail(value)) {
                    $this.closest('.field-wrapper').addClass('has-error');
                }
            });
        },
        
        /**
         * Проверка корректности email
         */
        isValidEmail: function(email) {
            var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
    };
    
    // Глобальный объект для доступа извне
    window.FieldFormFrontend = FieldFormFrontend;
    
    // Инициализация при загрузке документа
    $(document).ready(function() {
        FieldFormFrontend.init();
    });
    
})(jQuery);

// Локализация для JS
var fieldformFrontend = {
    ajaxUrl: '/wp-admin/admin-ajax.php',
    strings: {
        submitError: 'Произошла ошибка при отправке формы. Попробуйте позже.'
    }
};
