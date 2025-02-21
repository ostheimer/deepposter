jQuery(document).ready(function($) {
    const DEBUG = true;
    const debugLog = [];

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

    // Funktion zum Anzeigen von Fehlermeldungen
    function showError($container, message, details = null) {
        log('Fehler aufgetreten:', { message, details });
        let errorHtml = `<div class="notice notice-error"><p>${message}</p>`;
        if (details && DEBUG) {
            errorHtml += `<pre class="debug-info">${JSON.stringify(details, null, 2)}</pre>`;
        }
        errorHtml += '</div>';
        $container.html(errorHtml);
        $container.addClass('notice-error');
    }

    // Funktion zum Speichern der Debug-Logs
    function saveDebugLogs() {
        if (!debugLog.length) return;

        $.ajax({
            url: deepposterAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'deepposter_save_logs',
                nonce: deepposterAdmin.nonce,
                logs: JSON.stringify(debugLog)
            },
            success: function(response) {
                log('Debug-Logs gespeichert:', response);
            },
            error: function(xhr, status, error) {
                console.error('Fehler beim Speichern der Debug-Logs:', error);
            }
        });
    }

    // Standard-Prompt
    const defaultPrompt = `Du bist ein professioneller Content-Ersteller für WordPress-Blogs. 
Erstelle einen gut strukturierten Artikel in der Kategorie '[KATEGORIE]'. 
Der Artikel sollte informativ, gut recherchiert und SEO-optimiert sein. 
Formatiere den Artikel mit WordPress-kompatiblem HTML und strukturiere ihn mit Überschriften (h2, h3). 
Beginne mit dem Titel in der ersten Zeile, gefolgt von einer Leerzeile und dann dem Artikelinhalt.`;

    // Prompt-Vorschau initialisieren
    $('#promptText').val(defaultPrompt);

    // Prompt bei Kategorieänderung aktualisieren
    $('#categorySelect').on('change', function() {
        updatePromptPreview();
    });

    // Prompt-Vorschau aktualisieren
    function updatePromptPreview() {
        const category = $('#categorySelect option:selected').text();
        const currentPrompt = $('#promptText').val();
        const updatedPrompt = currentPrompt.replace(/\[KATEGORIE\]/g, category);
        $('#promptText').val(updatedPrompt);
    }

    // Formular absenden
    $('#aiGeneratorForm').on('submit', function(e) {
        e.preventDefault();
        log('Formular wird abgeschickt');

        const $form = $(this);
        const $submitButton = $form.find('button[type="submit"]');
        const $resultContainer = $('#generationResults');
        
        // Deaktiviere Submit-Button
        $submitButton
            .addClass('button button-primary')
            .attr('disabled', 'disabled')
            .text('Generiere...');
        
        // Zeige Lade-Animation
        $resultContainer.removeClass('notice-error notice-success');
        $resultContainer.html('<div class="notice notice-info"><p>Generiere Artikel...</p></div>');
        
        // Sammle Formulardaten
        const formData = {
            action: 'deepposter_generate',
            nonce: deepposterAdmin.nonce,
            category: $('#categorySelect').val(),
            count: $('#articleCount').val(),
            publish: $('#publishImmediately').is(':checked'),
            prompt: $('#promptText').val()
        };
        
        log('Sende Formulardaten:', formData);
        
        // Sende AJAX-Request
        $.post(ajaxurl, formData)
            .done(function(response) {
                if (response.success) {
                    // Zeige generierte Artikel an
                    let html = '<div class="notice notice-success"><p>Artikel erfolgreich generiert:</p>';
                    response.data.forEach(function(post) {
                        log('Verarbeite generierten Post:', post);
                        html += `
                            <div class="generation-item">
                                <h4>${post.title}</h4>
                                <div class="generation-meta">
                                    Kategorie: ${post.category}
                                    Status: ${post.status}
                                </div>
                                <div class="generation-actions">
                                    <a href="${post.editUrl}" class="button" target="_blank">Bearbeiten</a>
                                    <a href="${post.viewUrl}" class="button" target="_blank">Ansehen</a>
                                </div>
                            </div>`;
                    });
                    html += '</div>';
                    $resultContainer.html(html);
                    $resultContainer.addClass('notice-success');
                } else {
                    // Zeige Fehlermeldung
                    showError($resultContainer, response.data || 'Unbekannter Fehler');
                }
            })
            .fail(function(xhr, status, error) {
                // Zeige Fehlermeldung bei AJAX-Fehler
                showError($resultContainer, 'Ein Fehler ist aufgetreten: ' + error, { xhr, status, error });
            })
            .always(function() {
                // Aktiviere Submit-Button wieder
                $submitButton
                    .removeAttr('disabled')
                    .text('Artikel generieren');
                saveDebugLogs();
            });
    });
}); 