jQuery(document).ready(function($) {
    // Globaler Error Handler
    const errorLogs = [];
    const originalConsoleError = console.error;
    const originalConsoleLog = console.log;
    const originalConsoleWarn = console.warn;

    // Funktion zum Speichern der Logs
    function saveErrorLogs() {
        $.ajax({
            url: deepposter.ajaxurl,
            method: 'POST',
            data: {
                action: 'deepposter_save_logs',
                nonce: deepposter.nonce,
                logs: JSON.stringify(errorLogs)
            },
            success: function(response) {
                console.log('Logs gespeichert:', response);
            }
        });
    }

    // Override console methods
    console.error = function() {
        errorLogs.push({
            type: 'error',
            timestamp: new Date().toISOString(),
            message: Array.from(arguments).join(' '),
            stack: new Error().stack
        });
        originalConsoleError.apply(console, arguments);
        saveErrorLogs();
    };

    console.warn = function() {
        errorLogs.push({
            type: 'warning',
            timestamp: new Date().toISOString(),
            message: Array.from(arguments).join(' ')
        });
        originalConsoleWarn.apply(console, arguments);
    };

    console.log = function() {
        errorLogs.push({
            type: 'log',
            timestamp: new Date().toISOString(),
            message: Array.from(arguments).join(' ')
        });
        originalConsoleLog.apply(console, arguments);
    };

    // Globaler Error Handler für unbehandelte Fehler
    window.onerror = function(msg, url, lineNo, columnNo, error) {
        errorLogs.push({
            type: 'uncaught_error',
            timestamp: new Date().toISOString(),
            message: msg,
            url: url,
            lineNo: lineNo,
            columnNo: columnNo,
            stack: error ? error.stack : null
        });
        saveErrorLogs();
        return false;
    };

    // Promise Error Handler
    window.addEventListener('unhandledrejection', function(event) {
        errorLogs.push({
            type: 'unhandled_promise_rejection',
            timestamp: new Date().toISOString(),
            message: event.reason,
            stack: event.reason.stack
        });
        saveErrorLogs();
    });

    $('#aiGeneratorForm').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitButton = $form.find(':submit');
        const $results = $('#generationResults');
        
        // Disable submit button
        $submitButton.prop('disabled', true);
        
        // Show loading message
        $results.html('<div class="notice notice-info"><p>Generiere Artikel...</p></div>');
        
        // Collect form data
        const formData = {
            action: 'deepposter_generate',
            nonce: deepposter.nonce,
            category: $('#categorySelect').val(),
            count: $('#articleCount').val(),
            publish: $('#publishImmediately').is(':checked')
        };
        
        // Send AJAX request
        $.post(deepposter.ajaxurl, formData)
            .done(function(response) {
                if (response.success) {
                    let html = '';
                    response.data.forEach(function(post) {
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
                            </div>
                        `;
                    });
                    $results.html(html);
                } else {
                    $results.html(`
                        <div class="notice notice-error">
                            <p>${response.data}</p>
                        </div>
                    `);
                }
            })
            .fail(function(xhr, status, error) {
                $results.html(`
                    <div class="notice notice-error">
                        <p>Ein Fehler ist aufgetreten: ${error}</p>
                    </div>
                `);
            })
            .always(function() {
                $submitButton.prop('disabled', false);
            });
    });

    // Funktion zum Laden der OpenAI Modelle
    function loadOpenAIModels() {
        console.log('Starte Laden der OpenAI Modelle...');
        const $select = $('#model_selection');
        const $loading = $('#loading-models');
        
        if (!$select.length || !$loading.length) {
            console.error('Erforderliche DOM-Elemente nicht gefunden');
            return;
        }

        if (typeof deepposter === 'undefined') {
            console.error('deepposter Objekt nicht gefunden');
            $loading.text('Fehler beim Laden der Modelle: Konfiguration nicht gefunden');
            return;
        }

        console.log('API Key Status:', deepposter.openai_key ? 'Vorhanden' : 'Nicht vorhanden');
        
        if (!deepposter.openai_key) {
            $loading.text('Kein OpenAI API Key konfiguriert');
            return;
        }

        console.log('Sende AJAX-Anfrage...');
        $.ajax({
            url: deepposter.ajaxurl,
            method: 'POST',
            data: {
                action: 'deepposter_get_models',
                nonce: deepposter.nonce
            },
            success: function(response) {
                console.log('AJAX-Antwort erhalten:', response);
                
                let models = [];

                // Verarbeite die API-Antwort
                if (response.success && Array.isArray(response.data)) {
                    console.log('Rohe API-Antwort Modelle:', response.data);
                    
                    // Filtere und sortiere die Modelle
                    const preFilteredModels = response.data.filter(model => {
                        const isValid = model.id.includes('gpt') || 
                                      model.id.includes('chatgpt') || 
                                      model.id.includes('deepseek');
                        console.log(`Modell ${model.id}: ${isValid ? 'behalten' : 'gefiltert'}`);
                        return isValid;
                    });
                    
                    console.log('Nach Filterung:', preFilteredModels);

                    models = preFilteredModels.sort((a, b) => {
                        // Definiere Prioritäten für die Sortierung
                        const priorities = {
                            'gpt-4o': 1,
                            'chatgpt-4o-latest': 2,
                            'gpt-4': 3
                        };
                        
                        // Hole Prioritäten oder setze auf 100 als Standard
                        const priorityA = priorities[a.id] || 100;
                        const priorityB = priorities[b.id] || 100;
                        
                        console.log(`Sortierung: ${a.id} (Prio: ${priorityA}) vs ${b.id} (Prio: ${priorityB})`);
                        
                        // Sortiere nach Priorität, dann alphabetisch
                        if (priorityA !== priorityB) {
                            return priorityA - priorityB;
                        }
                        return a.name.localeCompare(b.name);
                    });

                    console.log('Nach Sortierung:', models);
                } else {
                    console.warn('Unerwartetes API-Antwortformat:', response);
                }

                // Wenn keine Modelle von der API kommen, verwende Fallback
                if (models.length === 0) {
                    console.warn('Keine Modelle von der API erhalten, verwende Fallback');
                    models = [
                        { id: 'gpt-4o', name: 'GPT-4o' },
                        { id: 'chatgpt-4o-latest', name: 'ChatGPT-4o Latest' },
                        { id: 'gpt-4', name: 'GPT-4' },
                        { id: 'gpt-3.5-turbo', name: 'GPT-3.5 Turbo' }
                    ];
                    console.log('Fallback-Modelle:', models);
                }

                // Baue die Modellauswahl
                let html = '<optgroup label="OpenAI">';
                models.forEach(function(model) {
                    // Wähle gpt-4o oder chatgpt-4o-latest als Standard
                    const defaultModel = 'gpt-4o';
                    const currentModel = $select.val() || defaultModel;
                    const selected = model.id === currentModel ? ' selected' : '';
                    console.log(`Modell ${model.id}: ${selected ? 'ausgewählt' : 'nicht ausgewählt'}`);
                    html += `<option value="${model.id}"${selected}>${model.name}</option>`;
                });
                
                html += '</optgroup>';
                html += '<optgroup label="DeepSeek">' +
                       '<option value="deepseek-chat">DeepSeek Chat</option>' +
                       '<option value="deepseek-coder">DeepSeek Coder</option>' +
                       '</optgroup>';
                
                console.log('Generiertes HTML für Select:', html);
                
                // Aktualisiere die Auswahl und verstecke den Ladeindikator
                $select.html(html).show();
                $loading.hide();

                // Setze Standard-Token-Anzahl basierend auf Modell
                const $tokenInput = $('#max_tokens');
                if (!$tokenInput.val()) {
                    if (currentModel.includes('gpt-4')) {
                        $tokenInput.val(32000); // Höhere Token-Anzahl für GPT-4 Modelle
                    } else if (currentModel.includes('gpt-3.5-turbo-16k')) {
                        $tokenInput.val(16000); // 16k Variante
                    } else {
                        $tokenInput.val(8000); // Standard für andere Modelle
                    }
                }

                // Debug-Ausgabe der finalen Auswahl
                console.log('Finale Modellauswahl:', $select.val());
                console.log('Maximale Tokens:', $tokenInput.val());
            },
            error: function(xhr, status, error) {
                console.error('AJAX-Fehler:', {xhr, status, error});
                $loading.text('Fehler beim Laden der Modelle: ' + error);
                
                // Zeige trotzdem die Standardmodelle an
                let html = '<optgroup label="OpenAI">' +
                          '<option value="gpt-4o" selected>GPT-4o</option>' +
                          '<option value="gpt-4">GPT-4</option>' +
                          '<option value="gpt-3.5-turbo">GPT-3.5 Turbo</option>' +
                          '</optgroup>' +
                          '<optgroup label="DeepSeek">' +
                          '<option value="deepseek-chat">DeepSeek Chat</option>' +
                          '<option value="deepseek-coder">DeepSeek Coder</option>' +
                          '</optgroup>';
                
                $select.html(html).show();
            }
        });
    }

    // Lade Modelle wenn wir auf der Einstellungsseite sind
    if (window.location.href.includes('page=deepposter-settings')) {
        console.log('Einstellungsseite erkannt, starte Modell-Ladeprozess');
        loadOpenAIModels();
    }
}); 