jQuery(document).ready(function($) {
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

    $('#aiGeneratorForm').on('submit', function(e) {
        e.preventDefault();
        log('Formular wird abgeschickt');

        const $form = $(this);
        const $submitButton = $form.find('button[type="submit"]');
        const $resultContainer = $('#generationResults');
        const $promptTextarea = $('#promptText');
        const $categorySelect = $('#categorySelect');
        const $articleCount = $('#articleCount');

        // Validiere Eingaben
        if (!$categorySelect.val()) {
            log('Keine Kategorie ausgewählt');
            $resultContainer.html('<div class="notice notice-error"><p>Bitte wählen Sie eine Kategorie aus.</p></div>');
            $categorySelect.focus();
            return;
        }

        if (!$promptTextarea.val() && !$promptTextarea.text()) {
            log('Kein Prompt eingegeben');
            $resultContainer.html('<div class="notice notice-error"><p>Bitte geben Sie einen Prompt ein.</p></div>');
            $promptTextarea.focus();
            return;
        }

        const count = parseInt($articleCount.val(), 10);
        if (isNaN(count) || count < 1 || count > 5) {
            log('Ungültige Artikelanzahl:', count);
            $resultContainer.html('<div class="notice notice-error"><p>Bitte geben Sie eine Anzahl zwischen 1 und 5 ein.</p></div>');
            $articleCount.focus();
            return;
        }

        // Formulardaten sammeln
        const formData = {
            action: 'deepposter_generate',
            nonce: deepposterAdmin.nonce,
            category: $categorySelect.val(),
            count: count,
            publish: $('#publishImmediately').is(':checked'),
            prompt: $promptTextarea.val() || $promptTextarea.text()
        };

        log('Sende Formulardaten:', formData);

        // Button deaktivieren und Ladeanimation anzeigen
        $submitButton.prop('disabled', true).text('Generiere...');
        $resultContainer.html('<div class="notice notice-info"><p>Generiere Artikel...</p><div class="spinner is-active" style="float: none; margin: 10px 0;"></div></div>');

        // AJAX Request senden
        $.ajax({
            url: deepposterAdmin.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                log('AJAX Antwort erhalten:', response);

                if (response.success) {
                    log('Generierung erfolgreich');
                    const posts = response.data;
                    
                    if (!posts || posts.length === 0) {
                        log('Keine Posts in der Antwort');
                        $resultContainer.html('<div class="notice notice-error"><p>Fehler: Es konnten keine Artikel generiert werden.</p></div>');
                        return;
                    }
                    
                    let html = '<div class="notice notice-success"><p>Artikel erfolgreich generiert:</p><ul>';
                    posts.forEach(function(post) {
                        log('Verarbeite Post:', post);
                        html += `<li>
                            <strong>${post.title}</strong><br>
                            Kategorie: ${post.category}<br>
                            Status: ${post.status === 'publish' ? 'Veröffentlicht' : 'Entwurf'}<br>
                            <a href="${post.editUrl}" target="_blank" class="button button-small">Bearbeiten</a>
                            <a href="${post.viewUrl}" target="_blank" class="button button-small">Ansehen</a>
                        </li>`;
                    });
                    html += '</ul></div>';
                    
                    log('Zeige Ergebnis an');
                    $resultContainer.html(html);
                } else {
                    log('Fehler bei der Generierung:', response.data);
                    $resultContainer.html(`<div class="notice notice-error"><p>Fehler: ${response.data}</p></div>`);
                }
            },
            error: function(xhr, status, error) {
                log('AJAX Fehler:', {xhr, status, error});
                let errorMessage = 'Ein unbekannter Fehler ist aufgetreten.';
                
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                } else if (error) {
                    errorMessage = error;
                }
                
                $resultContainer.html(`<div class="notice notice-error"><p>Fehler: ${errorMessage}</p></div>`);
            },
            complete: function() {
                // Button wieder aktivieren
                $submitButton.prop('disabled', false).text('Artikel generieren');
            }
        });
    });

    // Kategorie-Änderung Event
    $('#categorySelect').on('change', function() {
        const selectedCategory = $(this).find('option:selected').text();
        log('Kategorie geändert zu:', selectedCategory);
        
        const $promptTextarea = $('#promptText');
        let currentPrompt = $promptTextarea.val() || $promptTextarea.text();
        currentPrompt = currentPrompt.replace(/\[KATEGORIE\]/g, selectedCategory);
        $promptTextarea.val(currentPrompt);
        
        log('Prompt aktualisiert:', currentPrompt);
    });
}); 