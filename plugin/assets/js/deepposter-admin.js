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

    // Prüfe die aktuelle Seite anhand der URL-Parameter
    const urlParams = new URLSearchParams(window.location.search);
    const currentPage = urlParams.get('page');
    
    // Definiere die Seitentypen
    const isGeneratorPage = currentPage === 'deepposter';
    const isSettingsPage = currentPage === 'deepposter-settings';

    // Debug-Ausgabe der Initialisierung
    console.log('Initialisierungsstatus:', {
        ajaxurl: deepposterAdmin.ajaxurl,
        nonce: deepposterAdmin.nonce ? 'vorhanden' : 'fehlt',
        currentPage: currentPage,
        isGeneratorPage: isGeneratorPage,
        isSettingsPage: isSettingsPage,
        promptSelect: jQuery('#promptSelect').length ? 'gefunden' : 'nicht gefunden',
        selectedPromptContent: jQuery('#selectedPromptContent').length ? 'gefunden' : 'nicht gefunden'
    });

    // Event-Handler für die Modellauswahl (nur auf der Einstellungsseite)
    if (isSettingsPage) {
        initializeModelSelection();
    }

    // Event-Handler für Prompt-Auswahl und Laden (nur auf der Generator-Seite)
    if (isGeneratorPage) {
        // On Page-Load - Lade den ausgewählten Prompt direkt
        jQuery(document).ready(function() {
            console.log('Generator-Seite geladen - Trigger Prompt-Vorschau');
            
            // Initialisiere die Prompt-Vorschau automatisch, wenn ein Wert ausgewählt ist
            const promptSelect = jQuery('#promptSelect');
            if (promptSelect.length) {
                const initialPromptId = promptSelect.val();
                if (initialPromptId && initialPromptId !== '') {
                    console.log('Lade initialen Prompt direkt:', initialPromptId);
                    
                    // Direkter Aufruf der Funktion
                    updatePromptPreview(initialPromptId);
                }
            }
        });

        // Event-Handler für Prompt-Auswahl
        jQuery('#promptSelect').off('change').on('change', function() {
            const selectedPromptId = jQuery(this).val();
            console.log('Prompt-Auswahl Event ausgelöst:', {
                selectedValue: selectedPromptId
            });
            updatePromptPreview(selectedPromptId);
        });

        // Lade die Prompts
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

                    // Zeige die Erfolgsmeldung NUR auf der Generator-Seite
                    if (isGeneratorPage && !isSettingsPage) {
                        console.log('Zeige Erfolgsmeldung auf Generator-Seite');
                        const $existingNotice = jQuery('.notice-success');
                        if ($existingNotice.length) {
                            $existingNotice.remove();
                        }
                        
                        const $notice = jQuery('<div class="notice notice-success is-dismissible" id="prompts-loaded-notice"><p>Prompts geladen</p></div>');
                        jQuery('.wrap').prepend($notice);
                        
                        // Direkte Event-Delegation für das Schließen-Element
                        jQuery(document).on('click', '#prompts-loaded-notice .notice-dismiss', function() {
                            jQuery('#prompts-loaded-notice').fadeOut('fast', function() {
                                jQuery(this).remove();
                            });
                        });
                        
                        // Manuelle Initialisierung des WordPress-Dismiss-Buttons
                        if (typeof window.jQuery === 'function' && typeof window.jQuery.fn.find === 'function') {
                            if ($notice.find('.notice-dismiss').length === 0) {
                                $notice.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Diese Meldung ausblenden.</span></button>');
                            }
                        }
                    }
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

    // Event-Handler für die Modellauswahl
    function initializeModelSelection() {
        const $modelSelect = $('#model_selection');
        const $refreshButton = $('#refresh-models');
        const $loadingIndicator = $('#loading-models');
        
        if (!$modelSelect.length) {
            console.log('Modellauswahl nicht gefunden, überspringe Initialisierung');
            return;
        }

        console.log('Initialisiere Modellauswahl');

        // Event-Handler für den Refresh-Button
        $refreshButton.on('click', function(e) {
            e.preventDefault();
            loadOpenAIModels();
        });

        // Lade die Modelle beim Start
        loadOpenAIModels();

        function loadOpenAIModels() {
            console.log('Lade OpenAI Modelle...');
            
            $refreshButton.prop('disabled', true);
            $loadingIndicator.show();
            
            const currentSelection = $modelSelect.val();
            
            $.ajax({
                url: deepposterAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'deepposter_get_models',
                    nonce: deepposterAdmin.nonce
                },
                success: function(response) {
                    console.log('Modelle geladen:', response);
                    
                    if (response.success && response.data) {
                        let html = '<optgroup label="OpenAI">';
                        response.data.forEach(function(model) {
                            const selected = model.id === currentSelection ? ' selected' : '';
                            html += `<option value="${model.id}"${selected}>${model.id}</option>`;
                        });
                        html += '</optgroup>';
                        
                        // Füge DeepSeek Modelle hinzu
                        html += '<optgroup label="DeepSeek">' +
                               '<option value="deepseek-chat">DeepSeek Chat</option>' +
                               '<option value="deepseek-coder">DeepSeek Coder</option>' +
                               '</optgroup>';
                        
                        $modelSelect.html(html);
                        
                        // Aktualisiere die Beschreibung
                        updateModelDescription($modelSelect.val());
                    } else {
                        console.error('Fehler beim Laden der Modelle:', response.data);
                        alert('Fehler beim Laden der Modelle: ' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Fehler:', error);
                    alert('Fehler beim Laden der Modelle: ' + error);
                },
                complete: function() {
                    $refreshButton.prop('disabled', false);
                    $loadingIndicator.hide();
                }
            });
        }

        // Event-Handler für Modelländerungen
        $modelSelect.on('change', function() {
            const selectedModel = $(this).val();
            console.log('Modell ausgewählt:', selectedModel);
            updateModelDescription(selectedModel);
        });

        function updateModelDescription(modelId) {
            let description = '';
            
            if (modelId.includes('gpt-4')) {
                description = 'GPT-4: Fortgeschrittenes Modell mit hoher Qualität und Kontextverständnis';
            } else if (modelId.includes('gpt-3.5')) {
                description = 'GPT-3.5 Turbo: Schnelles und effizientes Modell für die meisten Anwendungsfälle';
            } else if (modelId.includes('deepseek')) {
                description = 'DeepSeek: Spezialisiertes Modell für Code und technische Inhalte';
            }
            
            $('.model-description').text(description);
        }
    }

    /**
     * Aktualisiert die Vorschau des ausgewählten Prompts
     */
    function updatePromptPreview(promptId) {
        console.log('UpdatePromptPreview aufgerufen mit ID:', promptId);
        
        // Validierung
        if (!promptId) {
            jQuery('#selectedPromptContent').html('<p><em>Bitte wählen Sie einen Prompt aus</em></p>');
            return;
        }
        
        // Zeige Ladezustand
        jQuery('#selectedPromptContent').html('<p><em>Lade Prompt...</em></p>');
        
        // Erfolgreiche AJAX-Implementierung
        jQuery.ajax({
            url: deepposterAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'deepposter_get_prompt_content',
                prompt_id: promptId,
                nonce: deepposterAdmin.nonce
            },
            success: function(response) {
                console.log('Prompt-Vorschau AJAX-Antwort:', response);
                
                // Erfolgreichen Ansatz verwenden
                if (response && response.success && response.data && response.data.content) {
                    // Content direkt in das DOM-Element einfügen
                    jQuery('#selectedPromptContent').html(response.data.content);
                    console.log('Prompt-Inhalt erfolgreich aktualisiert');
                } else {
                    jQuery('#selectedPromptContent').html('<p><em>Fehler beim Laden des Prompts</em></p>');
                    console.error('Fehlerhafte Antwortdaten:', response);
                }
            },
            error: function(xhr, status, error) {
                jQuery('#selectedPromptContent').html('<p><em>Fehler: ' + error + '</em></p>');
                console.error('AJAX-Fehler:', status, error);
            }
        });
    }

    // Event-Handler für das Formular
    jQuery('#deepposterSettingsForm').on('submit', function(e) {
        e.preventDefault();
        console.log('Formular wurde abgeschickt');

        const formData = {
            action: 'deepposter_generate',
            nonce: deepposterAdmin.nonce,
            prompt: jQuery('#promptSelect').val(),
            category: jQuery('#categorySelect').val(),
            count: jQuery('#articleCount').val(),
            publish: jQuery('#publishArticles').is(':checked')
        };

        console.log('Sende Artikel-Generierung Anfrage:', formData);

        // Deaktiviere den Submit-Button
        jQuery('#generateButton').prop('disabled', true).text('Generiere Artikel...');

        // Zeige Lade-Nachricht
        jQuery('#generationResults').html('<div class="notice notice-info"><p>Artikel werden generiert...</p></div>');

        // Sende AJAX-Anfrage
        jQuery.ajax({
            url: deepposterAdmin.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('Artikel-Generierung Antwort:', response);
                
                if (response.success) {
                    let html = '<div class="notice notice-success"><p>Artikel wurden erfolgreich generiert!</p>';
                    if (Array.isArray(response.data)) {
                        html += '<ul>';
                        response.data.forEach(function(article) {
                            html += '<li><a href="' + article.edit_url + '" target="_blank">' + article.title + '</a></li>';
                        });
                        html += '</ul>';
                    }
                    html += '</div>';
                    jQuery('#generationResults').html(html);
                } else {
                    jQuery('#generationResults').html(
                        '<div class="notice notice-error"><p>Fehler: ' + 
                        (response.data || 'Unbekannter Fehler') + 
                        '</p></div>'
                    );
                }
            },
            error: function(xhr, status, error) {
                console.error('Artikel-Generierung Fehler:', {xhr, status, error});
                jQuery('#generationResults').html(
                    '<div class="notice notice-error"><p>Fehler bei der Artikel-Generierung: ' + 
                    error + 
                    '</p></div>'
                );
            },
            complete: function() {
                // Aktiviere den Submit-Button wieder
                jQuery('#generateButton').prop('disabled', false).text('Artikel generieren');
            }
        });
    });
}); 