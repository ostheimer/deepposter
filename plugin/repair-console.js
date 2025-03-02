/**
 * DeepPoster Datenbank-Reparatur-Skript
 * 
 * Dieses Skript kann direkt in der Browser-Konsole ausgeführt werden,
 * um die Reparatur der duplizierten IDs zu starten.
 * 
 * Anleitung:
 * 1. Öffnen Sie die WordPress-Admin-Seite
 * 2. Öffnen Sie die Browser-Konsole (F12 oder Rechtsklick -> Untersuchen -> Konsole)
 * 3. Kopieren Sie den gesamten Inhalt dieses Skripts
 * 4. Fügen Sie es in die Konsole ein und drücken Sie Enter
 */

(function() {
    console.log('DeepPoster Datenbank-Reparatur wird gestartet...');
    
    // Erstelle einen Container für die Ausgabe
    const container = document.createElement('div');
    container.style.position = 'fixed';
    container.style.top = '50px';
    container.style.left = '50%';
    container.style.transform = 'translateX(-50%)';
    container.style.zIndex = '99999';
    container.style.width = '80%';
    container.style.maxWidth = '800px';
    container.style.padding = '20px';
    container.style.backgroundColor = '#f9f9f9';
    container.style.border = '1px solid #ddd';
    container.style.borderRadius = '5px';
    container.style.boxShadow = '0 0 10px rgba(0,0,0,0.2)';
    container.style.maxHeight = '80vh';
    container.style.overflow = 'auto';
    
    // Füge Inhalt hinzu
    container.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h2 style="margin: 0; color: #0073aa;">DeepPoster Datenbank-Reparatur</h2>
            <button id="closeRepair" style="background: none; border: none; cursor: pointer; font-size: 20px;">&times;</button>
        </div>
        <p>Dieses Tool repariert duplizierte IDs im Custom Post Type "deepposter_prompt", die zu Fehlern führen können.</p>
        <div style="padding: 10px 15px; margin: 15px 0; border-left: 4px solid #ffb900; background-color: #fff8e5;">
            <p><strong>Wichtig:</strong> Bitte erstellen Sie ein Backup Ihrer Datenbank, bevor Sie fortfahren.</p>
        </div>
        <button id="startRepair" style="background: #0073aa; color: white; border: none; padding: 10px 15px; border-radius: 3px; cursor: pointer; font-size: 16px;">Datenbank reparieren</button>
        <div id="repairResults" style="margin-top: 20px; display: none;">
            <h3>Ergebnisse:</h3>
            <div id="repairOutput"></div>
        </div>
    `;
    
    // Füge den Container zum Dokument hinzu
    document.body.appendChild(container);
    
    // Event-Handler für das Schließen-Button
    document.getElementById('closeRepair').addEventListener('click', function() {
        document.body.removeChild(container);
    });
    
    // Event-Handler für den Reparatur-Button
    document.getElementById('startRepair').addEventListener('click', function() {
        const startButton = this;
        const results = document.getElementById('repairResults');
        const output = document.getElementById('repairOutput');
        
        // Button deaktivieren und Text ändern
        startButton.disabled = true;
        startButton.textContent = 'Repariere...';
        
        // AJAX-Request erstellen
        const xhr = new XMLHttpRequest();
        xhr.open('POST', ajaxurl || '/wp-admin/admin-ajax.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onload = function() {
            // Ergebnisse anzeigen
            results.style.display = 'block';
            
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    console.log('Reparatur-Antwort:', response);
                    
                    if (response.success) {
                        output.innerHTML = `<div style="padding: 10px 15px; margin: 15px 0; border-left: 4px solid #46b450; background-color: #ecf7ed;"><p>${response.data.message}</p></div>`;
                        if (response.data.details) {
                            output.innerHTML += `<pre style="background: #f1f1f1; padding: 10px; overflow: auto; font-family: monospace; font-size: 14px;">${response.data.details}</pre>`;
                        }
                    } else {
                        output.innerHTML = `<div style="padding: 10px 15px; margin: 15px 0; border-left: 4px solid #dc3232; background-color: #fbeaea;"><p>Fehler: ${response.data.message}</p></div>`;
                    }
                } catch (e) {
                    console.error('Fehler beim Parsen der Antwort:', e);
                    output.innerHTML = `<div style="padding: 10px 15px; margin: 15px 0; border-left: 4px solid #dc3232; background-color: #fbeaea;"><p>Fehler beim Parsen der Antwort: ${e.message}</p></div>`;
                    output.innerHTML += `<pre style="background: #f1f1f1; padding: 10px; overflow: auto; font-family: monospace; font-size: 14px;">${xhr.responseText}</pre>`;
                }
            } else {
                output.innerHTML = `<div style="padding: 10px 15px; margin: 15px 0; border-left: 4px solid #dc3232; background-color: #fbeaea;"><p>Fehler: ${xhr.status} ${xhr.statusText}</p></div>`;
            }
            
            // Button wieder aktivieren
            startButton.disabled = false;
            startButton.textContent = 'Datenbank reparieren';
        };
        
        xhr.onerror = function() {
            console.error('Fehler bei der AJAX-Anfrage');
            results.style.display = 'block';
            output.innerHTML = '<div style="padding: 10px 15px; margin: 15px 0; border-left: 4px solid #dc3232; background-color: #fbeaea;"><p>Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.</p></div>';
            
            // Button wieder aktivieren
            startButton.disabled = false;
            startButton.textContent = 'Datenbank reparieren';
        };
        
        // Daten senden
        xhr.send('action=deepposter_repair_duplicate_ids');
        console.log('Reparatur-Anfrage gesendet');
    });
    
    console.log('DeepPoster Datenbank-Reparatur-Tool wurde geladen. Klicken Sie auf "Datenbank reparieren", um fortzufahren.');
})(); 