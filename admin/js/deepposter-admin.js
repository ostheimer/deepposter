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
                // Entferne alle vorhandenen Optionen
                $select.empty();
                
                // Füge den Platzhalter als erste Option hinzu
                $select.append($('<option>', {
                    value: '',
                    text: 'Prompt auswählen'
                }));
                
                // Füge die gespeicherten Prompts hinzu
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