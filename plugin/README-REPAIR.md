# DeepPoster Datenbank-Reparatur-Tool

Dieses Tool wurde entwickelt, um Probleme mit duplizierten IDs im Custom Post Type `deepposter_prompt` zu beheben, die zu Fehlern wie "Cannot create item with duplicate id" führen können.

## Symptome des Problems

Wenn Sie folgende Fehler in der Browser-Konsole sehen, kann das Reparatur-Tool helfen:

```
Cannot create item with duplicate id: deepposter_prompt
```

Diese Fehler treten auf, wenn in der WordPress-Datenbank mehrere Posts mit dem gleichen `post_name` existieren, was zu Konflikten führt.

## Verwendung des Reparatur-Tools

### 1. Zugriff auf das Tool

Nach der Installation des Updates finden Sie einen neuen Menüpunkt "Datenbank reparieren" im DeepPoster-Menü:

1. Melden Sie sich im WordPress-Admin an
2. Navigieren Sie zu "DeepPoster" im Hauptmenü
3. Klicken Sie auf "Datenbank reparieren" im Untermenü

### 2. Durchführen der Reparatur

1. **Wichtig**: Erstellen Sie vor der Verwendung ein Backup Ihrer Datenbank
2. Klicken Sie auf den Button "Datenbank reparieren"
3. Das Tool wird automatisch:
   - Duplizierte Post-Namen im Custom Post Type `deepposter_prompt` identifizieren
   - Eindeutige Namen für duplizierte Einträge generieren
   - Duplizierte Meta-Einträge bereinigen
   - Die Ergebnisse des Reparaturvorgangs anzeigen

### 3. Nach der Reparatur

1. Laden Sie die DeepPoster-Hauptseite neu
2. Die Dropdown-Liste "Prompt auswählen" sollte nun korrekt funktionieren
3. Überprüfen Sie die Browser-Konsole - die Fehlermeldungen sollten verschwunden sein

## Manuelle Reparatur (für Entwickler)

Falls das Tool nicht wie erwartet funktioniert, können Sie die Reparatur auch manuell durchführen:

```sql
-- Identifiziere duplizierte Post-Namen
SELECT post_name, COUNT(*) as count
FROM wp_posts
WHERE post_type = 'deepposter_prompt'
GROUP BY post_name
HAVING COUNT(*) > 1;

-- Aktualisiere duplizierte Post-Namen (für jeden duplizierten Eintrag ausführen)
UPDATE wp_posts
SET post_name = CONCAT(post_name, '-', UUID())
WHERE ID = [POST_ID] AND post_type = 'deepposter_prompt';

-- Identifiziere duplizierte Meta-Einträge
SELECT post_id, meta_key, COUNT(*) as count
FROM wp_postmeta
WHERE post_id IN (
    SELECT ID FROM wp_posts WHERE post_type = 'deepposter_prompt'
)
GROUP BY post_id, meta_key
HAVING COUNT(*) > 1;

-- Lösche duplizierte Meta-Einträge (für jeden duplizierten Eintrag ausführen)
DELETE FROM wp_postmeta
WHERE meta_id = [META_ID];
```

## Technische Details

Das Reparatur-Tool führt folgende Aktionen durch:

1. Identifiziert Posts vom Typ `deepposter_prompt` mit identischen `post_name`-Werten
2. Behält den ersten Eintrag bei und generiert für alle anderen eindeutige Namen
3. Identifiziert duplizierte Meta-Einträge für diese Posts
4. Behält den ersten Meta-Eintrag bei und löscht die Duplikate
5. Leert den WordPress-Cache, um sicherzustellen, dass die Änderungen wirksam werden

## Fehlerbehebung

Wenn nach der Reparatur weiterhin Probleme auftreten:

1. Überprüfen Sie die WordPress-Fehlerprotokolle
2. Stellen Sie sicher, dass die Datenbank-Tabellen nicht beschädigt sind
3. Führen Sie ein WordPress-Datenbank-Reparatur-Plugin aus
4. Kontaktieren Sie den Support, wenn die Probleme bestehen bleiben
