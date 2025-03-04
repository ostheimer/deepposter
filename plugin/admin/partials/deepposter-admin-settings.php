<?php
/**
 * Template for the DeepPoster settings page
 */

// If no direct access
if (!defined('WPINC')) {
    die;
}

// Ensure WordPress functions are available
if (!defined('ABSPATH')) {
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
}

// Load required JavaScript files
wp_enqueue_script('jquery');
wp_enqueue_style('dashicons');
wp_enqueue_script(
    'deepposter-admin',
    plugins_url('assets/js/deepposter-admin.js', dirname(dirname(__FILE__))),
    array('jquery'),
    '1.0.0',
    true
);

// Localize the script
wp_localize_script(
    'deepposter-admin',
    'deepposterAdmin',
    array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('deepposter_nonce'),
        'debug' => defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG
    )
);

// Ensure scripts are loaded
do_action('admin_enqueue_scripts');
?>

<div class="wrap">
    <h1>DeepPoster Einstellungen</h1>
    
    <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true'): ?>
        <div id="setting-error-settings_updated" class="notice notice-success settings-error is-dismissible"> 
            <p><strong>Einstellungen gespeichert.</strong></p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text">Diese Meldung ausblenden.</span>
            </button>
        </div>
    <?php endif; ?>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('deepposter_settings');
        do_settings_sections('deepposter_settings');
        submit_button('Einstellungen speichern');
        ?>
    </form>
</div>

<style>
.form-table th {
    width: 200px;
}
.form-table input[type="number"] {
    width: 100px;
}
.form-table select {
    min-width: 200px;
}
.description {
    margin-top: 5px;
    color: #666;
}
.notice-dismiss {
    position: absolute;
    top: 0;
    right: 1px;
    border: none;
    margin: 0;
    padding: 9px;
    background: none;
    color: #787c82;
    cursor: pointer;
}
.notice-dismiss:before {
    background: none;
    color: #787c82;
    content: "\f153";
    display: block;
    font: normal 16px/20px dashicons;
    speak: never;
    height: 20px;
    text-align: center;
    width: 20px;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}
.notice-dismiss:hover:before {
    color: #d63638;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Entferne die "Prompts geladen" Meldung sofort
    $('.notice:contains("Prompts geladen")').remove();
    
    // Event-Handler für das Ausblenden der Erfolgsmeldung
    $('.notice-dismiss').on('click', function() {
        $(this).closest('.notice').fadeOut();
    });

    // Entferne die "Prompts geladen" Meldung auch nach AJAX-Requests
    $(document).ajaxComplete(function() {
        $('.notice:contains("Prompts geladen")').remove();
    });
});
</script>

<?php
// Deutsche Übersetzung der Einstellungsseite
$translations = array(
    'DeepPoster Settings' => 'DeepPoster Einstellungen',
    'AI Provider' => 'KI-Anbieter',
    'Select your AI provider' => 'Wählen Sie Ihren KI-Anbieter',
    'Enter your OpenAI API key' => 'Geben Sie Ihren OpenAI API-Schlüssel ein',
    'Enter your DeepSeek API key' => 'Geben Sie Ihren DeepSeek API-Schlüssel ein',
    'Model Selection' => 'Modellauswahl',
    'Select the AI model to use' => 'Wählen Sie das zu verwendende KI-Modell',
    'Max Tokens' => 'Maximale Tokens',
    'Maximum number of tokens per request (1-128000)' => 'Maximale Anzahl der Tokens pro Anfrage (1-128000)',
    'Temperature' => 'Temperatur',
    'AI creativity level (0 = conservative, 1 = creative)' => 'KI-Kreativitätslevel (0 = konservativ, 1 = kreativ)',
    'Save Settings' => 'Einstellungen speichern'
);
?> 