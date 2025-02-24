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
define('DEEPPOSTER_VERSION', '2.0.31');
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
    
    // AJAX Endpoints registrieren
    add_action('wp_ajax_deepposter_get_prompts', array($ajax, 'get_prompts'));
    add_action('wp_ajax_deepposter_get_prompt', array($ajax, 'get_prompt'));
    add_action('wp_ajax_deepposter_save_prompt', array($ajax, 'save_prompt'));
    add_action('wp_ajax_deepposter_generate', array($ajax, 'generate_articles'));
    
    // Initialisiere Admin - NUR EINMAL!
    static $admin = null;
    if ($admin === null) {
        $admin = new DeepPoster_Admin();
    }
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

// Initialisiere das Plugin - NUR EINMAL!
add_action('init', 'deepposter_init');

// AJAX Handler registrieren - vermeiden wir doppelte Registrierungen
// Diese Registrierungen wurden bereits in deepposter_init() vorgenommen
// add_action('wp_ajax_deepposter_generate', array('DeepPoster_Ajax', 'generate_articles'));
// add_action('wp_ajax_deepposter_save_prompt', array('DeepPoster_Ajax', 'save_prompt'));
// add_action('wp_ajax_deepposter_get_prompt', array('DeepPoster_Ajax', 'get_prompt'));
// add_action('wp_ajax_deepposter_get_prompts', array('DeepPoster_Ajax', 'get_prompts'));

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

// Admin Assets einbinden
add_action('admin_enqueue_scripts', 'deepposter_admin_assets');
function deepposter_admin_assets($hook) {
    // Nur auf Plugin-Seiten laden
    if (strpos($hook, 'deepposter') === false) {
        return;
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
    wp_localize_script(
        'deepposter-admin',
        'deepposterAdmin',
        array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('deepposter_nonce'),
            'debug' => DEEPPOSTER_DEBUG
        )
    );
}

// Entferne den zweiten Aufruf von plugin_init() auf plugins_loaded
// add_action('plugins_loaded', 'deepposter_init');

// Die DeepPoster Hauptklasse nur initialisieren, wenn es noch keine Admin-Instanz gibt
if (!isset($GLOBALS['deepposter_plugin']) && !isset($GLOBALS['deepposter_admin'])) {
    $GLOBALS['deepposter_plugin'] = new DeepPoster();
}

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
            }
        }
    }

    // Initialisiere alle Komponenten
    private function init_components() {
        // Die Admin-Klasse wird bereits in deepposter_init() initialisiert
        // Admin nicht erneut initialisieren
        
        // Speicher-Handler
        $this->settings = new DeepPoster_Settings();
        
        // Generator
        $this->generator = new DeepPoster_Generator($this->settings);
        
        // AJAX-Handler
        $this->ajax_handler = new DeepPoster_Ajax();
    }
} 