<?php
/**
 * Hauptklasse für das DeepPoster Plugin
 */
class DeepPoster {
    
    /**
     * Die einzige Instanz dieser Klasse
     */
    private static $instance = null;

    /**
     * Der Konstruktor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
    }

    /**
     * Gibt die einzige Instanz dieser Klasse zurück
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Lädt die erforderlichen Abhängigkeiten
     */
    private function load_dependencies() {
        // WordPress Core-Funktionen
        require_once ABSPATH . 'wp-includes/formatting.php';
        require_once ABSPATH . 'wp-includes/category.php';
        require_once ABSPATH . 'wp-includes/pluggable.php';
        
        // Plugin-Klassen
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-deepposter-post-types.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-deepposter-ajax.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-deepposter-generator.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-deepposter-admin.php';
    }

    /**
     * Registriert alle Hooks für den Admin-Bereich
     */
    private function define_admin_hooks() {
        // Initialisiere Post Types
        $post_types = new DeepPoster_Post_Types();

        // Initialisiere AJAX Handler
        $ajax = new DeepPoster_Ajax();

        // Initialisiere Admin
        $admin = new DeepPoster_Admin();

        // Registriere Admin-Menü
        add_action('admin_menu', array($admin, 'add_admin_menu'));

        // Registriere Admin-Assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Registriert die Admin-Assets
     */
    public function enqueue_admin_assets($hook) {
        // Lade Assets nur auf der Plugin-Seite
        if ('toplevel_page_deepposter' !== $hook) {
            return;
        }

        // Registriere und lade JavaScript
        wp_enqueue_script(
            'deepposter-admin',
            plugins_url('assets/js/deepposter-admin.js', dirname(__FILE__)),
            array('jquery'),
            '1.0.0',
            true
        );

        // Lokalisiere das Script
        wp_localize_script(
            'deepposter-admin',
            'deepposterAdmin',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('deepposter_nonce')
            )
        );
    }
} 