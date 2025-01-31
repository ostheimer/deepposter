<?php
/**
 * Plugin Name: AI Blog Generator
 * Description: Generiert kategoriebasierte Blogbeiträge mit OpenAI
 * Version: 1.0
 * Author: Dein Name
 */

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . 'includes/settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/generator.php';

class AI_Blog_Generator {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_generate_articles', [$this, 'handle_generation']);
    }

    public function add_admin_page() {
        add_menu_page(
            'AI Blog Generator',
            'AI Generator',
            'manage_options',
            'ai-blog-generator',
            [$this, 'render_admin_ui'],
            'dashicons-edit-page'
        );
    }

    public function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_ai-blog-generator') return;
        
        wp_enqueue_style(
            'ai-generator-css', 
            plugins_url('assets/admin.css', __FILE__)
        );
        
        wp_enqueue_script(
            'ai-generator-js',
            plugins_url('assets/admin.js', __FILE__),
            ['jquery'],
            filemtime(plugin_dir_path(__FILE__) . 'assets/admin.js'),
            true
        );
        
        wp_localize_script('ai-generator-js', 'aiGenerator', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_blog_generator')
        ]);
    }

    public function render_admin_ui() {
        include plugin_dir_path(__FILE__) . 'templates/admin-form.php';
    }

    public function handle_generation() {
        check_ajax_referer('ai_blog_generator', 'nonce');
        
        if (!current_user_can('publish_posts')) {
            wp_send_json_error('Unzureichende Berechtigungen');
        }
        
        $category = absint($_POST['category']);
        $count = min(absint($_POST['count']), 5);
        $publish = $_POST['publish'] === 'true' ? 'publish' : 'draft';
        
        if (get_transient('ai_cooldown_' . get_current_user_id())) {
            wp_send_json_error('Bitte warten Sie 2 Minuten zwischen Generierungen');
        }
        set_transient('ai_cooldown_' . get_current_user_id(), 1, 120);
        
        $results = [];
        for ($i = 0; $i < $count; $i++) {
            $result = AI_Generator::create_post($category, $publish);
            if ($result && !is_wp_error($result)) {
                $results[] = get_post($result);
            }
        }
        
        wp_send_json_success($results);
    }
}

new AI_Blog_Generator();
