jQuery(document).ready(function($) {
    // Standard-Prompt
    const defaultPrompt = `Du bist ein professioneller Content-Ersteller für WordPress-Blogs. 
Erstelle einen gut strukturierten Artikel in der Kategorie '[KATEGORIE]'. 
Der Artikel sollte informativ, gut recherchiert und SEO-optimiert sein. 
Formatiere den Artikel mit WordPress-kompatiblem HTML und strukturiere ihn mit Überschriften (h2, h3). 
Beginne mit dem Titel in der ersten Zeile, gefolgt von einer Leerzeile und dann dem Artikelinhalt.`;

    // Prompt-Vorschau initialisieren
    $('#deepposter-prompt').val(defaultPrompt);

    // Prompt bei Kategorieänderung aktualisieren
    $('#deepposter-category').on('change', function() {
        updatePromptPreview();
    });

    // Prompt-Vorschau aktualisieren
    function updatePromptPreview() {
        const category = $('#deepposter-category option:selected').text();
        const currentPrompt = $('#deepposter-prompt').val();
        const updatedPrompt = currentPrompt.replace(/\[KATEGORIE\]/g, category);
        $('#deepposter-prompt').val(updatedPrompt);
    }

    // Formular absenden
    $('#deepposter-form').on('submit', function(e) {
        e.preventDefault();

        const $form = $(this);
        const $submitButton = $form.find('button[type="submit"]');
        const $resultContainer = $('#deepposter-result');
        
        // Deaktiviere Submit-Button
        $submitButton.prop('disabled', true).text('Generiere Artikel...');
        
        // Zeige Lade-Animation
        $resultContainer.html('<div class="spinner is-active" style="float: none; margin: 0;"></div>');
        
        // Sammle Formulardaten
        const formData = {
            action: 'deepposter_generate',
            nonce: deepposterParams.nonce,
            category: $('#deepposter-category').val(),
            count: $('#deepposter-count').val(),
            publish: $('#deepposter-publish').is(':checked'),
            prompt: $('#deepposter-prompt').val()
        };
        
        // Sende AJAX-Request
        $.post(ajaxurl, formData)
            .done(function(response) {
                if (response.success) {
                    // Zeige generierte Artikel an
                    let html = '<div class="notice notice-success"><p>Artikel erfolgreich generiert:</p><ul>';
                    response.data.forEach(function(post) {
                        html += `<li><strong>${post.title}</strong> - 
                                <a href="${post.edit_url}" target="_blank">Bearbeiten</a>
                                (Status: ${post.status})</li>`;
                    });
                    html += '</ul></div>';
                    $resultContainer.html(html);
                } else {
                    // Zeige Fehlermeldung
                    $resultContainer.html(
                        '<div class="notice notice-error"><p>Fehler: ' + 
                        (response.data || 'Unbekannter Fehler') + '</p></div>'
                    );
                }
            })
            .fail(function(xhr, status, error) {
                // Zeige Fehlermeldung bei AJAX-Fehler
                $resultContainer.html(
                    '<div class="notice notice-error"><p>AJAX-Fehler: ' + error + '</p></div>'
                );
            })
            .always(function() {
                // Aktiviere Submit-Button wieder
                $submitButton.prop('disabled', false).text('Artikel generieren');
            });
    });
}); 