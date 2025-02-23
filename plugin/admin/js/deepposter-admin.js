jQuery(document).ready(function($) {
    'use strict';

    const DEBUG = true;

    function log(message, data = null) {
        if (DEBUG) {
            console.log('DeepPoster Debug - ' + message);
            if (data) {
                console.log(data);
            }
        }
    }

    // Funktion zum Laden der verfügbaren Modelle
    function loadAvailableModels() {
        const $modelSelect = $('#model_selection');
        const $loadingIndicator = $('#loading-models');
        const $refreshButton = $('#refresh-models');
        
        log('Lade verfügbare Modelle von OpenAI');
        
        // Speichere die aktuelle Auswahl
        const currentSelection = $modelSelect.val();
        log('Aktuelle Auswahl:', currentSelection);
        
        $refreshButton.prop('disabled', true);
        $loadingIndicator.show();
        
        $.ajax({
            url: deepposterAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'deepposter_get_models',
                nonce: deepposterAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    log('Modelle erfolgreich geladen:', response.data);
                    
                    // Leere die aktuelle Liste
                    $modelSelect.empty();

                    // Füge die Modelle hinzu
                    response.data.forEach(function(model) {
                        $modelSelect.append(`<option value="${model.id}">${model.id}</option>`);
                    });
                    
                    // Setze die vorherige Auswahl wieder
                    if (currentSelection && $modelSelect.find(`option[value="${currentSelection}"]`).length > 0) {
                        log('Setze vorherige Auswahl:', currentSelection);
                        $modelSelect.val(currentSelection);
                    } else {
                        // Fallback auf das gespeicherte Modell
                        const savedModel = deepposterAdmin.saved_model || 'gpt-4o';
                        log('Setze gespeichertes Modell:', savedModel);
                        if ($modelSelect.find(`option[value="${savedModel}"]`).length > 0) {
                            $modelSelect.val(savedModel);
                        }
                    }

                    // Aktualisiere die Anzeige des aktuellen Modells
                    $('#current_model').text($modelSelect.val());
                } else {
                    log('Fehler beim Laden der Modelle:', response.data);
                    alert('Fehler beim Laden der Modelle: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                log('AJAX Fehler beim Laden der Modelle:', {xhr, status, error});
                alert('Fehler beim Laden der Modelle. Bitte versuchen Sie es später erneut.');
            },
            complete: function() {
                $refreshButton.prop('disabled', false);
                $loadingIndicator.hide();
            }
        });
    }

    // Event-Listener für den Aktualisieren-Button
    $('#refresh-models').on('click', function(e) {
        e.preventDefault();
        loadAvailableModels();
    });

    // Event-Listener für Modell-Änderung
    $('#model_selection').on('change', function() {
        const selectedModel = $(this).val();
        log('Modell geändert zu:', selectedModel);
        
        // Aktualisiere die Anzeige des aktuellen Modells
        $('#current_model').text(selectedModel);
        
        $.ajax({
            url: deepposterAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'deepposter_save_model',
                nonce: deepposterAdmin.nonce,
                model: selectedModel
            },
            success: function(response) {
                if (response.success) {
                    log('Modell erfolgreich gespeichert');
                    // Aktualisiere das gespeicherte Modell in den JavaScript-Variablen
                    deepposterAdmin.saved_model = selectedModel;
                } else {
                    log('Fehler beim Speichern des Modells:', response.data);
                    alert('Fehler beim Speichern des Modells: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                log('AJAX Fehler beim Speichern des Modells:', {xhr, status, error});
                alert('Fehler beim Speichern des Modells. Bitte versuchen Sie es später erneut.');
            }
        });
    });

    // Initial-Setup
    const $modelSelect = $('#model_selection');
    const savedModel = deepposterAdmin.saved_model || 'gpt-4o';
    
    // Zeige das gespeicherte Modell an
    if (!$modelSelect.val()) {
        $modelSelect.append(`<option value="${savedModel}">${savedModel}</option>`);
        $modelSelect.val(savedModel);
    }
    
    // Aktualisiere die Anzeige des aktuellen Modells
    $('#current_model').text($modelSelect.val());
    
    $modelSelect.show();
    $('#loading-models').hide();

    // Lade gespeicherte Prompts beim Start
    loadSavedPrompts();

    // Event-Handler für Prompt-Auswahl
    $('#promptSelect').on('change', function() {
        const promptId = $(this).val();
        if (promptId) {
            loadPromptContent(promptId);
        }
    });

    // Event-Handler für Prompt speichern
    $('#savePrompt').on('click', function() {
        const promptText = $('#promptText').val();
        if (!promptText) {
            showNotice('Bitte geben Sie einen Prompt-Text ein.', 'error');
            return;
        }
        savePrompt(promptText);
    });

    // Event-Handler für Formular-Übermittlung
    $('#deepposterSettingsForm').on('submit', function(e) {
        e.preventDefault();
        generateArticles();
    });

    // Lade gespeicherte Prompts
    function loadSavedPrompts() {
        $.ajax({
            url: deepposterAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'deepposter_get_prompts',
                nonce: deepposterAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    const $select = $('#promptSelect');
                    $select.find('option:not(:first)').remove();
                    
                    response.data.forEach(function(prompt) {
                        $select.append($('<option>', {
                            value: prompt.ID,
                            text: prompt.post_title || 'Prompt #' + prompt.ID
                        }));
                    });
                } else {
                    showNotice('Fehler beim Laden der Prompts: ' + response.data, 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotice('Fehler beim Laden der Prompts: ' + error, 'error');
            }
        });
    }

    // Lade Prompt-Inhalt
    function loadPromptContent(promptId) {
        $.ajax({
            url: deepposterAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'deepposter_get_prompt_content',
                prompt_id: promptId,
                nonce: deepposterAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#promptText').val(response.data);
                } else {
                    showNotice('Fehler beim Laden des Prompt-Inhalts: ' + response.data, 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotice('Fehler beim Laden des Prompt-Inhalts: ' + error, 'error');
            }
        });
    }

    // Speichere Prompt
    function savePrompt(promptText) {
        $.ajax({
            url: deepposterAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'deepposter_save_prompt',
                prompt: promptText,
                nonce: deepposterAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Prompt erfolgreich gespeichert!', 'success');
                    loadSavedPrompts(); // Aktualisiere die Liste
                } else {
                    showNotice('Fehler beim Speichern des Prompts: ' + response.data, 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotice('Fehler beim Speichern des Prompts: ' + error, 'error');
            }
        });
    }

    // Generiere Artikel
    function generateArticles() {
        const $form = $('#deepposterSettingsForm');
        const $submitButton = $('#generateButton');
        const $results = $('#generationResults');

        // Deaktiviere den Submit-Button
        $submitButton.prop('disabled', true).text('Generiere...');

        $.ajax({
            url: deepposterAdmin.ajaxurl,
            type: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    showResults(response.data);
                } else {
                    showNotice('Fehler bei der Artikel-Generierung: ' + response.data, 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotice('Fehler bei der Artikel-Generierung: ' + error, 'error');
            },
            complete: function() {
                // Aktiviere den Submit-Button wieder
                $submitButton.prop('disabled', false).text('Artikel generieren');
            }
        });
    }

    // Zeige Ergebnisse
    function showResults(results) {
        const $results = $('#generationResults');
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

    // Zeige Benachrichtigung
    function showNotice(message, type) {
        const $notice = $('<div>', {
            class: `notice notice-${type} is-dismissible`,
            html: `<p>${message}</p>`
        });

        $('#generationResults').html($notice);

        // Automatisches Ausblenden nach 5 Sekunden
        if (type === 'success') {
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    }

    // Debug-Logging wenn aktiviert
    function debug(message) {
        if (deepposterAdmin.debug) {
            console.log('DeepPoster Debug:', message);
        }
    }
}); 