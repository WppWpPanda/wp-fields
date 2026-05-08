<?php
/**
 * Административный модуль плагина
 * 
 * @package FieldForm\Admin
 */

namespace FieldForm\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class FieldForm_Admin {
    
    /**
     * Инициализация админ-модуля
     */
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_admin_menus']);
        add_action('admin_init', [__CLASS__, 'handle_form_creation']);
    }
    
    /**
     * Добавление пунктов меню
     */
    public static function add_admin_menus() {
        // Главное меню
        add_menu_page(
            __('FieldForm Builder', 'fieldform-builder'),
            __('FieldForm Builder', 'fieldform-builder'),
            'manage_options',
            'fieldform-builder',
            [__CLASS__, 'render_forms_list_page'],
            'dashicons-feedback',
            30
        );
        
        // Подменю: Список форм
        add_submenu_page(
            'fieldform-builder',
            __('Все формы', 'fieldform-builder'),
            __('Все формы', 'fieldform-builder'),
            'manage_options',
            'fieldform-builder',
            [__CLASS__, 'render_forms_list_page']
        );
        
        // Подменю: Конструктор форм (отдельная страница)
        add_submenu_page(
            'fieldform-builder',
            __('Редактор форм', 'fieldform-builder'),
            __('Редактор форм', 'fieldform-builder'),
            'manage_options',
            'fieldform-edit',
            [__CLASS__, 'render_builder_page']
        );
        
        // Подменю: Отправленные данные
        add_submenu_page(
            'fieldform-builder',
            __('Отправленные данные', 'fieldform-builder'),
            __('Отправленные данные', 'fieldform-builder'),
            'manage_options',
            'fieldform-submissions',
            [__CLASS__, 'render_submissions_page']
        );
    }
    
    /**
     * Обработка создания новой формы
     */
    public static function handle_form_creation() {
        if (!isset($_POST['fieldform_create_new']) || !current_user_can('manage_options')) {
            return;
        }
        
        check_admin_referer('fieldform_create_new_nonce');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'fieldform_forms';
        
        $title = sanitize_text_field($_POST['form_title'] ?? __('Новая форма', 'fieldform-builder'));
        
        $result = $wpdb->insert($table_name, [
            'title' => $title,
            'shortcode' => 'fieldform id="0"', // Будет обновлен после получения ID
            'settings' => serialize([]),
            'created_at' => current_time('mysql'),
        ]);
        
        if ($result) {
            $form_id = $wpdb->insert_id;
            
            // Обновляем шорткод с правильным ID
            $wpdb->update(
                $table_name,
                ['shortcode' => '[fieldform id="' . $form_id . '"]'],
                ['id' => $form_id]
            );
            
            wp_redirect(admin_url('admin.php?page=fieldform-edit&form_id=' . $form_id . '&created=1'));
            exit;
        }
    }
    
    /**
     * Рендер страницы списка форм
     */
    public static function render_forms_list_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fieldform_forms';
        
        // Обработка удаления
        if (isset($_GET['delete']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_form_' . intval($_GET['delete']))) {
            $form_id = intval($_GET['delete']);
            $wpdb->delete($table_name, ['id' => $form_id]);
            
            // Также удаляем поля и отправленные данные
            $wpdb->delete($wpdb->prefix . 'fieldform_fields', ['form_id' => $form_id]);
            $wpdb->delete($wpdb->prefix . 'fieldform_submissions', ['form_id' => $form_id]);
            
            echo '<div class="notice notice-success"><p>' . __('Форма удалена.', 'fieldform-builder') . '</p></div>';
        }
        
        $forms = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC", ARRAY_A);
        ?>
        <div class="wrap fieldform-forms-list">
            <h1 class="wp-heading-inline"><?php _e('Все формы', 'fieldform-builder'); ?></h1>
            
            <button type="button" class="page-title-action" onclick="document.getElementById('new-form-form').style.display='block'">
                <?php _e('Добавить новую', 'fieldform-builder'); ?>
            </button>
            
            <!-- Форма создания новой формы -->
            <div id="new-form-form" style="display:none; margin-top:20px; padding:20px; background:#fff; border:1px solid #ccd0d4;">
                <form method="post" action="">
                    <?php wp_nonce_field('fieldform_create_new_nonce'); ?>
                    <input type="hidden" name="fieldform_create_new" value="1">
                    
                    <label for="form_title"><?php _e('Название формы:', 'fieldform-builder'); ?></label>
                    <input type="text" name="form_title" id="form_title" class="large-text" required>
                    
                    <button type="submit" class="button button-primary"><?php _e('Создать форму', 'fieldform-builder'); ?></button>
                    <button type="button" class="button" onclick="document.getElementById('new-form-form').style.display='none'"><?php _e('Отмена', 'fieldform-builder'); ?></button>
                </form>
            </div>
            
            <?php if ($forms): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('ID', 'fieldform-builder'); ?></th>
                            <th><?php _e('Название', 'fieldform-builder'); ?></th>
                            <th><?php _e('Шорткод', 'fieldform-builder'); ?></th>
                            <th><?php _e('Дата создания', 'fieldform-builder'); ?></th>
                            <th><?php _e('Действия', 'fieldform-builder'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($forms as $form): ?>
                            <tr>
                                <td><?php echo esc_html($form['id']); ?></td>
                                <td><strong><?php echo esc_html($form['title']); ?></strong></td>
                                <td><code><?php echo esc_html($form['shortcode']); ?></code></td>
                                <td><?php echo esc_html($form['created_at']); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=fieldform-edit&form_id=' . $form['id']); ?>" class="button">
                                        <?php _e('Редактировать', 'fieldform-builder'); ?>
                                    </a>
                                    <a href="<?php echo admin_url('admin.php?page=fieldform-submissions&form_filter=' . $form['id']); ?>" class="button">
                                        <?php _e('Просмотр заявок', 'fieldform-builder'); ?>
                                    </a>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=fieldform-builder&delete=' . $form['id']), 'delete_form_' . $form['id']); ?>" class="button button-link-delete" onclick="return confirm('<?php esc_attr_e('Вы уверены?', 'fieldform-builder'); ?>')">
                                        <?php _e('Удалить', 'fieldform-builder'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php _e('Формы еще не созданы. Создайте первую форму!', 'fieldform-builder'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Рендер страницы конструктора форм
     */
    public static function render_builder_page() {
        // Проверка наличия формы
        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
        
        if (!$form_id) {
            echo '<div class="wrap"><h1>' . __('FieldForm Builder', 'fieldform-builder') . '</h1>';
            echo '<div class="notice notice-error"><p>' . __('Форма не выбрана. Пожалуйста, создайте новую форму или выберите существующую.', 'fieldform-builder') . '</p></div>';
            echo '<a href="' . admin_url('admin.php?page=fieldform-builder') . '" class="button button-primary">' . __('Вернуться к списку форм', 'fieldform-builder') . '</a></div>';
            return;
        }
        
        include FIELDFORM_PLUGIN_DIR . 'includes/admin/views/builder-page.php';
    }
    
    /**
     * Рендер страницы отправленных данных
     */
    public static function render_submissions_page() {
        include FIELDFORM_PLUGIN_DIR . 'includes/admin/views/submissions-page.php';
    }
}

FieldForm_Admin::init();
