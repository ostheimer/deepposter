# DeepPoster Datenbank-Reparatur über die Konsole

Dieses Dokument beschreibt, wie Sie das DeepPoster Datenbank-Reparatur-Tool über die Browser-Konsole ausführen können, um duplizierte IDs im Custom Post Type "deepposter_prompt" zu reparieren.

## Symptome des Problems

Wenn Sie folgende Probleme beobachten, könnte dies auf duplizierte IDs in der Datenbank hinweisen:

- Die Dropdown-Liste der Prompts wird nicht korrekt angezeigt oder ist leer
- In der Browser-Konsole erscheint die Fehlermeldung "Cannot create item with duplicate id"
- Beim Auswählen eines Prompts treten JavaScript-Fehler auf

## Anleitung zur Reparatur über die Browser-Konsole

1. **Öffnen Sie die WordPress-Admin-Seite**
   - Melden Sie sich in Ihrem WordPress-Admin-Bereich an
   - Navigieren Sie zu einer beliebigen Seite im Admin-Bereich (z.B. Dashboard oder DeepPoster-Seite)

2. **Öffnen Sie die Browser-Konsole**
   - Drücken Sie F12 oder Rechtsklick -> Untersuchen -> Konsole
   - Die Entwicklertools Ihres Browsers sollten sich öffnen

3. **Führen Sie das Reparatur-Skript aus**
   - Kopieren Sie den gesamten Inhalt des folgenden Skripts:
   ```javascript
   (function() {
       const script = document.createElement('script');
       script.src = '/wp-content/plugins/deepposter/assets/repair-console.js';
       script.onload = function() {
           console.log('Reparatur-Tool geladen');
       };
       document.head.appendChild(script);
   })();
   ```
   - Fügen Sie es in die Konsole ein und drücken Sie Enter

4. **Verwenden Sie das Reparatur-Tool**
   - Ein Popup-Fenster mit dem Titel "DeepPoster Datenbank-Reparatur" erscheint
   - Lesen Sie die Warnung und erstellen Sie ein Backup Ihrer Datenbank, falls noch nicht geschehen
   - Klicken Sie auf den Button "Datenbank reparieren"
   - Warten Sie, bis der Reparaturvorgang abgeschlossen ist

5. **Überprüfen Sie die Ergebnisse**
   - Nach Abschluss der Reparatur werden die Ergebnisse angezeigt
   - Bei erfolgreicher Reparatur sehen Sie eine Erfolgsmeldung mit Details zu den reparierten Einträgen
   - Bei Fehlern wird eine entsprechende Fehlermeldung angezeigt

6. **Testen Sie die Funktionalität**
   - Schließen Sie das Reparatur-Tool durch Klicken auf das X
   - Laden Sie die DeepPoster-Seite neu
   - Überprüfen Sie, ob die Dropdown-Liste der Prompts korrekt angezeigt wird
   - Überprüfen Sie, ob keine Fehlermeldungen mehr in der Konsole erscheinen

## Technische Details

Das Reparatur-Tool führt folgende Aktionen aus:

1. Identifiziert Posts mit identischen `post_name`-Werten im Custom Post Type "deepposter_prompt"
2. Generiert eindeutige Namen für duplizierte Einträge
3. Bereinigt duplizierte Meta-Einträge
4. Leert den WordPress-Cache

## Fehlerbehebung

Falls nach der Reparatur weiterhin Probleme auftreten:

1. **Überprüfen Sie die Konsole auf Fehlermeldungen**
   - Öffnen Sie die Browser-Konsole (F12)
   - Suchen Sie nach spezifischen Fehlermeldungen

2. **Versuchen Sie eine manuelle Reparatur**
   - Führen Sie die folgenden SQL-Befehle in phpMyAdmin oder einem anderen Datenbank-Tool aus:
   ```sql
   -- Identifizieren Sie duplizierte post_name-Werte
   SELECT post_name, COUNT(*) as count 
   FROM wp_posts 
   WHERE post_type = 'deepposter_prompt' 
   GROUP BY post_name 
   HAVING count > 1;
   
   -- Aktualisieren Sie duplizierte post_name-Werte (für jeden Duplikat-Eintrag einzeln ausführen)
   UPDATE wp_posts 
   SET post_name = CONCAT(post_name, '-', ID) 
   WHERE ID = [ID des duplizierten Eintrags];
   ```

3. **Kontaktieren Sie den Support**
   - Wenn die Probleme weiterhin bestehen, kontaktieren Sie den DeepPoster-Support
   - Teilen Sie die Fehlermeldungen und die Ergebnisse des Reparatur-Tools mit

## Hinweise

- Das Reparatur-Tool ist nur für Administratoren gedacht
- Erstellen Sie immer ein Backup Ihrer Datenbank, bevor Sie das Tool verwenden
- Das Tool ändert Daten in Ihrer WordPress-Datenbank 