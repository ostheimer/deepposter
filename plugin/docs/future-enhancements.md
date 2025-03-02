# DeepPoster - Zukünftige Verbesserungen

## Übersicht
Dieses Dokument enthält Vorschläge für zukünftige Erweiterungen des DeepPoster-Plugins, die auf Basis der aktuellen Implementierung vorgesehen sind. Diese Features sollen die Benutzerfreundlichkeit und Funktionalität des Plugins weiter verbessern.

## 1. Löschfunktion für Prompts

### Beschreibung
Benutzer sollten in der Lage sein, gespeicherte Prompts direkt aus dem Dropdown-Menü zu löschen, ohne umständliche Navigationsschritte.

### Funktionsumfang
- Hinzufügen eines Lösch-Buttons (Mülleimer-Icon) neben jedem Prompt im Dropdown
- Implementierung eines Bestätigungsdialogs vor dem endgültigen Löschen
- Sofortige Aktualisierung der Dropdown-Liste nach dem Löschen

### Technische Anforderungen
- JavaScript-Erweiterung für das Dropdown-Menü
- AJAX-Handler zum Löschen von Prompts in der Datenbank
- Benutzerfreundliche Bestätigungsabfrage
- Berechtigungsprüfung vor dem Löschen

## 2. Prompt-Kategorisierung

### Beschreibung
Benutzer sollten Prompts kategorisieren können, um sie besser zu organisieren und schneller zu finden, besonders bei einer großen Anzahl von gespeicherten Prompts.

### Funktionsumfang
- Hinzufügen von Tags oder Kategorien zu Prompts
- Filterung der Prompts nach Kategorien im Dropdown
- Mehrere Kategorien pro Prompt möglich
- Visuelle Kennzeichnung der Kategorien (z.B. durch Farben)

### Technische Anforderungen
- Erweiterung der Prompt-Speicherfunktion um Kategoriefeld(er)
- Filterungslogik für die Anzeige im Dropdown
- Benutzeroberfläche für Kategorieerstellung und -verwaltung
- Datenbankstruktur für die Speicherung der Kategorien

## 3. Import/Export von Prompts

### Beschreibung
Benutzer sollten ihre Prompts exportieren und importieren können, um sie zu sichern oder mit anderen Nutzern zu teilen.

### Funktionsumfang
- Export aller Prompts in eine JSON- oder CSV-Datei
- Import von Prompts aus einer Datei
- Selektiver Export einzelner Prompts
- Conflict-Management beim Import (überschreiben/duplizieren/überspringen)

### Technische Anforderungen
- Export-Funktion mit Datei-Download
- Import-Funktion mit Datei-Upload
- Validierung der importierten Daten
- Fehlerbehandlung bei ungültigen Importdaten
- Benutzerfreundliche Oberfläche für den Import/Export-Prozess

## Prioritäten und Zeitplan

Diese Features sind nach Priorität geordnet:

1. **Löschfunktion für Prompts** - Hohe Priorität, kurzfristige Umsetzung
2. **Prompt-Kategorisierung** - Mittlere Priorität, mittelfristige Umsetzung
3. **Import/Export von Prompts** - Niedrige Priorität, langfristige Umsetzung

Die genaue Zeitplanung wird im Rahmen der Projektplanung festgelegt. 