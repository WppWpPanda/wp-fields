<?php
/**
 * Plugin Name: FieldForm Builder
 * Plugin URI: https://example.com/fieldform-builder
 * Description: Легковесный и расширяемый плагин для создания произвольных форм на WordPress. Добавление новых типов полей через копирование папок.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: fieldform-builder
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.8
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Определение констант плагина
define('FIELDFORM_VERSION', '1.0.0');
define('FIELDFORM_PLUGIN_FILE', __FILE__);
define('FIELDFORM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FIELDFORM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FIELDFORM_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Автозагрузка классов (PSR-4)
spl_autoload_register(function ($class) {
    $prefix = 'FieldForm\\';
    $base_dir = FIELDFORM_PLUGIN_DIR . 'core/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Загрузка ядра плагина
require_once FIELDFORM_PLUGIN_DIR . 'includes/class-fieldform-loader.php';
require_once FIELDFORM_PLUGIN_DIR . 'includes/class-fieldform-activator.php';
require_once FIELDFORM_PLUGIN_DIR . 'includes/class-fieldform-deactivator.php';

// Инициализация плагина
function fieldform_init() {
    $loader = new FieldForm\Includes\FieldForm_Loader();
    $loader->run();
}
add_action('plugins_loaded', 'fieldform_init');

// Хуки активации/деактивации
register_activation_hook(__FILE__, ['FieldForm\Includes\FieldForm_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['FieldForm\Includes\FieldForm_Deactivator', 'deactivate']);
