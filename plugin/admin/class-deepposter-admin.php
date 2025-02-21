<?php
/**
 * Die Admin-spezifische Funktionalität des Plugins
 */
class DeepPoster_Admin {

    /**
     * Initialisiert die Klasse
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Registriert das Plugin im Admin-Menü
     */
    public function add_plugin_admin_menu() {
        add_menu_page(
            'DeepPoster', 
            'DeepPoster',
            'manage_options',
            'deepposter',
            array($this, 'display_plugin_admin_page'),
            'dashicons-admin-generic',
            30
        );

        add_submenu_page(
            'deepposter',
            'DeepPoster',
            'DeepPoster',
            'manage_options',
            'deepposter',
            array($this, 'display_plugin_admin_page')
        );

        add_submenu_page(
            'deepposter',
            'Einstellungen',
            'Einstellungen',
            'manage_options',
            'deepposter-settings',
            array($this, 'display_plugin_settings_page')
        );

        add_submenu_page(
            'deepposter',
            'System Status',
            'System Status',
            'manage_options',
            'deepposter-status',
            array($this, 'display_plugin_status_page')
        );
    }

    /**
     * Lädt die Admin-Skripte
     */
    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_deepposter' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'deepposter-admin',
            plugin_dir_url(__FILE__) . '../assets/css/deepposter-admin.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'deepposter-admin',
            plugin_dir_url(__FILE__) . '../assets/js/deepposter-admin.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script(
            'deepposter-admin',
            'deepposterParams',
            array(
                'nonce' => wp_create_nonce('deepposter_nonce')
            )
        );
    }

    /**
     * Zeigt die Hauptseite des Plugins an
     */
    public function display_plugin_admin_page() {
        require_once plugin_dir_path(__FILE__) . 'partials/deepposter-admin-display.php';
    }

    /**
     * Zeigt die Einstellungsseite an
     */
    public function display_plugin_settings_page() {
        require_once plugin_dir_path(__FILE__) . 'partials/deepposter-admin-settings.php';
    }

    /**
     * Zeigt die Status-Seite an
     */
    public function display_plugin_status_page() {
        require_once plugin_dir_path(__FILE__) . 'partials/deepposter-admin-status.php';
    }
} 