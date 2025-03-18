<?php

/**
 * Plugin Name: WP News Ticker
 * Description: Plugin para exibição de notícias em um efeito de transição no front-end.
 * Version: 1.1.5
 * Author: Julio Marques - Maxim Web
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Criar o menu no painel do WordPress
function wnt_add_menu()
{
    add_menu_page('Notícias', 'Notícias', 'manage_options', 'wnt_news', 'wnt_news_list_page', 'dashicons-megaphone', 20);
    add_submenu_page('wnt_news', 'Listar Notícias', 'Listar Notícias', 'manage_options', 'wnt_news', 'wnt_news_list_page');
    add_submenu_page('wnt_news', 'Adicionar Notícia', 'Adicionar Notícia', 'manage_options', 'wnt_add_news', 'wnt_add_news_page');
}
add_action('admin_menu', 'wnt_add_menu');

// Criar a tabela no banco de dados ao ativar o plugin
function wnt_create_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'wnt_news';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        url VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'wnt_create_table');

// Página para adicionar/editar notícia
function wnt_add_news_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'wnt_news';
    $id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
    $news = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id)) : null;

    if (isset($_POST['wnt_save_news'])) {
        $title = sanitize_text_field($_POST['wnt_title']);
        $url = esc_url_raw($_POST['wnt_url']); // Usar esc_url_raw para permitir URLs vazias
        if (empty($title)) {
            echo '<div class="error"><p>O título é obrigatório.</p></div>';
        } else {
            if ($id) {
                $wpdb->update($table_name, ['title' => $title, 'url' => $url], ['id' => $id]);
            } else {
                $wpdb->insert($table_name, ['title' => $title, 'url' => $url]);
            }
            // Redireciona para a página de listagem de notícias
            echo '<script>window.location.href = "' . admin_url('admin.php?page=wnt_news') . '";</script>';
            exit;
        }
    }
?>
    <div class="wrap">
        <h1><?php echo $id ? 'Editar Notícia' : 'Adicionar Notícia'; ?></h1>
        <div class="postbox" style="padding: 20px; width: 80%; margin: auto;">
            <h2>Preencha os campos abaixo.</h2>
            <div class="inside">
                <form method="post">
                    <label for="wnt_title" style="display: block; margin-bottom: 5px; font-weight: bold;">Título da Notícia</label>
                    <input type="text" id="wnt_title" name="wnt_title" value="<?php echo esc_attr($news->title ?? ''); ?>" required style="width: 100%; padding: 8px; box-sizing: border-box; margin-bottom: 15px;"><br>

                    <label for="wnt_url" style="display: block; margin-bottom: 5px; font-weight: bold;">URL da Notícia</label>
                    <input type="url" id="wnt_url" name="wnt_url" value="<?php echo esc_url($news->url ?? ''); ?>" style="width: 100%; padding: 8px; box-sizing: border-box; margin-bottom: 15px;"><br>

                    <input type="submit" name="wnt_save_news" value="Salvar Notícia" class="button-primary">
                    <a href="<?php echo admin_url('admin.php?page=wnt_news'); ?>" class="button">Cancelar</a>
                </form>
            </div>
        </div>
    </div>
<?php
}

// Página para listar notícias
function wnt_news_list_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'wnt_news';

    if (isset($_GET['delete'])) {
        $wpdb->delete($table_name, ['id' => intval($_GET['delete'])]);
        echo '<script>window.location.href = "' . admin_url('admin.php?page=wnt_news') . '";</script>';
        exit;
    }

    $news_list = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
?>
    <div class="wrap">
        <h1>Gerenciar Notícias <a href="?page=wnt_add_news" class="page-title-action">Adicionar Notícia</a></h1>
        <p>Para utilizar o plugin adicione o Shotcode <code>[wp_news_ticker]</code> na pagina que deseja exibir as noticias acima.</p>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Notícia</th>
                    <th>Link</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($news_list as $news) : ?>
                    <tr>
                        <td><?php echo $news->id; ?></td>
                        <td><?php echo esc_html($news->title); ?></td>
                        <td>
                            <?php if (!empty($news->url)) : ?>
                                <a href="<?php echo esc_url($news->url); ?>" target="_blank">Ver Notícia</a>
                            <?php else : ?>
                                <span>Sem link</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?page=wnt_add_news&edit=<?php echo $news->id; ?>" class="button button-small"><span class="dashicons dashicons-edit"></span> Editar</a>
                            <a href="?page=wnt_news&delete=<?php echo $news->id; ?>" class="button button-small" onclick="return confirm('Tem certeza que deseja remover esta notícia?');"
                                style=" color: #dc3232; border-color: #dc3232;">
                                <span class="dashicons dashicons-trash"></span> Remover
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p>Desenvolvido por:
            <a href="https://www.linkedin.com/in/juliomarquesjr/" target="_blank">
                <strong>Maxim Web.</strong>
            </a>
        </p>
    </div>
<?php
}


function wnt_display_news_ticker()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'wnt_news';
    // Consulta as notícias no banco de dados
    $news_list = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

    // Verifica se há notícias para exibir
    if (empty($news_list)) {
        return '<p>Nenhuma notícia encontrada.</p>';
    }

    // Inicia o buffer de saída
    ob_start();
?>
    <div id="wnt-news-ticker" class="wnt-news-ticker">
        <div class="wnt-ticker-container">
            <marquee class="wnt-ticker-content" onmouseover="this.stop();" onmouseout="this.start();">
                <span class="wnt-ticker-item"><strong>Últimas notícias: </strong></span>
                <?php foreach ($news_list as $news) : ?>
                    <span class="wnt-ticker-item">
                        <?php if (!empty($news->url)) : ?>
                            <a href="<?php echo esc_url($news->url); ?>" target="_blank"><?php echo esc_html($news->title); ?></a>
                        <?php else : ?>
                            <?php echo esc_html($news->title); ?>
                        <?php endif; ?>
                    </span>
                <?php endforeach; ?>
            </marquee>
        </div>
    </div>
<?php
    // Retorna o conteúdo do buffer
    return ob_get_clean();
}


// Registrar o shortcode
function wnt_register_shortcode()
{
    add_shortcode('wp_news_ticker', 'wnt_display_news_ticker');
}
add_action('init', 'wnt_register_shortcode');

// Adicionar CSS no front-end
function wnt_enqueue_styles()
{
    wp_enqueue_style('wnt-news-ticker-style', plugins_url('css/style.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'wnt_enqueue_styles');
