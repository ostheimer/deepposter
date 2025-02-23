<?php
/**
 * Manages the admin functionality of the plugin
 */
class DeepPoster_Admin {
    
    /**
     * Initializes the admin functionality
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Adds the admin menu
     */
    public function add_admin_menu() {
        // Hauptmenü
        add_menu_page(
            'DeepPoster Generator',  // Seitentitel
            'DeepPoster',           // Menütitel
            'manage_options',       // Erforderliche Berechtigung
            'deepposter',          // Menü-Slug
            array($this, 'display_admin_page'),  // Callback-Funktion
            'dashicons-format-chat',  // Icon
            20  // Position
        );

        // Generator als erstes Untermenü (damit es als aktiv markiert wird)
        add_submenu_page(
            'deepposter',
            'Generator',
            'Generator',
            'manage_options',
            'deepposter',
            array($this, 'display_admin_page')
        );

        // Prompts verwalten
        add_submenu_page(
            'deepposter',
            'Prompts',
            'Alle Prompts',
            'manage_options',
            'edit.php?post_type=deepposter_prompt',
            null
        );

        // Einstellungen
        add_submenu_page(
            'deepposter',
            'DeepPoster Einstellungen',
            'Einstellungen',
            'manage_options',
            'deepposter-settings',
            array($this, 'display_plugin_settings_page')
        );

        // System Status
        add_submenu_page(
            'deepposter',
            'DeepPoster System Status',
            'System Status',
            'manage_options',
            'deepposter-status',
            array($this, 'display_plugin_status_page')
        );
    }

    /**
     * Loads the admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Only load scripts on plugin pages
        if (strpos($hook, 'deepposter') === false) {
            return;
        }

        // Register and load CSS
        wp_register_style(
            'deepposter-admin',
            plugins_url('../assets/css/deepposter-admin.css', __FILE__),
            array(),
            '1.0.0'
        );
        wp_enqueue_style('deepposter-admin');

        // Register and load JavaScript
        wp_register_script(
            'deepposter-admin',
            plugins_url('../assets/js/deepposter-admin.js', __FILE__),
            array('jquery'),
            '1.0.0',
            true
        );
        wp_enqueue_script('deepposter-admin');

        // Localize the script
        wp_localize_script(
            'deepposter-admin',
            'deepposterAdmin',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('deepposter_nonce')
            )
        );
    }

    /**
     * Displays the main admin page (prompt generator)
     */
    public function display_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Stelle sicher, dass die WordPress-Funktionen verfügbar sind
        if (!defined('ABSPATH')) {
            require_once dirname(dirname(dirname(__FILE__))) . '/wp-load.php';
        }
        
        require_once plugin_dir_path(__FILE__) . 'partials/deepposter-admin-display.php';
    }

    /**
     * Displays the settings page
     */
    public function display_plugin_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        require_once plugin_dir_path(__FILE__) . 'partials/deepposter-admin-settings.php';
    }

    /**
     * Displays the status page
     */
    public function display_plugin_status_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        require_once plugin_dir_path(__FILE__) . 'partials/deepposter-admin-status.php';
    }
}

// Deutsche Übersetzungen für die Admin-Menüs
$admin_translations = array(
    'DeepPoster' => 'DeepPoster',
    'Manage Prompts' => 'Prompts verwalten',
    'Prompts' => 'Prompts',
    'Settings' => 'Einstellungen',
    'System Status' => 'System Status',
    'You do not have sufficient permissions to access this page.' => 'Sie haben keine ausreichenden Berechtigungen, um auf diese Seite zuzugreifen.'
); 