/**
 * DeepPoster Admin JavaScript
 * 
 * Hauptdatei für die Administratorfunktionen des DeepPoster-Plugins.
 */

jQuery(document).ready(function($) {
    'use strict';

    const DEBUG = true;

    // Stellt sicher, dass das deepposterAdmin-Objekt existiert und fügt fehlende Eigenschaften hinzu
    if (typeof deepposterAdmin === 'undefined') {
        deepposterAdmin = {};
    }

    // Stelle sicher, dass die ajaxurl-Eigenschaft definiert ist
    if (typeof deepposterAdmin.ajaxurl === 'undefined') {
        deepposterAdmin.ajaxurl = typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php';
    }

    // Stelle sicher, dass die nonce-Eigenschaft definiert ist
    if (typeof deepposterAdmin.nonce === 'undefined') {
        deepposterAdmin.nonce = jQuery('#deepposter_nonce').val() || '';
    }

    // Stelle sicher, dass die i18n-Eigenschaft definiert ist
    if (typeof deepposterAdmin.i18n === 'undefined') {
        deepposterAdmin.i18n = {
            // Standard-Nachrichten
            saving: 'Speichern...',
            saved: 'Gespeichert',
            error: 'Fehler',
            confirm: 'Bestätigen',
            cancel: 'Abbrechen',
            errorGenerating: 'Fehler beim Generieren des Artikels',
            generating: 'Generiere Artikel...',
            deleted: 'Gelöscht',
            confirm_delete: 'Möchten Sie diesen Prompt wirklich löschen?',
            selectPrompt: 'Prompt auswählen',
            promptsLoaded: 'Prompts geladen',
            noPrompts: 'Keine gespeicherten Prompts vorhanden.',
            errorLoadingPrompts: 'Fehler beim Laden der Prompts',
            unknownError: 'Unbekannter Fehler',
            promptLoaded: 'Prompt geladen',
            promptNotFound: 'Der ausgewählte Prompt konnte nicht gefunden werden.',
            errorLoadingPrompt: 'Fehler beim Laden des Prompts',
            untitledPrompt: 'Unbenanntes Prompt',
            promptSaved: 'Prompt erfolgreich gespeichert',
            errorSavingPrompt: 'Fehler beim Speichern des Prompts',
            emptyPromptText: 'Bitte geben Sie einen Prompt-Text ein.',
            emptyPromptTitle: 'Bitte geben Sie einen Titel für den Prompt ein.'
        };
    }

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

    // Event-Handler für Prompt-Auswahl (Dropdown)
    $('#promptSelect').on('change', function() {
        var selectedPromptId = $(this).val();
        
        // Wenn kein Prompt ausgewählt ist, nichts tun
        if (!selectedPromptId) {
            return;
        }
        
        // Hole den ausgewählten Prompt aus dem Datenpool
        $.ajax({
            url: deepposterAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'deepposter_get_prompts',
                nonce: deepposterAdmin.nonce
            },
            success: function(response) {
                if (response.success && response.data.prompts) {
                    // Prüfe, ob der ausgewählte Prompt existiert
                    if (response.data.prompts[selectedPromptId]) {
                        var promptData = response.data.prompts[selectedPromptId];
                        
                        // Fülle die Formularfelder mit den Prompt-Daten
                        $('#promptTitle').val(promptData.title || '');
                        $('#promptText').val(promptData.text || '');
                        
                        // Zeige eine Erfolgsbenachrichtigung an
                        showNotice('Prompt geladen: ' + promptData.title, 'success');
                    } else {
                        showNotice('Der ausgewählte Prompt konnte nicht gefunden werden.', 'error');
                    }
                } else {
                    showNotice('Fehler beim Laden des Prompts: ' + (response.data?.message || 'Unbekannter Fehler'), 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotice('Fehler beim Laden des Prompts: ' + error, 'error');
            }
        });
    });

    /**
     * Event-Listener für das Speichern eines Prompts
     */
    jQuery(document).on('click', '#savePrompt', function() {
        var promptText = jQuery('#promptText').val();
        var promptTitle = jQuery('#promptTitle').val();
        
        // Validierung
        if (!promptText) {
            showNotice(deepposterAdmin.i18n.emptyPromptText, 'error');
            return;
        }
        
        if (!promptTitle) {
            showNotice(deepposterAdmin.i18n.emptyPromptTitle, 'error');
            return;
        }
        
        jQuery.ajax({
            url: deepposterAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'deepposter_save_prompt',
                prompt_text: promptText,
                prompt_title: promptTitle,
                nonce: deepposterAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice(deepposterAdmin.i18n.promptSaved, 'success');
                    loadSavedPrompts(); // Aktualisiere die Dropdown-Liste
                } else {
                    showNotice(deepposterAdmin.i18n.errorSavingPrompt + ': ' + 
                        (response.data && response.data.message ? response.data.message : deepposterAdmin.i18n.unknownError), 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX-Fehler:', error);
                showNotice(deepposterAdmin.i18n.errorSavingPrompt + ': ' + error, 'error');
            }
        });
    });

    // Event-Handler für Formular-Übermittlung
    $('#deepposterSettingsForm').on('submit', function(e) {
        e.preventDefault();
        generateArticles();
    });

    /**
     * Lädt gespeicherte Prompts und füllt das Dropdown-Menü
     */
    function loadSavedPrompts() {
        console.log('loadSavedPrompts() wird aufgerufen...');
        console.log('AJAX URL:', deepposterAdmin.ajaxurl);
        console.log('Nonce vorhanden:', !!deepposterAdmin.nonce);
        
        // AJAX-Request zum Laden der gespeicherten Prompts
        console.log('Sende AJAX-Anfrage zum Laden der Prompts...');
        jQuery.ajax({
            url: deepposterAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'deepposter_get_prompts',
                nonce: deepposterAdmin.nonce
            },
            success: function(response) {
                console.log('AJAX-Antwort erhalten:', response);
                
                var $promptSelect = jQuery('#promptSelect');
                $promptSelect.empty();
                $promptSelect.append('<option value="">' + deepposterAdmin.i18n.selectPrompt + '</option>');
                
                // Überprüfen, ob Prompts vorhanden sind
                if (response.success && response.data.prompts) {
                    console.log('Anzahl geladener Prompts:', Object.keys(response.data.prompts).length);
                    
                    // Füge alle geladenen Prompts zum Dropdown hinzu
                    var promptsAvailable = false;
                    for (var promptId in response.data.prompts) {
                        if (response.data.prompts.hasOwnProperty(promptId)) {
                            var prompt = response.data.prompts[promptId];
                            console.log('Füge Prompt hinzu: ID=' + promptId + ', Titel=' + prompt.title);
                            $promptSelect.append('<option value="' + promptId + '">' + prompt.title + '</option>');
                            promptsAvailable = true;
                        }
                    }
                    
                    // Aktiviere/Deaktiviere Dropdown je nach Verfügbarkeit von Prompts
                    if (promptsAvailable) {
                        console.log('Prompts gefunden, aktiviere Dropdown');
                        $promptSelect.prop('disabled', false);
                        showNotice(deepposterAdmin.i18n.promptsLoaded, 'success');
                    } else {
                        console.log('Keine Prompts gefunden, deaktiviere Dropdown');
                        $promptSelect.prop('disabled', true);
                        showNotice(deepposterAdmin.i18n.noPrompts, 'info');
                    }
                } else {
                    console.log('Fehler in der AJAX-Antwort oder keine Prompts vorhanden:', response);
                    $promptSelect.prop('disabled', true);
                    showNotice(deepposterAdmin.i18n.errorLoadingPrompts + ': ' + 
                        (response.data && response.data.message ? response.data.message : deepposterAdmin.i18n.unknownError), 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX-Fehler beim Laden der Prompts:', status, error);
                console.error('AJAX-Antwort:', xhr.responseText);
                
                // Deaktiviere Dropdown und zeige Fehlermeldung
                jQuery('#promptSelect').prop('disabled', true);
                showNotice(deepposterAdmin.i18n.errorLoadingPrompts + ': ' + error, 'error');
            }
        });
    }

    /**
     * Event-Handler für die Auswahl eines Prompts aus dem Dropdown
     */
    jQuery(document).on('change', '#promptSelect', function() {
        var selectedPromptId = jQuery(this).val();
        
        // Wenn ein Prompt ausgewählt wurde
        if (selectedPromptId) {
            jQuery.ajax({
                url: deepposterAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'deepposter_get_prompts',
                    nonce: deepposterAdmin.nonce
                },
                success: function(response) {
                    if (response.success && response.data && response.data.prompts) {
                        var prompt = response.data.prompts[selectedPromptId];
                        
                        if (prompt) {
                            // Setze den Prompt-Titel und -Text in die Felder
                            jQuery('#promptTitle').val(prompt.title || '');
                            jQuery('#promptText').val(prompt.text || '');
                            showNotice(deepposterAdmin.i18n.promptLoaded, 'success');
                        } else {
                            showNotice(deepposterAdmin.i18n.promptNotFound, 'error');
                        }
                    } else {
                        showNotice(deepposterAdmin.i18n.errorLoadingPrompt + ': ' + 
                            (response.data && response.data.message ? response.data.message : deepposterAdmin.i18n.unknownError), 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX-Fehler:', error);
                    showNotice(deepposterAdmin.i18n.errorLoadingPrompt + ': ' + error, 'error');
                }
            });
        } else {
            // Prompt-Felder leeren, wenn kein Prompt ausgewählt ist
            jQuery('#promptTitle').val('');
            jQuery('#promptText').val('');
        }
    });

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

    /**
     * Zeigt eine Benachrichtigung an
     * @param {string} message - Die anzuzeigende Nachricht 
     * @param {string} type - Der Typ der Benachrichtigung (success, error, info, warning)
     * @param {number} duration - Die Dauer in Millisekunden, für die die Benachrichtigung angezeigt werden soll (0 = dauerhaft)
     */
    function showNotice(message, type = 'info', duration = 5000) {
        // Entferne alte Benachrichtigungen mit derselben Art
        jQuery('.deepposter-notice.' + type).remove();
        
        // Erstelle die Benachrichtigung
        var $notice = jQuery('<div class="deepposter-notice ' + type + '">' + 
                         '<span class="deepposter-notice-message">' + message + '</span>' +
                         '<button type="button" class="deepposter-notice-dismiss">×</button>' +
                       '</div>');
        
        // Füge die Benachrichtigung zum Container hinzu oder erstelle einen
        var $container = jQuery('#deepposter-notices');
        if ($container.length === 0) {
            $container = jQuery('<div id="deepposter-notices"></div>');
            jQuery('body').append($container);
            
            // Füge CSS für Benachrichtigungen hinzu, falls noch nicht vorhanden
            if (jQuery('#deepposter-notices-css').length === 0) {
                jQuery('head').append(`
                    <style id="deepposter-notices-css">
                        #deepposter-notices {
                            position: fixed;
                            top: 32px;
                            right: 15px;
                            z-index: 9999;
                            width: 300px;
                        }
                        .deepposter-notice {
                            margin: 5px 0;
                            padding: 10px 15px;
                            border-radius: 3px;
                            position: relative;
                            display: flex;
                            align-items: center;
                            justify-content: space-between;
                            animation: deepposter-fade-in 0.5s;
                        }
                        .deepposter-notice.success {
                            background-color: #dff0d8;
                            color: #3c763d;
                            border: 1px solid #d6e9c6;
                        }
                        .deepposter-notice.error {
                            background-color: #f2dede;
                            color: #a94442;
                            border: 1px solid #ebccd1;
                        }
                        .deepposter-notice.info {
                            background-color: #d9edf7;
                            color: #31708f;
                            border: 1px solid #bce8f1;
                        }
                        .deepposter-notice.warning {
                            background-color: #fcf8e3;
                            color: #8a6d3b;
                            border: 1px solid #faebcc;
                        }
                        .deepposter-notice-message {
                            flex: 1;
                        }
                        .deepposter-notice-dismiss {
                            background: none;
                            border: none;
                            color: inherit;
                            font-size: 18px;
                            cursor: pointer;
                            opacity: 0.5;
                            transition: opacity 0.2s;
                        }
                        .deepposter-notice-dismiss:hover {
                            opacity: 1;
                        }
                        @keyframes deepposter-fade-in {
                            from { opacity: 0; transform: translateY(-10px); }
                            to { opacity: 1; transform: translateY(0); }
                        }
                    </style>
                `);
            }
        }
        $container.append($notice);
        
        // Event-Handler für den Schließen-Button
        $notice.find('.deepposter-notice-dismiss').on('click', function() {
            $notice.fadeOut(300, function() {
                $notice.remove();
            });
        });
        
        // Automatisches Ausblenden nach der angegebenen Dauer
        if (duration > 0) {
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $notice.remove();
                });
            }, duration);
        }
        
        return $notice;
    }

    // Debug-Logging wenn aktiviert
    function debug(message) {
        if (deepposterAdmin.debug) {
            console.log('DeepPoster Debug:', message);
        }
    }

    // Initialer Aufruf zum Laden der Prompts beim Seitenstart
    console.log('Initialer Aufruf von loadSavedPrompts()');
    loadSavedPrompts();
}); 