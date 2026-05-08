<?php
/**
 * Страница просмотра отправленных данных
 * 
 * @package FieldForm\Admin\Views
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Обработка экспорта в CSV
if (isset($_GET['export']) && isset($_GET['form_id'])) {
    $form_id = intval($_GET['form_id']);
    $table_name = $wpdb->prefix . 'fieldform_submissions';
    
    $submissions = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE form_id = %d ORDER BY created_at DESC", $form_id), ARRAY_A);
    
    if ($submissions) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=submissions-' . $form_id . '-' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // Заголовки
        fputcsv($output, ['ID', 'Form ID', 'Data', 'IP', 'User Agent', 'Created At']);
        
        // Данные
        foreach ($submissions as $submission) {
            fputcsv($output, $submission);
        }
        
        fclose($output);
        exit;
    }
}

// Получение списка форм для фильтра
$forms_table = $wpdb->prefix . 'fieldform_forms';
$forms = $wpdb->get_results("SELECT id, title FROM $forms_table ORDER BY title", ARRAY_A);

// Фильтрация по форме
$form_filter = isset($_GET['form_filter']) ? intval($_GET['form_filter']) : 0;

// Пагинация
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

// Получение данных
$submissions_table = $wpdb->prefix . 'fieldform_submissions';

$where_clause = '';
$params = [];

if ($form_filter) {
    $where_clause = 'WHERE form_id = %d';
    $params[] = $form_filter;
}

$total_query = "SELECT COUNT(*) FROM $submissions_table $where_clause";
$total_items = $wpdb->get_var($wpdb->prepare($total_query, $params));

$query = "SELECT * FROM $submissions_table $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";
$params[] = $per_page;
$params[] = $offset;

$submissions = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);

?>

<div class="wrap fieldform-submissions-page">
    <h1><?php _e('Отправленные данные', 'fieldform-builder'); ?></h1>
    
    <!-- Фильтры -->
    <div class="fieldform-filters" style="margin: 20px 0;">
        <form method="get">
            <input type="hidden" name="page" value="fieldform-submissions">
            
            <label for="form_filter"><?php _e('Фильтр по форме:', 'fieldform-builder'); ?></label>
            <select name="form_filter" id="form_filter">
                <option value="0"><?php _e('Все формы', 'fieldform-builder'); ?></option>
                <?php foreach ($forms as $form): ?>
                    <option value="<?php echo esc_attr($form['id']); ?>" <?php selected($form_filter, $form['id']); ?>>
                        <?php echo esc_html($form['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <?php if ($form_filter): ?>
                <a href="?page=fieldform-submissions&export=1&form_id=<?php echo esc_attr($form_filter); ?>" class="button">
                    <?php _e('Экспорт в CSV', 'fieldform-builder'); ?>
                </a>
            <?php endif; ?>
            
            <button type="submit" class="button"><?php _e('Применить фильтр', 'fieldform-builder'); ?></button>
        </form>
    </div>
    
    <!-- Таблица данных -->
    <?php if ($submissions): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('ID', 'fieldform-builder'); ?></th>
                    <th><?php _e('Форма ID', 'fieldform-builder'); ?></th>
                    <th><?php _e('Данные', 'fieldform-builder'); ?></th>
                    <th><?php _e('IP', 'fieldform-builder'); ?></th>
                    <th><?php _e('Дата отправки', 'fieldform-builder'); ?></th>
                    <th><?php _e('Действия', 'fieldform-builder'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($submissions as $submission): ?>
                    <tr>
                        <td><?php echo esc_html($submission['id']); ?></td>
                        <td><?php echo esc_html($submission['form_id']); ?></td>
                        <td>
                            <details>
                                <summary><?php _e('Просмотр данных', 'fieldform-builder'); ?></summary>
                                <pre><?php echo esc_html(print_r(json_decode($submission['data'], true), true)); ?></pre>
                            </details>
                        </td>
                        <td><?php echo esc_html($submission['ip']); ?></td>
                        <td><?php echo esc_html($submission['created_at']); ?></td>
                        <td>
                            <a href="#" class="button button-small delete-submission" data-id="<?php echo esc_attr($submission['id']); ?>">
                                <?php _e('Удалить', 'fieldform-builder'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Пагинация -->
        <?php
        $total_pages = ceil($total_items / $per_page);
        if ($total_pages > 1):
            ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => $current_page,
                    ]);
                    ?>
                </div>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <p><?php _e('Нет отправленных данных.', 'fieldform-builder'); ?></p>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    $('.delete-submission').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('<?php _e('Вы уверены, что хотите удалить эту запись?', 'fieldform-builder'); ?>')) {
            return;
        }
        
        var submissionId = $(this).data('id');
        var $row = $(this).closest('tr');
        
        $.post(ajaxurl, {
            action: 'fieldform_delete_submission',
            id: submissionId,
            nonce: '<?php echo wp_create_nonce('fieldform_delete_submission'); ?>'
        }, function(response) {
            if (response.success) {
                $row.fadeOut();
            } else {
                alert(response.data.message);
            }
        });
    });
});
</script>
