<?php
/**
 * Main dashboard template for DeepPoster
 *
 * @package DeepPoster
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get saved settings
$api_provider = get_option('deepposter_api_provider', 'openai');
$active_prompt = get_option('deepposter_active_prompt', 0);

// Stelle sicher, dass die WordPress-Funktionen verfügbar sind
if (!defined('ABSPATH')) {
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
}

// Lade die benötigten JavaScript-Dateien
wp_enqueue_script('jquery');

// Debug-Ausgabe für JavaScript-Loading
$js_url = plugins_url('assets/js/deepposter-admin.js', dirname(dirname(__FILE__)));
error_log('Loading DeepPoster Admin JS from: ' . $js_url);

wp_enqueue_script(
    'deepposter-admin',
    $js_url,
    array('jquery'),
    filemtime(plugin_dir_path(dirname(dirname(__FILE__))) . 'assets/js/deepposter-admin.js'),
    true
);

// Debug-Ausgabe für Script-Lokalisierung
$script_data = array(
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('deepposter_nonce'),
    'debug' => true
);
error_log('Localizing DeepPoster Admin JS with data: ' . print_r($script_data, true));

// Lokalisiere das Script
wp_localize_script(
    'deepposter-admin',
    'deepposterAdmin',
    $script_data
);

// Stelle sicher, dass die Skripte geladen werden
do_action('admin_enqueue_scripts');
?>

<div class="wrap">
    <h1>DeepPoster Generator</h1>

    <div class="deepposter-grid">
        <div class="deepposter-main">
            <form id="deepposterSettingsForm" method="post" action="">
                <?php wp_nonce_field('deepposter_nonce', 'nonce'); ?>
                
                <h2>Gespeicherte Prompts:</h2>
                <div class="form-group">
                    <select id="promptSelect" name="prompt" class="regular-text">
                        <option value="">Prompt auswählen</option>
                    </select>
                    <p class="description">
                        Wählen Sie einen gespeicherten Prompt aus.
                    </p>
                </div>

                <h2>Kategorie auswählen:</h2>
                <div class="form-group">
                    <select id="categorySelect" name="category" class="regular-text">
                        <option value="">Kategorie wählen</option>
                        <?php
                        $categories = get_categories(array('hide_empty' => false));
                        foreach ($categories as $category) {
                            echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
                        }
                        ?>
                    </select>
                    <p class="description">
                        Wählen Sie die Kategorie für die generierten Artikel.
                    </p>
                </div>

                <h2>Anzahl der Artikel:</h2>
                <div class="form-group">
                    <input type="number" id="articleCount" name="count" value="1" min="1" max="10" class="small-text">
                    <p class="description">
                        Wie viele Artikel sollen generiert werden? (1-10)
                    </p>
                </div>

                <h2>Veröffentlichung:</h2>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="publishArticles" name="publish" value="true">
                        Artikel direkt veröffentlichen
                    </label>
                </div>

                <div class="submit-button">
                    <button type="submit" id="generateButton" class="button button-primary">
                        Artikel generieren
                    </button>
                </div>
            </form>

            <div id="generationResults"></div>
        </div>

        <div class="deepposter-sidebar">
            <div class="prompt-preview">
                <h3>Ausgewählter Prompt:</h3>
                <div id="selectedPromptContent" class="prompt-content">
                    <em>Bitte wählen Sie einen Prompt aus.</em>
                </div>
                
                <!-- Temporärer Test-Bereich wurde entfernt, da nicht mehr benötigt -->
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    console.log('DOM ready - Optimierte Implementierung');
    
    // Direkter Event-Handler für Dropdown-Änderungen
    $('#promptSelect').on('change', function() {
        var promptId = $(this).val();
        console.log('Prompt-ID geändert zu:', promptId);
        
        if (!promptId) {
            $('#selectedPromptContent').html('<em>Bitte wählen Sie einen Prompt aus.</em>');
            return;
        }
        
        // Ladestatus anzeigen
        $('#selectedPromptContent').html('<em>Lade Prompt...</em>');
        
        // Optimierte AJAX-Anfrage basierend auf der erfolgreichen Testversion
        $.ajax({
            url: deepposterAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'deepposter_get_prompt_content',
                prompt_id: promptId,
                nonce: deepposterAdmin.nonce
            },
            success: function(response) {
                console.log('AJAX-Antwort:', response);
                
                if (response && response.success && response.data && response.data.content) {
                    // Content direkt in das DOM-Element einfügen
                    $('#selectedPromptContent').html(response.data.content);
                    console.log('Prompt-Inhalt erfolgreich geladen');
                } else {
                    $('#selectedPromptContent').html('<em>Fehler beim Laden des Prompts</em>');
                    console.error('Fehlerhafte Antwortdaten:', response);
                }
            },
            error: function(xhr, status, error) {
                $('#selectedPromptContent').html('<em>Fehler: ' + error + '</em>');
                console.error('AJAX-Fehler:', status, error);
            }
        });
    });
    
    // Initiale Ladung des ausgewählten Prompts
    var initialPromptId = $('#promptSelect').val();
    if (initialPromptId) {
        console.log('Lade initialen Prompt:', initialPromptId);
        $('#promptSelect').trigger('change');
    }
});
</script>

<style>
.deepposter-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
    max-width: 1200px;
    margin-top: 20px;
}

.deepposter-main {
    padding-right: 30px;
}

.deepposter-sidebar {
    background: #f9f9f9;
    border-radius: 4px;
    padding: 15px;
    border: 1px solid #ddd;
}

.prompt-preview {
    position: sticky;
    top: 32px;
}

.prompt-preview h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #23282d;
    font-size: 14px;
    font-weight: 600;
}

.prompt-content {
    font-size: 13px;
    line-height: 1.5;
    color: #444;
    white-space: pre-wrap;
    padding: 15px;
    background: #fff;
    border: 1px solid #e5e5e5;
    border-radius: 3px;
    min-height: 100px;
    max-height: 400px;
    overflow-y: auto;
}

.ai-generator {
    max-width: 800px;
    margin-top: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group select,
.form-group textarea {
    width: 100%;
    margin-bottom: 10px;
}

.form-group .description {
    color: #666;
    font-style: italic;
    margin-top: 5px;
}

#generationResults {
    margin-top: 20px;
}

#generationResults .notice {
    margin: 5px 0 15px;
}

#generationResults ul {
    margin: 0;
    padding: 0;
    list-style: none;
}

#generationResults li {
    margin-bottom: 15px;
    padding: 10px;
    background: #f9f9f9;
    border: 1px solid #e5e5e5;
}

#generationResults .button {
    margin-right: 10px;
    margin-top: 5px;
}

.notice {
    margin: 20px 0;
    padding: 10px;
    border-radius: 4px;
}

.notice-error {
    border-left: 4px solid #dc3232;
    background: #fef7f7;
}

.notice-success {
    border-left: 4px solid #46b450;
    background: #f7fff7;
}

.notice-info {
    border-left: 4px solid #00a0d2;
    background: #f7fcfe;
}
</style>

<?php
// Deutsche Übersetzung der Hauptseite
$translations = array(
    'DeepPoster' => 'DeepPoster',
    'Saved Prompts:' => 'Gespeicherte Prompts:',
    'Select a prompt' => 'Prompt auswählen',
    'Select a saved prompt or create a new one.' => 'Wählen Sie einen gespeicherten Prompt aus oder erstellen Sie einen neuen.',
    'Prompt Preview & Customization:' => 'Prompt Vorschau & Anpassung:',
    'Here you can customize the prompt that will be sent to the AI.' => 'Hier können Sie das Prompt anpassen, das an die KI gesendet wird.',
    'The placeholders [CATEGORY] will be replaced automatically.' => 'Die Platzhalter [KATEGORIE] werden automatisch ersetzt.',
    'Save Prompt' => 'Prompt speichern',
    'Select Category:' => 'Kategorie auswählen:',
    'Choose category' => 'Kategorie wählen',
    'Number of Articles:' => 'Anzahl Artikel:',
    'Publish Immediately' => 'Sofort veröffentlichen',
    'Generate Articles' => 'Artikel generieren'
);
?>

<?php
// Debug-Ausgabe
if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
    error_log('DeepPoster Debug - Template geladen');
    error_log('Vorschau-Element ID: selectedPromptContent');
}
?> 