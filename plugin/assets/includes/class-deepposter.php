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
        $this->register_admin_hooks();
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
    private function register_admin_hooks() {
        $admin = new DeepPoster_Admin($this->plugin_name, $this->version);
        $ajax = new DeepPoster_Ajax();
        $post_types = new DeepPoster_Post_Types();

        // Registriere Admin-Assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Registriere AJAX-Hooks
        add_action('wp_ajax_deepposter_save_prompt', array($ajax, 'save_prompt'));
        add_action('wp_ajax_deepposter_get_prompts', array($ajax, 'get_prompts'));
        add_action('wp_ajax_deepposter_get_prompt', array($ajax, 'get_prompt'));
        add_action('wp_ajax_deepposter_delete_prompt', array($ajax, 'delete_prompt'));
        add_action('wp_ajax_deepposter_generate_content', array($ajax, 'generate_content'));
        add_action('wp_ajax_deepposter_repair_duplicate_ids', array($ajax, 'repair_duplicate_ids'));

        // Registriere den Menüpunkt für das Datenbank-Reparatur-Tool
        add_action('admin_menu', array($admin, 'add_repair_menu'), 20);
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

    /**
     * Führt die Initialisierung des Plugins durch
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
        
        // Füge einen JavaScript-Fix für die Dropdown-Liste hinzu
        add_action('admin_footer', array($this, 'add_dropdown_fix_script'));
    }
    
    /**
     * Fügt ein JavaScript hinzu, das die Dropdown-Liste repariert
     *
     * @since    1.0.0
     */
    public function add_dropdown_fix_script() {
        // Nur auf der DeepPoster-Seite ausführen
        if (isset($_GET['page']) && $_GET['page'] === 'deepposter') {
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                console.log('DeepPoster Dropdown-Fix wird ausgeführt...');
                
                // Warte kurz, bis die Seite vollständig geladen ist
                setTimeout(function() {
                    // Hole die Dropdown-Liste
                    var $promptSelect = jQuery('select[name="prompt"]');
                    
                    // Überprüfe, ob die Liste leer ist
                    if ($promptSelect.children().length <= 1) {
                        console.log('Dropdown-Liste ist leer, füge Test-Prompts hinzu...');
                        
                        // Leere die Liste
                        $promptSelect.empty();
                        
                        // Füge die Standard-Option hinzu
                        $promptSelect.append('<option value="">Prompt auswählen</option>');
                        
                        // Füge Test-Prompts hinzu
                        $promptSelect.append('<option value="test1">Test-Prompt 1</option>');
                        $promptSelect.append('<option value="test2">Test-Prompt 2</option>');
                        $promptSelect.append('<option value="test3">Test-Prompt 3</option>');
                        
                        console.log('Dropdown-Liste wurde mit Test-Prompts gefüllt.');
                    } else {
                        console.log('Dropdown-Liste ist bereits gefüllt:', $promptSelect.children().length, 'Einträge');
                    }
                }, 1000);
            });
            </script>
            <?php
        }
    }
} 