<?php
/**
 * Plugin Name: DeepPoster
 * Description: KI-gestützte Content-Generierung mit Planungsfunktion
 * Version: 2.0
 * Author: Andreas Ostheimer
 */

// Sicherstellen, dass WordPress geladen ist
if (!defined('WPINC')) {
    die('WordPress nicht geladen');
}

// WordPress Core-Funktionen
require_once ABSPATH . 'wp-includes/plugin.php';
require_once ABSPATH . 'wp-includes/formatting.php';
require_once ABSPATH . 'wp-includes/category.php';
require_once ABSPATH . 'wp-includes/pluggable.php';

// Plugin Konstanten definieren
define('DEEPPOSTER_VERSION', '2.0');
define('DEEPPOSTER_DEBUG', true);
define('DEEPPOSTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DEEPPOSTER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Aktiviere WordPress Debug-Logging
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}
if (!defined('WP_DEBUG_LOG')) {
    define('WP_DEBUG_LOG', true);
}
if (!defined('WP_DEBUG_DISPLAY')) {
    define('WP_DEBUG_DISPLAY', false);
}

// Lade Abhängigkeiten
require_once DEEPPOSTER_PLUGIN_DIR . 'includes/class-deepposter-dependencies.php';
require_once DEEPPOSTER_PLUGIN_DIR . 'includes/class-deepposter-generator.php';
require_once DEEPPOSTER_PLUGIN_DIR . 'includes/class-deepposter-settings.php';
require_once DEEPPOSTER_PLUGIN_DIR . 'includes/class-deepposter-post-types.php';
require_once DEEPPOSTER_PLUGIN_DIR . 'includes/class-deepposter-ajax.php';
require_once DEEPPOSTER_PLUGIN_DIR . 'admin/class-deepposter-admin.php';

// Plugin initialisieren
function deepposter_init() {
    if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
        error_log('DeepPoster Debug - Plugin wird initialisiert');
    }

    // Initialisiere Post Types zuerst
    $post_types = new DeepPoster_Post_Types();
    $post_types->register_post_types();
    
    // Dann initialisiere AJAX Handler
    $ajax = new DeepPoster_Ajax();
    
    // Zuletzt initialisiere Admin
    $admin = new DeepPoster_Admin();
}

// Plugin aktivieren
register_activation_hook(__FILE__, 'deepposter_activate');
function deepposter_activate() {
    // Initialisiere Post Types bei Aktivierung
    $post_types = new DeepPoster_Post_Types();
    $post_types->register_post_types();
    
    // Spüle die Rewrite Rules
    flush_rewrite_rules();
}

// Plugin deaktivieren
register_deactivation_hook(__FILE__, 'deepposter_deactivate');
function deepposter_deactivate() {
    // Spüle die Rewrite Rules
    flush_rewrite_rules();
}

// Registriere Einstellungen
add_action('admin_init', function() {
    // API Provider Settings
    register_setting('deepposter_settings', 'deepposter_api_provider');
    register_setting('deepposter_settings', 'deepposter_openai_key');
    register_setting('deepposter_settings', 'deepposter_deepseek_key');
    
    // Model Settings
    register_setting('deepposter_settings', 'deepposter_model', array(
        'default' => 'gpt-4'
    ));
    register_setting('deepposter_settings', 'deepposter_max_tokens', array(
        'default' => 10000
    ));
    register_setting('deepposter_settings', 'deepposter_temperature', array(
        'default' => 0.7
    ));
});

// Initialisiere das Plugin
add_action('init', 'deepposter_init');

// AJAX Handler registrieren
add_action('wp_ajax_deepposter_generate', array('DeepPoster_Ajax', 'generate_articles'));
add_action('wp_ajax_deepposter_save_prompt', array('DeepPoster_Ajax', 'save_prompt'));
add_action('wp_ajax_deepposter_get_prompt', array('DeepPoster_Ajax', 'get_prompt'));
add_action('wp_ajax_deepposter_get_prompts', array('DeepPoster_Ajax', 'get_prompts'));

