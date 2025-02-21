<?php defined('ABSPATH') || exit; ?>

<div class="wrap">
    <h2>DeepPoster</h2>
    
    <div class="deepposter-layout">
        <div class="deepposter-main">
            <form id="aiGeneratorForm" class="deepposter-form" method="post" action="javascript:void(0);">
                <div class="deepposter-settings-field">
                    <label for="promptText">Prompt Vorschau & Anpassung:</label>
                    <textarea id="promptText" name="prompt" rows="10" style="width: 100%; margin-top: 10px;"><?php
                        echo esc_textarea(
                            get_option('deepposter_prompt',
                                'Du bist ein professioneller Content-Ersteller für WordPress-Blogs. ' .
                                'Der Artikel sollte informativ, gut recherchiert und SEO-optimiert sein. ' .
                                'Formatiere den Artikel mit WordPress-kompatiblem HTML und strukturiere ihn mit Überschriften (h2, h3). ' .
                                'Beginne mit dem Titel in der ersten Zeile, gefolgt von einer Leerzeile und dann dem Artikelinhalt. ' .
                                'Schreibe über: [KATEGORIE]'
                            )
                        );
                    ?></textarea>
                    <div class="prompt-actions" style="margin-top: 10px;">
                        <button type="button" id="savePrompt" class="button button-primary">Prompt speichern</button>
                    </div>
                    <p class="description">Hier können Sie das Prompt anpassen, das an die KI gesendet wird. Die Platzhalter [KATEGORIE] werden automatisch ersetzt. Klicken Sie auf "Prompt speichern", um Ihre Änderungen zu speichern.</p>
                </div>

                <div class="deepposter-settings-field">
                    <label for="categorySelect">Kategorie auswählen:</label>
                    <select id="categorySelect" name="category">
                        <option value="">Kategorie wählen</option>
                        <?php
                        $categories = get_categories(['hide_empty' => false]);
                        foreach ($categories as $category) {
                            echo '<option value="' . esc_attr($category->term_id) . '">' . 
                                 esc_html($category->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="deepposter-settings-field">
                    <label for="articleCount">Anzahl Artikel:</label>
                    <select id="articleCount" name="count">
                        <?php for($i = 1; $i <= 10; $i++) : ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="deepposter-settings-field checkbox-field">
                    <label>
                        <input type="checkbox" id="publishImmediately" name="publish">
                        Sofort veröffentlichen
                    </label>
                </div>

                <div class="deepposter-settings-field">
                    <button type="submit" class="button button-primary">Artikel generieren</button>
                </div>

                <?php wp_nonce_field('deepposter_nonce', 'deepposter_nonce'); ?>
            </form>

            <div id="generationResults" class="generation-results"></div>
        </div>
        
        <div class="deepposter-sidebar">
            <div id="debugSection" class="debug-section">
                <h3>Debug-Informationen</h3>
                <div id="debugLog" class="debug-log"></div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Übernehme das Design-System von den Einstellungen */
    .deepposter-settings-field {
        margin-bottom: 20px;
    }
    .deepposter-settings-field label {
        display: inline-block;
        min-width: 150px;
        margin-bottom: 5px;
        font-weight: 600;
    }
    .deepposter-settings-field input[type="text"],
    .deepposter-settings-field input[type="password"],
    .deepposter-settings-field input[type="number"],
    .deepposter-settings-field select {
        width: 350px !important;
        height: 40px !important;
        padding: 8px 12px !important;
        font-size: 14px !important;
        line-height: 1.5 !important;
        border: 1px solid #8c8f94 !important;
        border-radius: 4px !important;
        background-color: #fff !important;
    }
    .deepposter-settings-field input:focus,
    .deepposter-settings-field select:focus {
        border-color: #2271b1 !important;
        box-shadow: 0 0 0 1px #2271b1 !important;
        outline: none !important;
    }
    .deepposter-settings-field.checkbox-field {
        margin-left: 150px;
    }
    .deepposter-settings-field.checkbox-field label {
        min-width: auto;
        font-weight: normal;
    }
    .deepposter-settings-field button {
        margin-left: 150px !important;
        height: 40px !important;
        padding: 0 20px !important;
        font-size: 14px !important;
    }
    .generation-results {
        margin-top: 30px;
    }
    .generation-item {
        background: #fff;
        border: 1px solid #c3c4c7;
        border-radius: 4px;
        padding: 15px;
        margin-bottom: 15px;
    }
    .generation-item h4 {
        margin: 0 0 10px 0;
        font-size: 16px;
    }
    .generation-meta {
        color: #646970;
        margin-bottom: 10px;
    }
    .generation-actions {
        margin-top: 10px;
    }
    .generation-actions .button {
        margin-right: 10px;
    }
    .notice {
        margin: 15px 0;
        padding: 12px;
        border-radius: 4px;
    }
    .notice-info {
        border-left: 4px solid #72aee6;
        background: #f0f6fc;
    }
    .notice-error {
        border-left: 4px solid #d63638;
        background: #fcf0f1;
    }
    
    .deepposter-layout {
        display: flex;
        gap: 30px;
        margin-top: 20px;
    }
    
    .deepposter-main {
        flex: 1;
        min-width: 0; /* Verhindert Overflow bei langen Inhalten */
    }
    
    .deepposter-sidebar {
        width: 400px;
        flex-shrink: 0;
    }
    
    .debug-section {
        position: sticky;
        top: 32px; /* WordPress Admin Bar Höhe */
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        height: calc(100vh - 100px);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .debug-section h3 {
        margin: 0;
        padding: 15px 20px;
        background: #f0f0f1;
        border-bottom: 1px solid #ddd;
        font-size: 14px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .debug-section h3::after {
        content: '';
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #00a32a;
        margin-left: 10px;
    }
    
    .debug-log {
        flex: 1;
        padding: 0;
        margin: 0;
        overflow-y: auto;
        font-family: ui-monospace, SFMono-Regular, SF Mono, Menlo, Consolas, Liberation Mono, monospace;
        font-size: 12px;
        line-height: 1.6;
        background: #fff;
    }
    
    .debug-log .log-entry {
        margin: 0;
        padding: 8px 15px;
        border-bottom: 1px solid #f0f0f1;
        display: flex;
        align-items: flex-start;
    }
    
    .debug-log .log-entry:hover {
        background: #f6f7f7;
    }
    
    .debug-log .log-entry:last-child {
        border-bottom: none;
    }
    
    .debug-log .timestamp {
        color: #646970;
        font-size: 11px;
        min-width: 75px;
        padding-right: 10px;
        font-family: ui-monospace, SFMono-Regular, SF Mono, Menlo, Consolas, Liberation Mono, monospace;
    }
    
    .debug-log .message {
        flex: 1;
        word-break: break-word;
    }
    
    .debug-log .success {
        color: #00a32a;
        position: relative;
        padding-left: 18px;
    }
    
    .debug-log .success::before {
        content: '✓';
        position: absolute;
        left: 0;
        top: 0;
    }
    
    .debug-log .error {
        color: #d63638;
        position: relative;
        padding-left: 18px;
    }
    
    .debug-log .error::before {
        content: '✕';
        position: absolute;
        left: 0;
        top: 0;
    }
    
    .debug-log .info {
        color: #2271b1;
        position: relative;
        padding-left: 18px;
    }
    
    .debug-log .info::before {
        content: 'ℹ';
        position: absolute;
        left: 0;
        top: 0;
    }
    
    /* Scrollbar Styling */
    .debug-log::-webkit-scrollbar {
        width: 8px;
    }
    
    .debug-log::-webkit-scrollbar-track {
        background: #f0f0f1;
    }
    
    .debug-log::-webkit-scrollbar-thumb {
        background: #c3c4c7;
        border-radius: 4px;
    }
    
    .debug-log::-webkit-scrollbar-thumb:hover {
        background: #a7aaad;
    }
    
    /* Responsive Design */
    @media screen and (max-width: 1200px) {
        .deepposter-layout {
            flex-direction: column;
        }
        
        .deepposter-sidebar {
            width: 100%;
        }
        
        .debug-section {
            position: static;
            height: 400px;
        }
    }
</style>

<script>
var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
var deepposter = <?php echo json_encode(array(
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('deepposter_nonce')
)); ?>;

jQuery(document).ready(function($) {
    // Debug-Logging Funktion
    function debugLog(message, type = 'info') {
        const $debugLog = $('#debugLog');
        const timestamp = new Date().toLocaleTimeString();
        const $entry = $('<div class="log-entry">')
            .append($('<span class="timestamp">').text(timestamp))
            .append($('<span>').addClass('message ' + type).text(message));
        
        // Füge neue Einträge am Anfang ein
        $debugLog.prepend($entry);
        
        // Begrenze die Anzahl der Log-Einträge
        const maxEntries = 100;
        const $entries = $('.log-entry');
        if ($entries.length > maxEntries) {
            $entries.slice(maxEntries).remove();
        }
    }

    // Lade den gespeicherten Prompt beim Start
    debugLog('Lade gespeicherten Prompt...');
    $.ajax({
        url: deepposter.ajaxurl,
        method: 'POST',
        data: {
            action: 'deepposter_get_prompt',
            nonce: deepposter.nonce
        },
        success: function(response) {
            if (response.success && response.data) {
                $('#promptText').val(response.data);
                debugLog('Prompt erfolgreich geladen', 'success');
            } else {
                debugLog('Kein gespeicherter Prompt gefunden', 'error');
            }
        },
        error: function() {
            debugLog('Fehler beim Laden des Prompts', 'error');
        }
    });

    // Prompt bei Kategorieänderung aktualisieren
    $('#categorySelect').on('change', function() {
        const category = $(this).find('option:selected').text();
        debugLog('Kategorie geändert zu: ' + category);
        const currentPrompt = $('#promptText').val();
        // Entferne alte Kategorie am Ende und füge neue hinzu
        const promptWithoutCategory = currentPrompt.replace(/Schreibe über:.*$/, '').trim();
        const updatedPrompt = promptWithoutCategory + ' Schreibe über: ' + category;
        $('#promptText').val(updatedPrompt);
    });

    // Prompt speichern
    $('#savePrompt').on('click', function() {
        const $button = $(this);
        const originalText = $button.text();
        const prompt = $('#promptText').val();
        
        debugLog('Speichere Prompt...');
        $button.prop('disabled', true).text('Wird gespeichert...');

        $.ajax({
            url: deepposter.ajaxurl,
            method: 'POST',
            data: {
                action: 'deepposter_save_prompt',
                nonce: deepposter.nonce,
                prompt: prompt
            },
            success: function(response) {
                if (response.success) {
                    debugLog('Prompt erfolgreich gespeichert', 'success');
                    $button.text('Gespeichert!');
                    setTimeout(function() {
                        $button.prop('disabled', false).text(originalText);
                    }, 2000);
                } else {
                    const errorMsg = 'Fehler beim Speichern: ' + response.data;
                    debugLog(errorMsg, 'error');
                    alert(errorMsg);
                    $button.prop('disabled', false).text(originalText);
                }
            },
            error: function(xhr, status, error) {
                const errorMsg = 'Fehler beim Speichern des Prompts: ' + error;
                debugLog(errorMsg, 'error');
                alert(errorMsg);
                $button.prop('disabled', false).text(originalText);
            }
        });
    });

    // Formular absenden
    $('#aiGeneratorForm').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitButton = $form.find('button[type="submit"]');
        const $resultContainer = $('#generationResults');
        
        // Deaktiviere Submit-Button
        $submitButton.prop('disabled', true);
        $submitButton.text('Generiere...');
        
        // Zeige Lade-Animation
        $resultContainer.html('<div class="notice notice-info"><p>Generiere Artikel...</p></div>');
        
        // Sammle Formulardaten
        const formData = {
            action: 'deepposter_generate',
            nonce: deepposter.nonce,
            category: $('#categorySelect').val(),
            count: $('#articleCount').val(),
            publish: $('#publishImmediately').is(':checked'),
            prompt: $('#promptText').val()
        };
        
        // Logge den Prompt und die Einstellungen
        debugLog('Starte Artikelgenerierung...', 'info');
        debugLog('------------------------', 'info');
        debugLog('Einstellungen:', 'info');
        debugLog(`Kategorie: ${$('#categorySelect option:selected').text()}`, 'info');
        debugLog(`Anzahl Artikel: ${formData.count}`, 'info');
        debugLog(`Sofort veröffentlichen: ${formData.publish ? 'Ja' : 'Nein'}`, 'info');
        debugLog('------------------------', 'info');
        debugLog('Sende Prompt an KI:', 'info');
        debugLog(formData.prompt, 'info');
        debugLog('------------------------', 'info');
        
        // Sende AJAX-Request
        $.post(deepposter.ajaxurl, formData)
            .done(function(response) {
                if (response.success) {
                    // Zeige generierte Artikel an
                    let html = '<div class="notice notice-success"><p>Artikel erfolgreich generiert:</p>';
                    response.data.forEach(function(post) {
                        // Logge Details für jeden generierten Artikel
                        debugLog(`Artikel generiert (ID: ${post.id})`, 'success');
                        debugLog(`Titel: ${post.title}`, 'info');
                        debugLog(`Kategorie: ${post.category}`, 'info');
                        debugLog(`Status: ${post.status}`, 'info');
                        if (post.tags && post.tags.length > 0) {
                            debugLog(`Schlagwörter: ${post.tags.join(', ')}`, 'info');
                        }
                        debugLog('------------------------', 'info');

                        html += `
                            <div class="generation-item">
                                <h4>${post.title}</h4>
                                <div class="generation-meta">
                                    <div>Kategorie: ${post.category}</div>
                                    <div>Status: ${post.status}</div>
                                    ${post.tags ? `<div>Schlagwörter: ${post.tags.join(', ')}</div>` : ''}
                                </div>
                                <div class="generation-actions">
                                    <a href="${post.editUrl}" class="button" target="_blank">Bearbeiten</a>
                                    <a href="${post.viewUrl}" class="button" target="_blank">Ansehen</a>
                                </div>
                            </div>`;
                    });
                    html += '</div>';
                    $resultContainer.html(html);
                } else {
                    // Logge Fehler
                    debugLog('Fehler bei der Artikelgenerierung:', 'error');
                    debugLog(response.data, 'error');
                    debugLog('------------------------', 'error');
                    $resultContainer.html(`<div class="notice notice-error"><p>${response.data}</p></div>`);
                }
            })
            .fail(function(xhr, status, error) {
                // Logge AJAX-Fehler
                debugLog('AJAX-Fehler bei der Artikelgenerierung:', 'error');
                debugLog(`Status: ${status}`, 'error');
                debugLog(`Fehler: ${error}`, 'error');
                debugLog('------------------------', 'error');
                $resultContainer.html(`<div class="notice notice-error"><p>Ein Fehler ist aufgetreten: ${error}</p></div>`);
            })
            .always(function() {
                debugLog('Artikelgenerierung abgeschlossen', 'info');
                debugLog('------------------------', 'info');
                // Aktiviere Submit-Button wieder
                $submitButton.prop('disabled', false);
                $submitButton.text('Artikel generieren');
            });
    });
});
</script> 