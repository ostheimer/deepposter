jQuery(document).ready(function($) {
    const DEBUG = window.deepposterAdmin && window.deepposterAdmin.debug;
    const AJAX_URL = window.deepposterAdmin ? window.deepposterAdmin.ajaxurl : '/wp-admin/admin-ajax.php';
    const debugLog = [];

    // Globaler AJAX-Error-Handler
    $(document).ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
        log('AJAX-Fehler', {
            status: jqXHR.status,
            statusText: jqXHR.statusText,
            responseText: jqXHR.responseText,
            error: thrownError,
            settings: ajaxSettings
        });
        showError('Ein Fehler ist aufgetreten: ' + jqXHR.statusText);
        event.preventDefault();
        return false;
    });

    function log(message, data = null) {
        const timestamp = new Date().toISOString();
        const logEntry = {
            timestamp,
            message: 'DeepPoster Debug - ' + message,
            data
        };
        
        debugLog.push(logEntry);
        
        if (DEBUG) {
            console.log(`[${timestamp}] ${logEntry.message}`);
            if (data) {
                console.log('Data:', data);
            }
        }
    }

    function showError(message, error = null) {
        console.error('DeepPoster Error - ' + message);
        if (error) {
            console.error(error);
        }
        $('#generationResults').prepend(
            '<div class="notice notice-error">' +
            '<p>' + message + '</p>' +
            '</div>'
        );
    }

    function showSuccess(message) {
        // Bestehende Benachrichtigungen entfernen
        $('.notice').fadeOut(300, function() {
            $(this).remove();
        });

        // Neue Erfolgsmeldung erstellen
        const $notice = $('<div class="notice notice-success"><p>' + message + '</p></div>');
        $('#wpbody-content').prepend($notice);
        
        // Nach 5 Sekunden ausblenden
        setTimeout(function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    function loadPrompts() {
        log('Starte loadPrompts');
        log('AJAX URL:', AJAX_URL);

        return $.ajax({
            url: AJAX_URL,
            type: 'POST',
            data: {
                action: 'deepposter_get_prompts',
                nonce: window.deepposterAdmin.nonce
            }
        })
        .done(function(response) {
            log('Prompt-Antwort erhalten', response);
            
            if (response.success && response.data && response.data.prompts) {
                const $select = $('#promptSelect');
                $select.find('option:not(:first)').remove();
                
                response.data.prompts.forEach(function(prompt) {
                    $select.append(
                        $('<option></option>')
                            .val(prompt.id)
                            .text(prompt.title)
                    );
                });

                // Wenn ein aktiver Prompt existiert, wähle ihn aus
                if (response.data.activePrompt) {
                    $select.val(response.data.activePrompt);
                    const activePrompt = response.data.prompts.find(p => p.id === response.data.activePrompt);
                    if (activePrompt) {
                        $('#promptText').val(activePrompt.content);
                        $('#promptId').val(activePrompt.id);
                        // Zeige den Löschen-Button, wenn ein Prompt ausgewählt ist
                        $('#deletePrompt').show();
                    }
                } else {
                    // Kein aktiver Prompt, verstecke Löschen-Button
                    $('#deletePrompt').hide();
                }
                
                log('Prompts geladen', response.data.prompts);
            } else {
                showError('Fehler beim Laden der Prompts: ' + (response.data ? response.data.message : 'Unbekannter Fehler'));
            }
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            showError('AJAX-Fehler beim Laden der Prompts', {
                status: textStatus,
                error: errorThrown,
                response: jqXHR.responseText
            });
        });
    }

    // Prompt speichern
    $('#savePrompt').on('click', function(e) {
        e.preventDefault();
        
        const prompt = $('#promptText').val();
        log('Speichere Prompt', { prompt: prompt });
        
        // Validierung
        if (!prompt || prompt.trim().length === 0) {
            showError('Bitte geben Sie einen Prompt ein');
            return;
        }

        if (prompt.length < 10) {
            showError('Der Prompt ist zu kurz');
            return false;
        }

        if (prompt.length > 2000) {
            showError('Der Prompt ist zu lang');
            return false;
        }

        const $button = $(this);
        const originalText = $button.text();
        $button.prop('disabled', true).text('Speichere...');

        return $.ajax({
            url: AJAX_URL,
            type: 'POST',
            data: {
                action: 'deepposter_save_prompt',
                prompt: prompt,
                nonce: $('#deepposter_nonce').val()
            }
        }).then(function(response) {
            log('Speicher-Antwort erhalten', response);
            try {
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }

                if (response.success) {
                    showSuccess('Prompt erfolgreich gespeichert');
                    // Lade die Prompt-Liste neu
                    return loadPrompts();
                } else {
                    const errorMessage = response.data && response.data.message 
                        ? response.data.message 
                        : 'Fehler beim Speichern des Prompts';
                    showError(errorMessage);
                }
            } catch (e) {
                log('JSON-Parsing-Fehler', e);
                showError('Fehler beim Verarbeiten der Server-Antwort');
            }
        }).always(function() {
            $button.prop('disabled', false).text(originalText);
        });
    });

    // Prompt bei Auswahl laden
    $('#promptSelect').on('change', function() {
        const promptId = $(this).val();
        $('#promptId').val(promptId);
        
        if (!promptId) {
            $('#promptText').val('');
            $('#deletePrompt').hide();
            return;
        }

        log('Prompt ausgewählt', { id: promptId });
        
        // Zeige den Löschen-Button, wenn ein Prompt ausgewählt ist
        $('#deletePrompt').show();
        
        return $.ajax({
            url: AJAX_URL,
            type: 'POST',
            data: {
                action: 'deepposter_get_prompt',
                nonce: window.deepposterAdmin.nonce,
                prompt_id: promptId
            }
        }).then(function(response) {
            log('Prompt-Lade-Antwort erhalten', response);
            try {
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }

                if (response.success && response.data && response.data.content) {
                    $('#promptText').val(response.data.content);
                } else {
                    showError('Fehler beim Laden des Prompts: ' + (response.data ? response.data.message : 'Unbekannter Fehler'));
                }
            } catch (e) {
                log('JSON-Parsing-Fehler', e);
                showError('Fehler beim Laden des Prompts');
            }
        });
    });

    // Prompt bei Kategorieänderung aktualisieren
    $('#categorySelect').on('change', function() {
        const category = $(this).find('option:selected').text();
        const currentPrompt = $('#promptText').val();
        const updatedPrompt = currentPrompt.replace(/\[KATEGORIE\]/g, category);
        $('#promptText').val(updatedPrompt);
    });

    // Prompt löschen
    $('#deletePrompt').on('click', function(e) {
        e.preventDefault();
        
        const promptId = $('#promptId').val();
        if (!promptId) {
            showError('Kein Prompt zum Löschen ausgewählt');
            return;
        }
        
        const promptTitle = $('#promptSelect option:selected').text();
        
        // Bestätigungsdialog anzeigen
        if (!confirm('Möchten Sie den Prompt "' + promptTitle + '" wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.')) {
            return;
        }
        
        log('Lösche Prompt', { id: promptId, title: promptTitle });
        
        const $button = $(this);
        const originalText = $button.text();
        $button.prop('disabled', true).text('Lösche...');
        
        return $.ajax({
            url: AJAX_URL,
            type: 'POST',
            data: {
                action: 'deepposter_delete_prompt',
                prompt_id: promptId,
                nonce: window.deepposterAdmin.nonce
            }
        }).then(function(response) {
            log('Lösch-Antwort erhalten', response);
            try {
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }

                if (response.success) {
                    showSuccess('Prompt erfolgreich gelöscht');
                    // Setze das Formular zurück und verstecke den Löschen-Button
                    $('#promptSelect').val('');
                    $('#promptText').val('');
                    $('#promptId').val('');
                    $('#deletePrompt').hide();
                    // Lade die Prompt-Liste neu
                    return loadPrompts();
                } else {
                    const errorMessage = response.data && response.data.message 
                        ? response.data.message 
                        : 'Fehler beim Löschen des Prompts';
                    showError(errorMessage);
                }
            } catch (e) {
                log('JSON-Parsing-Fehler', e);
                showError('Fehler beim Verarbeiten der Server-Antwort');
            }
        }).always(function() {
            $button.prop('disabled', false).text(originalText);
        });
    });

    // Artikel generieren
    $('form').on('submit', function(e) {
        e.preventDefault();
        
        const prompt = $('#promptText').val();
        const category = $('#categorySelect').val();
        
        // Validierung
        if (!prompt || prompt.trim().length === 0) {
            showError('Bitte geben Sie einen Prompt ein');
            return;
        }

        if (!category) {
            showError('Bitte wählen Sie eine Kategorie aus');
            return;
        }

        const $form = $(this);
        const $button = $form.find('button[type="submit"]');
        const $results = $('#generationResults');
        
        // Button deaktivieren
        $button.prop('disabled', true).text('Generiere...');
        
        // Zeige Ladeanimation
        $results.html('<div class="notice notice-info"><p>Generiere Artikel...</p></div>');

        $.ajax({
            url: AJAX_URL,
            type: 'POST',
            data: {
                action: 'deepposter_generate',
                prompt: prompt,
                category: category,
                nonce: $('#deepposter_nonce').val()
            },
            success: function(response) {
                try {
                    if (typeof response === 'string') {
                        response = JSON.parse(response);
                    }

                    if (response.success) {
                        showSuccess('Artikel erfolgreich generiert');
                        let html = '<div class="notice notice-success"><p>Artikel erfolgreich generiert:</p><ul>';
                        response.data.forEach(function(post) {
                            html += `<li>
                                <strong>${post.title}</strong><br>
                                Kategorie: ${post.category}<br>
                                Status: ${post.status}<br>
                                <a href="${post.editUrl}" target="_blank" class="button">Bearbeiten</a>
                                <a href="${post.viewUrl}" target="_blank" class="button">Ansehen</a>
                            </li>`;
                        });
                        html += '</ul></div>';
                        $results.html(html);
                    } else {
                        const errorMessage = response.data && response.data.message 
                            ? response.data.message 
                            : 'Fehler beim Generieren des Artikels';
                        showError(errorMessage, response);
                    }
                } catch (e) {
                    console.error('DeepPoster Debug - JSON-Parsing-Fehler:', e);
                    showError('Fehler beim Verarbeiten der Server-Antwort', e);
                }
            },
            complete: function() {
                $button.prop('disabled', false).text('Artikel generieren');
            }
        });
    });

    // Lade die Prompts beim Start
    log('Seite geladen');
    loadPrompts().then(function() {
        log('Initiales Laden der Prompts abgeschlossen');
    });
}); 