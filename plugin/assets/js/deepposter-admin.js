/**
 * DeepPoster Admin JavaScript
 * 
 * Hauptdatei für die Administratorfunktionen des DeepPoster-Plugins.
 */

jQuery(document).ready(function($) {
    'use strict';

    console.log('DeepPoster Admin JS geladen - Start der Initialisierung');
    
    // Stellt sicher, dass das deepposterAdmin-Objekt existiert
    if (typeof deepposterAdmin === 'undefined') {
        console.warn('deepposterAdmin Objekt nicht gefunden, erstelle neu');
        deepposterAdmin = {
            ajaxurl: typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php',
            nonce: jQuery('#deepposter_nonce').val() || ''
        };
    }

    // Debug-Ausgabe der Initialisierung
    console.log('Initialisierungsstatus:', {
        ajaxurl: deepposterAdmin.ajaxurl,
        nonce: deepposterAdmin.nonce ? 'vorhanden' : 'fehlt',
        promptSelect: jQuery('#promptSelect').length ? 'gefunden' : 'nicht gefunden',
        selectedPromptContent: jQuery('#selectedPromptContent').length ? 'gefunden' : 'nicht gefunden'
    });

    // Event-Handler für Prompt-Auswahl
    jQuery('#promptSelect').off('change').on('change', function() {
        const selectedPromptId = jQuery(this).val();
        console.log('Prompt-Auswahl Event ausgelöst:', {
            selectedValue: selectedPromptId
        });
        updatePromptPreview(selectedPromptId);
    });

    /**
     * Aktualisiert die Vorschau des ausgewählten Prompts
     */
    function updatePromptPreview(promptId) {
        const $preview = jQuery('#selectedPromptContent');
        
        if (!promptId) {
            $preview.html('<em>Bitte wählen Sie einen Prompt aus.</em>');
            return;
        }

        console.log('Lade Prompt-Vorschau:', promptId);

        jQuery.ajax({
            url: deepposterAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'deepposter_get_prompt_content',
                prompt_id: promptId,
                nonce: deepposterAdmin.nonce
            },
            success: function(response) {
                console.log('AJAX Antwort für Prompt-Vorschau:', response);
                
                if (response.success && response.data) {
                    $preview.html(response.data);
                } else {
                    $preview.html('<em>Fehler beim Laden des Prompts</em>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Fehler:', error);
                $preview.html('<em>Fehler beim Laden des Prompts: ' + error + '</em>');
            }
        });
    }

    /**
     * Lädt gespeicherte Prompts und füllt das Dropdown-Menü
     */
    function loadSavedPrompts() {
        console.log('Lade gespeicherte Prompts...');
        
        jQuery.ajax({
            url: deepposterAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'deepposter_get_prompts',
                nonce: deepposterAdmin.nonce
            },
            success: function(response) {
                console.log('AJAX Antwort für Prompts:', response);
                
                const $promptSelect = jQuery('#promptSelect');
                $promptSelect.empty();
                $promptSelect.append('<option value="">Prompt auswählen</option>');
                
                if (response.success && response.data.prompts) {
                    for (const promptId in response.data.prompts) {
                        const prompt = response.data.prompts[promptId];
                        $promptSelect.append(
                            '<option value="' + prompt.post_id + '">' + 
                            prompt.title + 
                            '</option>'
                        );
                    }
                    $promptSelect.prop('disabled', false);
                } else {
                    $promptSelect.prop('disabled', true);
                    jQuery('#selectedPromptContent').html('<em>Keine Prompts verfügbar</em>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Fehler beim Laden der Prompts:', error);
                jQuery('#promptSelect').prop('disabled', true);
                jQuery('#selectedPromptContent').html('<em>Fehler beim Laden der Prompts</em>');
            }
        });
    }

    // Event-Handler für Formular-Übermittlung
    jQuery('#deepposterSettingsForm').on('submit', function(e) {
        e.preventDefault();
        const $form = jQuery(this);
        const $submitButton = jQuery('#generateButton');
        const $results = jQuery('#generationResults');

        $submitButton.prop('disabled', true).text('Generiere...');

        jQuery.ajax({
            url: deepposterAdmin.ajaxurl,
            type: 'POST',
            data: $form.serialize(),
            success: function(response) {
                console.log('Artikel-Generierung Antwort:', response);
                if (response.success) {
                    showResults(response.data);
                } else {
                    showError('Fehler bei der Artikel-Generierung: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Fehler bei der Generierung:', error);
                showError('Fehler bei der Artikel-Generierung: ' + error);
            },
            complete: function() {
                $submitButton.prop('disabled', false).text('Artikel generieren');
            }
        });
    });

    function showResults(results) {
        const $results = jQuery('#generationResults');
        let html = '<div class="notice notice-success"><p>Artikel erfolgreich generiert!</p></div><ul>';

        results.forEach(function(article) {
            html += `
                <li>
                    <strong>${article.title}</strong><br>
                    <a href="${article.edit_url}" class="button" target="_blank">Bearbeiten</a>
                    <a href="${article.view_url}" class="button" target="_blank">Ansehen</a>
                </li>
            `;
        });

        html += '</ul>';
        $results.html(html);
    }

    function showError(message) {
        const $results = jQuery('#generationResults');
        $results.html('<div class="notice notice-error"><p>' + message + '</p></div>');
    }

    // Starte das Laden der Prompts
    loadSavedPrompts();
}); 