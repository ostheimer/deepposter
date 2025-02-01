<?php
/**
 * Plugin Name: DeepPoster
 * Description: KI-gestützte Content-Generierung mit Planungsfunktion
 * Version: 2.0
 * Author: Andreas Ostheimer
 */

defined('ABSPATH') || exit;

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

        wp_enqueue_style(
            'deepposter-admin',
            plugins_url('assets/admin.css', __FILE__),
            [],
            DEEPPOSTER_VERSION
        );

        wp_enqueue_script(
            'deepposter-js',
            plugins_url('assets/admin.js', __FILE__),
            ['jquery'],
            DEEPPOSTER_VERSION,
            true
        );

        // Debug-Ausgabe für Entwicklung
        if (DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Hook: ' . $hook);
            error_log('DeepPoster Debug - OpenAI Key vorhanden: ' . (get_option('deepposter_openai_key') ? 'Ja' : 'Nein'));
        }

        wp_localize_script('deepposter-js', 'deepposter', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('deepposter_nonce'),
            'openai_key' => !empty(get_option('deepposter_openai_key')),
            'debug' => DEEPPOSTER_DEBUG,
            'i18n' => array(
                'select_date' => __('Datum auswählen', 'deepposter'),
                'loading_models' => __('Lade Modelle...', 'deepposter'),
                'no_api_key' => __('Kein OpenAI API Key konfiguriert', 'deepposter'),
                'error_loading' => __('Fehler beim Laden der Modelle:', 'deepposter')
            )
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
    }

    public function scheduled_posts_check() {
        // Scheduler-Logik hier implementieren
    }
}

new DeepPoster(); 