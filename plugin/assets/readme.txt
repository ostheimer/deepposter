=== DeepPoster Fehlerbehebung ===

== Problembehebung für leere Dropdown-Liste ==

Wenn die Dropdown-Liste für Prompts leer bleibt, können Sie folgende Lösungen ausprobieren:

1. **JavaScript-Fix im Browser ausführen**
   Öffnen Sie die Browser-Konsole (F12 oder Rechtsklick > Untersuchen > Konsole) und führen Sie folgenden Code aus:
   ```javascript
   jQuery('select[name="prompt"]').empty().append('<option value="">Prompt auswählen</option>').append('<option value="test1">Test-Prompt 1</option>').append('<option value="test2">Test-Prompt 2</option>').append('<option value="test3">Test-Prompt 3</option>');
   ```

2. **Manuell einen Prompt erstellen**
   Erstellen Sie im WordPress-Admin unter "DeepPoster Prompts" einen neuen Prompt und setzen Sie ihn als aktiv.

3. **Manuelle Datenbank-Bereinigung**
   Wenn die obigen Methoden nicht funktionieren, können Sie folgende SQL-Befehle in phpMyAdmin oder einem anderen Datenbank-Tool ausführen:

   ```sql
   -- Erstelle einen Test-Prompt
   INSERT INTO wp_posts (
       post_author, post_date, post_date_gmt, post_content, post_title, 
       post_excerpt, post_status, comment_status, ping_status, post_password, 
       post_name, to_ping, pinged, post_modified, post_modified_gmt, 
       post_content_filtered, post_parent, guid, menu_order, post_type, 
       post_mime_type, comment_count
   ) VALUES (
       1, NOW(), NOW(), 'Du bist ein professioneller Content-Ersteller für WordPress-Blogs. Erstelle einen gut strukturierten Artikel in der Kategorie [KATEGORIE]. Der Artikel sollte informativ, gut recherchiert und SEO-optimiert sein.', 
       'Test-Prompt (SQL)', '', 'publish', 'closed', 'closed', '', 
       'test-prompt-sql', '', '', NOW(), NOW(), 
       '', 0, '', 0, 'deepposter_prompt', 
       '', 0
   );

   -- Setze den neuen Prompt als aktiven Prompt
   UPDATE wp_options SET option_value = LAST_INSERT_ID() WHERE option_name = 'deepposter_active_prompt';
   ```

== Kontakt ==

Bei weiteren Problemen wenden Sie sich bitte an den Support. 