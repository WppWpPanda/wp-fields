# FieldForm Builder

**Версия:** 1.0.0  
**Требуется:** WordPress 5.8+, PHP 7.4+  
**Лицензия:** GPL v2 или позже

Легковесный и расширяемый плагин для создания произвольных форм на WordPress. Ключевая особенность — добавление нового типа поля осуществляется простым копированием папки с определённой структурой файлов, без необходимости редактировать ядро плагина.

## Содержание

- [Возможности](#возможности)
- [Установка](#установка)
- [Использование](#использование)
- [Добавление нового типа поля](#добавление-нового-типа-поля)
- [API для разработчиков](#api-для-разработчиков)
- [Структура плагина](#структура-плагина)

## Возможности

- ✅ Drag-and-drop конструктор форм
- ✅ Автоматическое обнаружение типов полей
- ✅ Расширяемая архитектура (добавление полей через папки)
- ✅ Валидация на клиенте и сервере
- ✅ Защита от спама (nonce + honeypot)
- ✅ Email уведомления
- ✅ Экспорт данных в CSV
- ✅ Переопределение шаблонов в теме
- ✅ Поддержка i18n (готов к переводу)

## Установка

1. Скопируйте файлы плагина в `/wp-content/plugins/fieldform-builder/`
2. Активируйте плагин в админ-панели WordPress
3. При активации автоматически создадутся необходимые таблицы БД

## Использование

### Создание формы

1. Перейдите в **FieldForm Builder → Все формы**
2. Введите название формы
3. Перетащите нужные поля из боковой панели
4. Настройте каждое поле (нажмите "Настроить")
5. Сохраните форму

### Вставка формы на сайт

Используйте шорткод:
```
[fieldform id="X"]
```
где `X` — ID вашей формы.

## Добавление нового типа поля

Это ключевая особенность плагина! Для добавления нового типа поля:

### Шаг 1: Создайте папку

```
/wp-content/plugins/fieldform-builder/field-types/your-field-type/
```

### Шаг 2: Добавьте config.php

```php
<?php
return [
    'name'        => __('Ваше поле', 'fieldform-builder'),
    'category'    => 'basic',  // basic, advanced, choice
    'icon'        => 'dashicons-editor-text',
    'has_options' => true,
    'default_args'=> [
        'placeholder' => '',
    ],
];
```

### Шаг 3: Добавьте field.php

```php
<?php
if (!defined('ABSPATH')) {
    exit;
}

class FieldForm_Field_YourFieldType extends \FieldForm\Core\Abstract_Field_Type {
    
    public function get_type() {
        return 'your-field-type';
    }
    
    public function render_admin_options($field_id, $saved_value = []) {
        // Рендер настроек поля в админке
        return '<p>Настройки поля</p>';
    }
    
    public function render_frontend($field, $value = '') {
        // Рендер поля на фронтенде
        return '<input type="text" value="' . esc_attr($value) . '">';
    }
    
    public function validate($value, $field_settings) {
        // Валидация
        if (empty($value) && !empty($field_settings['required'])) {
            return new \WP_Error('required', 'Поле обязательно');
        }
        return true;
    }
}
```

### Шаг 4: (Опционально) Добавьте template.php

Шаблон для фронтенда, который можно переопределить в теме.

### Готово!

Перезагрузите страницу админки — новый тип поля появится в списке доступных.

## API для разработчиков

### Хуки

#### fieldform/allowed_field_types
Фильтр для изменения списка доступных типов полей.

```php
add_filter('fieldform/allowed_field_types', function($types) {
    // Удалить тип поля
    unset($types['email']);
    return $types;
});
```

#### fieldform/after_field_registered
Действие после регистрации типа поля.

```php
add_action('fieldform/after_field_registered', function($type, $instance) {
    error_log('Зарегистрировано поле: ' . $type);
}, 10, 2);
```

#### fieldform/register_custom_field_type
Хук для программной регистрации типа поля.

```php
do_action('fieldform/register_custom_field_type', 'my-type', $instance);
```

#### fieldform/submit_button_text
Фильтр для изменения текста кнопки отправки.

```php
add_filter('fieldform/submit_button_text', function($text, $form) {
    return 'Отправить заявку';
}, 10, 2);
```

### Переопределение шаблонов в теме

Создайте файл в вашей теме:
```
/your-theme/fieldform/fields/text/template.php
```

## Структура плагина

```
fieldform-builder/
├── fieldform-builder.php      # Главный файл плагина
├── core/                       # Ядро плагина
│   ├── class-abstract-field-type.php
│   └── class-field-types-manager.php
├── field-types/                # Типы полей
│   ├── text/
│   │   ├── config.php
│   │   ├── field.php
│   │   └── template.php
│   ├── email/
│   └── select/
├── includes/                   # Вспомогательные классы
│   ├── class-fieldform-loader.php
│   ├── class-fieldform-activator.php
│   ├── class-fieldform-deactivator.php
│   ├── admin/views/
│   └── frontend/views/
├── assets/                     # CSS и JS
│   ├── css/
│   │   ├── admin.css
│   │   └── frontend.css
│   └── js/
│       ├── admin.js
│       └── frontend.js
└── docs/                       # Документация
```

## Примеры

### Пример: Поле "Телефон" с маской

Смотрите пример в документации `docs/adding-field-type.md`.

## Требования

- PHP 7.4 или выше (совместим с PHP 8.x)
- WordPress 5.8 или выше
- jQuery (включён в WordPress)

## Безопасность

Плагин использует:
- Nonce токены для защиты форм
- Honeypot поля для защиты от спама
- Экранирование всех выводимых данных (`esc_html`, `esc_attr`, `wp_kses`)
- Проверку capabilities (`manage_options`)

## Лицензия

GPL v2 или позже

---

**Разработчик:** Your Name  
**Сайт:** https://example.com
