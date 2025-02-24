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
            // Entferne alle vorhandenen Optionen
            $select.empty();
            
            // Füge den Platzhalter als erste Option hinzu
            $select.append(
                $('<option></option>')
                    .val('')
                    .text('Prompt auswählen')
            );
            
            // Füge die gespeicherten Prompts hinzu
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
            }
        }
    });
} 