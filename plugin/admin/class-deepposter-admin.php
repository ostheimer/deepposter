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
        add_action('admin_init', array($this, 'register_settings'));
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
            'Prompts verwalten',
            'Prompts verwalten',
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

    /**
     * Registriert die Plugin-Einstellungen
     */
    public function register_settings() {
        register_setting('deepposter_settings', 'deepposter_api_provider');
        register_setting('deepposter_settings', 'deepposter_openai_api_key');
        register_setting('deepposter_settings', 'deepposter_deepseek_key');
        register_setting('deepposter_settings', 'deepposter_model');
        register_setting('deepposter_settings', 'deepposter_max_tokens');
        register_setting('deepposter_settings', 'deepposter_temperature');

        add_settings_section(
            'deepposter_main_section',
            'API Einstellungen',
            array($this, 'settings_section_callback'),
            'deepposter_settings'
        );

        add_settings_field(
            'deepposter_api_provider',
            'AI Provider',
            array($this, 'api_provider_callback'),
            'deepposter_settings',
            'deepposter_main_section'
        );

        add_settings_field(
            'deepposter_openai_api_key',
            'OpenAI API Key',
            array($this, 'openai_api_key_callback'),
            'deepposter_settings',
            'deepposter_main_section'
        );

        add_settings_field(
            'deepposter_model',
            'Model Selection',
            array($this, 'model_selection_callback'),
            'deepposter_settings',
            'deepposter_main_section'
        );

        add_settings_field(
            'deepposter_max_tokens',
            'Max Tokens',
            array($this, 'max_tokens_callback'),
            'deepposter_settings',
            'deepposter_main_section'
        );

        add_settings_field(
            'deepposter_temperature',
            'Temperature',
            array($this, 'temperature_callback'),
            'deepposter_settings',
            'deepposter_main_section'
        );
    }

    /**
     * Callback für die Settings Section
     */
    public function settings_section_callback() {
        echo '<p>Konfigurieren Sie hier Ihre API-Einstellungen für die KI-Provider.</p>';
    }

    /**
     * Callback für API Provider Einstellung
     */
    public function api_provider_callback() {
        $provider = get_option('deepposter_api_provider', 'openai');
        ?>
        <select name="deepposter_api_provider" id="api_provider">
            <option value="openai" <?php selected($provider, 'openai'); ?>>OpenAI</option>
            <option value="deepseek" <?php selected($provider, 'deepseek'); ?>>DeepSeek</option>
        </select>
        <p class="description">Wählen Sie Ihren KI-Provider</p>
        <?php
    }

    /**
     * Callback für OpenAI API Key Einstellung
     */
    public function openai_api_key_callback() {
        $api_key = get_option('deepposter_openai_api_key', '');
        ?>
        <input type="password" 
               id="openai_key" 
               name="deepposter_openai_api_key" 
               value="<?php echo esc_attr($api_key); ?>" 
               class="regular-text">
        <p class="description">Geben Sie Ihren OpenAI API-Schlüssel ein</p>
        <?php
    }

    /**
     * Callback für Model Selection Einstellung
     */
    public function model_selection_callback() {
        $model = get_option('deepposter_model', '');
        ?>
        <div class="model-selection-wrapper">
            <select name="deepposter_model" id="model_selection" style="min-width: 200px;">
                <option value="">Lade Modelle...</option>
            </select>
            <button type="button" id="refresh-models" class="button" style="margin-left: 10px;">
                <span class="dashicons dashicons-update"></span> Modelle aktualisieren
            </button>
            <span id="loading-models" style="display:none; margin-left: 10px;">
                <img src="<?php echo admin_url('images/spinner.gif'); ?>" alt="Loading...">
            </span>
        </div>
        <p class="description">Wählen Sie das zu verwendende KI-Modell</p>
        <p class="model-description" style="font-style: italic; margin-top: 5px;"></p>
        <?php
    }

    /**
     * Callback für Max Tokens Einstellung
     */
    public function max_tokens_callback() {
        $max_tokens = get_option('deepposter_max_tokens', 16000);
        ?>
        <input type="number" 
               id="max_tokens" 
               name="deepposter_max_tokens" 
               value="<?php echo esc_attr($max_tokens); ?>" 
               min="1" 
               max="128000">
        <p class="description">Maximale Anzahl der Tokens pro Anfrage (1-128000)</p>
        <?php
    }

    /**
     * Callback für Temperature Einstellung
     */
    public function temperature_callback() {
        $temperature = get_option('deepposter_temperature', 0.7);
        ?>
        <input type="number" 
               id="temperature" 
               name="deepposter_temperature" 
               value="<?php echo esc_attr($temperature); ?>" 
               min="0" 
               max="1" 
               step="0.1">
        <p class="description">KI-Kreativitätslevel (0 = konservativ, 1 = kreativ)</p>
        <?php
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