add_action('wp_ajax_deepposter_get_models', function() {
    check_ajax_referer('deepposter_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Keine Berechtigung');
        return;
    }

    $api_key = get_option('deepposter_openai_key');
    if (empty($api_key)) {
        wp_send_json_error('Kein OpenAI API Key konfiguriert');
        return;
    }

    if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
        error_log('DeepPoster Debug - Starte API-Anfrage an OpenAI');
    }

    $response = wp_remote_get('https://api.openai.com/v1/models', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ],
        'timeout' => 15
    ]);

    if (is_wp_error($response)) {
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - API-Fehler: ' . $response->get_error_message());
        }
        wp_send_json_error('API-Fehler: ' . $response->get_error_message());
        return;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Ungültiger Status Code: ' . $status_code);
        }
        wp_send_json_error('API-Fehler: Status ' . $status_code);
        return;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($body['data']) || !is_array($body['data'])) {
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Ungültige API-Antwort: ' . print_r($body, true));
        }
        wp_send_json_error('Ungültige API-Antwort');
        return;
    }

    // Filtere nur die Chat-Modelle
    $chat_models = array_filter($body['data'], function($model) {
        return strpos($model['id'], 'gpt') !== false;
    });

    if (empty($chat_models)) {
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Keine GPT-Modelle gefunden');
        }
        wp_send_json_success([]); // Leeres Array, aber success=true
        return;
    }

    // Formatiere die Modelle für die Anzeige
    $formatted_models = array_map(function($model) {
        return [
            'id' => $model['id'],
            'name' => ucwords(str_replace('-', ' ', $model['id']))
        ];
    }, $chat_models);

    if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
        error_log('DeepPoster Debug - Gefundene Modelle: ' . print_r($formatted_models, true));
    }

    wp_send_json_success(array_values($formatted_models));
});

// Plugin initialisieren
add_action('plugins_loaded', function() {
    if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
        error_log('DeepPoster Debug - Plugin wird geladen');
    }
    
    new DeepPoster();
});

class DeepPoster {
    private $capability = 'manage_options';
    private $generator;
    private $settings;
    private $ajax_handler;

    public function __construct() {
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Plugin wird initialisiert');
        }

        // Initialisiere Komponenten
        try {
            $this->init_components();
        } catch (Exception $e) {
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Fehler bei der Initialisierung:');
                error_log($e->getMessage());
                error_log($e->getTraceAsString());
            }
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error"><p>DeepPoster Fehler: ' . esc_html($e->getMessage()) . '</p></div>';
            });
            return;
        }

        // Registriere Hooks
        add_action('admin_enqueue_scripts', array($this, 'admin_assets'));
        add_action('wp_ajax_deepposter_get_models', array($this->ajax_handler, 'get_models'));
        add_action('wp_ajax_deepposter_save_model', array($this->ajax_handler, 'save_model'));
        add_action('deepposter_daily_maintenance', array($this, 'scheduled_posts_check'));

        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Actions registriert');
        }
    }

    private function init_components() {
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Initialisiere Komponenten');
        }

        // Prüfe API Key
        $api_key = get_option('deepposter_openai_key');
        if (empty($api_key)) {
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Kein API Key konfiguriert');
            }
            throw new Exception('OpenAI API Key ist nicht konfiguriert. Bitte konfigurieren Sie den API Key in den Einstellungen.');
        }

        // Initialisiere Generator
        $this->generator = new DeepPoster_Generator($api_key);
        
        // Initialisiere Settings
        $this->settings = new Deepposter_Settings();
        
        // Initialisiere AJAX Handler
        $this->ajax_handler = new DeepPoster_Ajax($this->generator);

        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Komponenten initialisiert');
        }
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
            DEEPPOSTER_PLUGIN_URL . 'assets/css/deepposter-admin.css',
            array(),
            DEEPPOSTER_VERSION
        );

        // JavaScript einbinden
        wp_enqueue_script(
            'deepposter-admin',
            DEEPPOSTER_PLUGIN_URL . 'assets/js/deepposter-admin.js',
            array('jquery'),
            DEEPPOSTER_VERSION,
            true
        );

        // JavaScript-Variablen lokalisieren
        wp_localize_script('deepposter-admin', 'deepposterAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('deepposter_nonce'),
            'openai_key' => !empty(get_option('deepposter_openai_key')),
            'debug' => DEEPPOSTER_DEBUG,
            'saved_model' => get_option('deepposter_model', 'gpt-4')
        ));
    }

    public function scheduled_posts_check() {
        // Scheduler-Logik hier implementieren
    }
} 