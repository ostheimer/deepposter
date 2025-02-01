<?php
/**
 * Plugin Name: DeepPoster
 * Description: KI-gestützte Content-Generierung mit Planungsfunktion
 * Version: 2.0
 * Author: Andreas Ostheimer
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin Konstanten definieren
define('DEEPPOSTER_VERSION', '2.0');
define('DEEPPOSTER_DEBUG', true);
define('DEEPPOSTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DEEPPOSTER_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once plugin_dir_path(__FILE__) . 'includes/settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/generator.php';
require_once plugin_dir_path(__FILE__) . 'includes/debug.php';
require_once plugin_dir_path(__FILE__) . 'includes/scheduler.php';

class DeepPoster {
    private $capability = 'manage_options';

    public function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
        add_action('wp_ajax_deepposter_generate', [$this, 'ajax_handler']);
        add_action('wp_ajax_deepposter_get_models', [$this, 'get_models_handler']);
        add_action('deepposter_daily_maintenance', [$this, 'scheduled_posts_check']);
    }

    public function admin_menu() {
        add_menu_page(
            'DeepPoster',
            'DeepPoster',
            $this->capability,
            'deepposter',
            [$this, 'dashboard_ui'],
            'dashicons-schedule',
            6
        );

        add_submenu_page(
            'deepposter',
            'Einstellungen',
            'Einstellungen',
            $this->capability,
            'deepposter-settings',
            [$this, 'settings_ui']
        );

        add_submenu_page(
            'deepposter',
            'System Status',
            'System Status',
            $this->capability,
            'deepposter-status',
            [$this, 'status_ui']
        );
    }

    public function admin_assets($hook) {
        if (strpos($hook, 'deepposter') === false) return;

        // Debug-Ausgabe für Entwicklung
        if (DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Hook: ' . $hook);
            error_log('DeepPoster Debug - OpenAI Key vorhanden: ' . (get_option('deepposter_openai_key') ? 'Ja' : 'Nein'));
            error_log('DeepPoster Debug - Plugin URL: ' . DEEPPOSTER_PLUGIN_URL);
        }

        // CSS einbinden
        wp_enqueue_style(
            'deepposter-admin',
            DEEPPOSTER_PLUGIN_URL . 'admin/css/deepposter-admin.css',
            [],
            DEEPPOSTER_VERSION
        );

        // JavaScript einbinden
        wp_enqueue_script(
            'deepposter-admin',
            DEEPPOSTER_PLUGIN_URL . 'admin/js/deepposter-admin.js',
            ['jquery'],
            DEEPPOSTER_VERSION,
            true
        );

        // JavaScript-Variablen lokalisieren
        wp_localize_script('deepposter-admin', 'deepposterAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('deepposter_nonce'),
            'openai_key' => !empty(get_option('deepposter_openai_key')),
            'debug' => DEEPPOSTER_DEBUG
        ));
    }

    public function dashboard_ui() {
        include plugin_dir_path(__FILE__) . 'templates/dashboard.php';
    }

    public function settings_ui() {
        include plugin_dir_path(__FILE__) . 'templates/settings.php';
    }

    public function status_ui() {
        include plugin_dir_path(__FILE__) . 'templates/status.php';
    }

    public function ajax_handler() {
        check_ajax_referer('deepposter_nonce', 'nonce');
        
        if (!current_user_can($this->capability)) {
            wp_send_json_error(__('Zugriff verweigert', 'deepposter'), 403);
        }

        // Validiere Eingaben
        $category_id = isset($_POST['category']) ? intval($_POST['category']) : 0;
        $count = isset($_POST['count']) ? min(5, max(1, intval($_POST['count']))) : 1;
        $should_publish = isset($_POST['publish']) && $_POST['publish'] === 'true';
        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';

        if (DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - AJAX Handler:');
            error_log('Category: ' . $category_id);
            error_log('Count: ' . $count);
            error_log('Publish: ' . ($should_publish ? 'yes' : 'no'));
            error_log('Prompt length: ' . strlen($prompt));
        }

        if (empty($category_id)) {
            wp_send_json_error('Bitte wählen Sie eine Kategorie aus.');
            return;
        }

        if (empty($prompt)) {
            wp_send_json_error('Bitte geben Sie ein Prompt ein.');
            return;
        }

        try {
            // Hole OpenAI API Key
            $api_key = get_option('deepposter_openai_key');
            if (empty($api_key)) {
                wp_send_json_error('Bitte hinterlegen Sie zuerst Ihren OpenAI API Key in den Einstellungen.');
                return;
            }

            if (DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - API Key gefunden, starte Generator');
            }

            // Initialisiere Generator
            require_once DEEPPOSTER_PLUGIN_DIR . 'includes/class-deepposter-generator.php';
            $generator = new DeepPoster_Generator($api_key);
            
            // Generiere Artikel
            $posts = $generator->generate_posts($category_id, $count, $should_publish, $prompt);
            
            if (DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Generated posts: ' . print_r($posts, true));
            }

            if (empty($posts)) {
                wp_send_json_error('Es konnten keine Artikel generiert werden.');
                return;
            }

            wp_send_json_success($posts);
        } catch (Exception $e) {
            if (DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Error: ' . $e->getMessage());
                error_log('DeepPoster Debug - Stack trace: ' . $e->getTraceAsString());
            }
            wp_send_json_error($e->getMessage());
        }
    }

    public function get_models_handler() {
        check_ajax_referer('deepposter_nonce', 'nonce');
        
        if (!current_user_can($this->capability)) {
            wp_send_json_error(__('Zugriff verweigert', 'deepposter'), 403);
        }

        try {
            $api_key = get_option('deepposter_openai_key');
            if (empty($api_key)) {
                wp_send_json_error('OpenAI API Key nicht gefunden');
                return;
            }

            $response = wp_remote_get('https://api.openai.com/v1/models', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json'
                ]
            ]);

            if (is_wp_error($response)) {
                wp_send_json_error($response->get_error_message());
                return;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            
            if (empty($body['data'])) {
                wp_send_json_error('Keine Modelle gefunden');
                return;
            }

            // Filtere nur die Chat-Modelle
            $chat_models = array_filter($body['data'], function($model) {
                return strpos($model['id'], 'gpt') !== false;
            });

            wp_send_json_success(array_values($chat_models));
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function scheduled_posts_check() {
        // Scheduler-Logik hier implementieren
    }
}

new DeepPoster(); 