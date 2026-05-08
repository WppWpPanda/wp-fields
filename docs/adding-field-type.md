# Добавление нового типа поля в FieldForm Builder

В этом документе описан пошаговый процесс создания собственного типа поля для плагина FieldForm Builder.

## Архитектура системы типов полей

Каждый тип поля представляет собой отдельную папку в директории `field-types/` и содержит:

- `config.php` — мета-данные и настройки по умолчанию
- `field.php` — класс с логикой рендера и валидации
- `template.php` (опционально) — шаблон для фронтенда

## Пример: Создание поля "Телефон" с маской ввода

### Шаг 1: Создание структуры папок

```bash
cd /wp-content/plugins/fieldform-builder/field-types/
mkdir phone
```

### Шаг 2: Создание config.php

Создайте файл `field-types/phone/config.php`:

```php
<?php
/**
 * Конфигурация типа поля "Phone"
 * 
 * @package FieldForm\FieldTypes\Phone
 */

return [
    'name'        => __('Телефон', 'fieldform-builder'),
    'category'    => 'basic',
    'icon'        => 'dashicons-phone',
    'has_options' => true,
    'default_args'=> [
        'placeholder'   => '+7 (___) ___-__-__',
        'mask'          => '+9 (999) 999-99-99',
        'country_code'  => '+7',
    ],
];
```

### Шаг 3: Создание field.php

Создайте файл `field-types/phone/field.php`:

```php
<?php
/**
 * Класс поля типа "Phone"
 * 
 * @package FieldForm\FieldTypes\Phone
 */

if (!defined('ABSPATH')) {
    exit;
}

class FieldForm_Field_Phone extends \FieldForm\Core\Abstract_Field_Type {
    
    /**
     * Получить тип поля
     * @return string
     */
    public function get_type() {
        return 'phone';
    }
    
    /**
     * Рендер настроек поля в админке
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
     */
    public function sanitize($value, $field_settings) {
        // Очищаем от лишних символов, оставляем только цифры и +
        return preg_replace('/[^\d+]/', '', $value);
    }
}
```

### Шаг 4: (Опционально) Создание template.php

Создайте файл `field-types/phone/template.php`:

```php
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
```

### Шаг 5: Проверка работы

1. Перезагрузите страницу админки **FieldForm Builder**
2. В боковой панели должен появиться новый тип поля **"Телефон"** с иконкой телефона
3. Перетащите поле на форму и настройте его

## Советы по разработке

### 1. Именование класса

Класс должен называться `FieldForm_Field_{TypeName}` где `{TypeName}` — название папки с заглавной буквы:

- `text` → `FieldForm_Field_Text`
- `phone_number` → `FieldForm_Field_Phone_Number`

### 2. Категории полей

Доступные категории:
- `basic` — базовые поля (текст, email, телефон)
- `choice` — поля выбора (select, radio, checkbox)
- `advanced` — продвинутые поля (дата, файл, rich text)

### 3. Иконки Dashicons

Популярные иконки:
- `dashicons-editor-text` — текст
- `dashicons-email` — email
- `dashicons-phone` — телефон
- `dashicons-menu` — выбор
- `dashicons-calendar` — дата
- `dashicons-upload` — загрузка файла

### 4. Переопределение в теме

Пользователь может переопределить шаблон любого поля, создав файл:
```
/your-theme/fieldform/fields/{type}/template.php
```

### 5. Программная регистрация

Альтернативно можно зарегистрировать поле через хук:

```php
add_action('init', function() {
    require_once __DIR__ . '/my-custom-field.php';
    $instance = new FieldForm_Field_MyCustom();
    do_action('fieldform/register_custom_field_type', 'my-custom', $instance);
});
```

## Отладка

Если поле не появляется:

1. Проверьте наличие `config.php` и `field.php`
2. Убедитесь, что класс назван правильно
3. Проверьте логи PHP на наличие ошибок
4. Очистите кеш объектом WordPress: `wp cache flush`

## Дополнительные примеры

Смотрите реализацию полей `text`, `email`, `select` в папке `field-types/`.
