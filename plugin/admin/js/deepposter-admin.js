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
        
        log('Lade verfügbare Modelle von OpenAI');

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
                    
                    $modelSelect.empty();
                    response.data.forEach(function(model) {
                        $modelSelect.append(`<option value="${model.id}">${model.id}</option>`);
                    });
                    
                    $loadingIndicator.hide();
                    $modelSelect.show();
                } else {
                    log('Fehler beim Laden der Modelle:', response.data);
                    $loadingIndicator.text('Fehler beim Laden der Modelle');
                }
            },
            error: function(xhr, status, error) {
                log('AJAX Fehler beim Laden der Modelle:', {xhr, status, error});
                $loadingIndicator.text('Fehler beim Laden der Modelle');
            }
        });
    }

    // Event-Listener für API Provider Änderung
    $('#api_provider').on('change', function() {
        loadAvailableModels();
    });

    // Initial beim Laden der Seite
    loadAvailableModels();

    $('#aiGeneratorForm').on('submit', function(e) {
        e.preventDefault();
        log('Formular wird abgeschickt');

        const $form = $(this);
        const $submitButton = $form.find('button[type="submit"]');
        const $resultContainer = $('#generationResults');
        const $promptTextarea = $('#promptText');

        // Formulardaten sammeln
        const formData = {
            action: 'deepposter_generate',
            nonce: deepposterAdmin.nonce,
            category: $('#categorySelect').val(),
            count: $('#articleCount').val(),
            publish: $('#publishImmediately').is(':checked'),
            prompt: $promptTextarea.val() || $promptTextarea.text()
        };

        log('Gesammelte Formulardaten:', formData);

        if (!formData.prompt) {
            log('Kein Prompt gefunden!');
            $resultContainer.html('<div class="error">Fehler: Das Prompt-Feld ist leer.</div>');
            return;
        }

        // Button deaktivieren und Ladeanimation anzeigen
        $submitButton.prop('disabled', true).text('Generiere...');
        $resultContainer.empty();

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
                    
                    posts.forEach(function(post) {
                        const $item = $('<div class="generation-item"></div>');
                        $item.append(`<h3>${post.title}</h3>`);
                        $item.append(`<p>Kategorie: ${post.category}</p>`);
                        $item.append(`<p>Status: ${post.status}</p>`);
                        $item.append(`<p><a href="${post.editUrl}" target="_blank">Bearbeiten</a> | <a href="${post.viewUrl}" target="_blank">Ansehen</a></p>`);
                        $resultContainer.append($item);
                    });
                } else {
                    log('Fehler bei der Generierung:', response.data);
                    $resultContainer.html(`<div class="error">Fehler: ${response.data}</div>`);
                }
            },
            error: function(xhr, status, error) {
                log('AJAX Fehler:', {xhr, status, error});
                $resultContainer.html('<div class="error">Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.</div>');
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