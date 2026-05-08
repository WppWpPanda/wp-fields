<?php
/**
 * Деактиватор плагина
 * 
 * @package FieldForm\Includes
 */

namespace FieldForm\Includes;

class FieldForm_Deactivator {
    
    /**
     * Метод деактивации плагина
     */
    public static function deactivate() {
        flush_rewrite_rules();
        
        // Примечание: таблицы БД не удаляются при деактивации
        // Для полного удаления используйте uninstall.php
    }
}
