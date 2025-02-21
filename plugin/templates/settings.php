<?php
defined('ABSPATH') || exit;

// Stelle sicher, dass die WordPress-Funktionen verfügbar sind
require_once ABSPATH . 'wp-includes/option.php';
require_once ABSPATH . 'wp-includes/formatting.php';

// Registriere die Einstellungen
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
?>

<div class="wrap">
    <h2>DeepPoster Einstellungen</h2>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('deepposter_settings');
        do_settings_sections('deepposter_settings');
        ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="api_provider">KI-Anbieter</label>
                </th>
                <td>
                    <select name="deepposter_api_provider" id="api_provider">
                        <option value="openai" <?php selected(get_option('deepposter_api_provider'), 'openai'); ?>>OpenAI</option>
                        <option value="deepseek" <?php selected(get_option('deepposter_api_provider'), 'deepseek'); ?>>DeepSeek</option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="openai_key">OpenAI API Key</label>
                </th>
                <td>
                    <input type="password" 
                           id="openai_key" 
                           name="deepposter_openai_key" 
                           value="<?php echo esc_attr(get_option('deepposter_openai_key')); ?>" 
                           class="regular-text">
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="deepseek_key">DeepSeek API Key</label>
                </th>
                <td>
                    <input type="password" 
                           id="deepseek_key" 
                           name="deepposter_deepseek_key" 
                           value="<?php echo esc_attr(get_option('deepposter_deepseek_key')); ?>" 
                           class="regular-text">
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="model_selection">Modellauswahl</label>
                </th>
                <td>
                    <div class="model-selection-wrapper">
                        <select name="deepposter_model" id="model_selection">
                            <option value="<?php echo esc_attr(get_option('deepposter_model', 'gpt-4')); ?>">
                                <?php echo esc_html(get_option('deepposter_model', 'gpt-4')); ?>
                            </option>
                        </select>
                        <div id="loading-models" class="loading-indicator">Lade Modelle...</div>
                        <button type="button" id="refresh-models" class="button">Modelle aktualisieren</button>
                    </div>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="max_tokens">Maximale Tokens</label>
                </th>
                <td>
                    <input type="number" 
                           id="max_tokens" 
                           name="deepposter_max_tokens" 
                           value="<?php echo esc_attr(get_option('deepposter_max_tokens', 10000)); ?>"
                           min="1"
                           max="128000"
                           step="1"
                           class="regular-text">
                    <p class="description">
                        Maximale Anzahl der Tokens pro Anfrage. Empfohlene Werte:<br>
                        - GPT-4 Modelle: bis zu 128000 Tokens<br>
                        - GPT-3.5-Turbo-16k: bis zu 16000 Tokens<br>
                        - Andere Modelle: bis zu 8000 Tokens
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="temperature">Temperature</label>
                </th>
                <td>
                    <input type="number" 
                           id="temperature" 
                           name="deepposter_temperature" 
                           value="<?php echo esc_attr(get_option('deepposter_temperature', 0.7)); ?>"
                           min="0"
                           max="1"
                           step="0.1"
                           class="regular-text">
                    <p class="description">
                        Die Temperatur für die Generierung von Antworten.<br>
                        0 = sehr konservativ/vorhersehbar<br>
                        1 = sehr kreativ/unvorhersehbar
                    </p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>

<style>
.model-selection-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    max-width: 600px;
}

.model-selection-wrapper select {
    flex: 1;
    min-width: 350px;
}

#loading-models {
    display: none;
    height: 40px;
    line-height: 40px;
    padding: 0 12px;
    background: #f0f0f1;
    border: 1px solid #8c8f94;
    border-radius: 4px;
}

#refresh-models {
    height: 40px;
    margin-left: 10px;
}

.prompt-container {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
}

.prompt-preview-container {
    flex: 1;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.prompt-preview-container h4 {
    margin-top: 0;
    margin-bottom: 10px;
}

#prompt-preview {
    margin-bottom: 15px;
    min-height: 100px;
    white-space: pre-wrap;
}

#save-prompt {
    margin-top: 10px;
}

#custom_prompt {
    flex: 1;
    min-width: 300px;
}
</style>

<script>
var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
var deepposterAdmin = <?php echo json_encode(array(
    'nonce' => wp_create_nonce('deepposter_nonce'),
    'openai_key' => !empty(get_option('deepposter_openai_key')),
    'saved_model' => get_option('deepposter_model', 'gpt-4')
)); ?>;

jQuery(document).ready(function($) {
    // Debug-Logging aktivieren
    const DEBUG = true;
    function log(message, data = null) {
        if (DEBUG) {
            console.log('DeepPoster Debug -', message);
            if (data) console.log(data);
        }
    }

    // Lade die Modelle initial
    if (deepposterAdmin.openai_key) {
        log('OpenAI Key vorhanden, lade Modelle...');
        loadOpenAIModels();
    } else {
        log('Kein OpenAI Key konfiguriert');
    }
    
    // Event-Handler für den Aktualisieren-Button
    $('#refresh-models').on('click', function(e) {
        e.preventDefault();
        log('Aktualisiere Modelle...');
        loadOpenAIModels();
    });
    
    // Funktion zum Laden der OpenAI Modelle
    function loadOpenAIModels() {
        const $select = $('#model_selection');
        const $loading = $('#loading-models');
        const $refreshButton = $('#refresh-models');
        
        $refreshButton.prop('disabled', true);
        $loading.show();
        
        log('Sende AJAX-Anfrage:', {
            url: ajaxurl,
            action: 'deepposter_get_models',
            nonce: deepposterAdmin.nonce
        });
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'deepposter_get_models',
                nonce: deepposterAdmin.nonce
            },
            success: function(response) {
                log('AJAX-Antwort erhalten:', response);
                
                if (response.success) {
                    let html = '<optgroup label="OpenAI">';
                    response.data.forEach(function(model) {
                        html += `<option value="${model.id}">${model.name}</option>`;
                    });
                    html += '</optgroup>';
                    
                    html += '<optgroup label="DeepSeek">' +
                           '<option value="deepseek-chat">DeepSeek Chat</option>' +
                           '<option value="deepseek-coder">DeepSeek Coder</option>' +
                           '</optgroup>';
                    
                    $select.html(html);
                    
                    // Setze das gespeicherte Modell
                    const savedModel = deepposterAdmin.saved_model || 'gpt-4';
                    if ($select.find(`option[value="${savedModel}"]`).length > 0) {
                        $select.val(savedModel);
                        log('Gespeichertes Modell gesetzt:', savedModel);
                    }
                } else {
                    const errorMsg = 'Fehler beim Laden der Modelle: ' + response.data;
                    log('Fehler:', errorMsg);
                    alert(errorMsg);
                }
            },
            error: function(xhr, status, error) {
                const errorMsg = 'Fehler beim Laden der Modelle: ' + error;
                log('AJAX-Fehler:', {xhr, status, error});
                alert(errorMsg);
            },
            complete: function() {
                $refreshButton.prop('disabled', false);
                $loading.hide();
            }
        });
    }
});
</script> 