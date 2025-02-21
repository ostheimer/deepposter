<?php
/**
 * Klasse für die Artikelgenerierung mit OpenAI
 */

// Lade Abhängigkeiten
require_once plugin_dir_path(__FILE__) . 'class-deepposter-dependencies.php';

class DeepPoster_Generator {
    private $api_key;
    private $api_url = 'https://api.openai.com/v1/chat/completions';
    private $model;
    private $max_tokens;
    private $temperature;

    /**
     * Konstruktor
     */
    public function __construct($api_key) {
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Generator wird initialisiert');
            error_log('API Key vorhanden: ' . (!empty($api_key) ? 'Ja' : 'Nein'));
        }

        if (empty($api_key)) {
            throw new Exception('OpenAI API Key ist nicht konfiguriert.');
        }

        $this->api_key = $api_key;
        $this->model = get_option('deepposter_model', 'gpt-4');
        $this->max_tokens = get_option('deepposter_max_tokens', 10000);
        $this->temperature = get_option('deepposter_temperature', 0.7);

        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Generator initialisiert mit:');
            error_log('Model: ' . $this->model);
            error_log('Max Tokens: ' . $this->max_tokens);
            error_log('Temperature: ' . $this->temperature);
        }

        // Validiere Einstellungen
        if (empty($this->model)) {
            throw new Exception('Kein OpenAI Modell ausgewählt.');
        }

        if (empty($this->max_tokens) || $this->max_tokens < 1) {
            throw new Exception('Ungültige Max Tokens Einstellung.');
        }

        if (!isset($this->temperature) || $this->temperature < 0 || $this->temperature > 2) {
            throw new Exception('Ungültige Temperature Einstellung.');
        }
    }

    /**
     * Generiert die angegebene Anzahl von Artikeln
     */
    public function generate_posts($category_id, $count = 1, $should_publish = false, $custom_prompt = '') {
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Starte Artikelgenerierung:');
            error_log('Category ID: ' . $category_id);
            error_log('Count: ' . $count);
            error_log('Should Publish: ' . ($should_publish ? 'true' : 'false'));
            error_log('Custom Prompt: ' . $custom_prompt);
            error_log('API Key: ' . (empty($this->api_key) ? 'Nicht gesetzt' : 'Vorhanden'));
            error_log('Model: ' . $this->model);
            error_log('Max Tokens: ' . $this->max_tokens);
            error_log('Temperature: ' . $this->temperature);
        }

        try {
            // Validiere API Key
            if (empty($this->api_key)) {
                throw new Exception('OpenAI API Key ist nicht konfiguriert.');
            }

            // Validiere Kategorie
            $category = get_category($category_id);
            if (!$category) {
                if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                    error_log('DeepPoster Debug - Kategorie nicht gefunden: ' . $category_id);
                }
                throw new Exception('Die ausgewählte Kategorie wurde nicht gefunden.');
            }

            // Validiere Anzahl
            $count = intval($count);
            if ($count < 1 || $count > 5) {
                throw new Exception('Die Anzahl der Artikel muss zwischen 1 und 5 liegen.');
            }

            // Validiere Prompt
            if (empty($custom_prompt)) {
                throw new Exception('Bitte geben Sie einen Prompt ein.');
            }

            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Validierung erfolgreich, starte Generierung');
            }

            $generated_posts = array();
            $errors = array();

            for ($i = 0; $i < $count; $i++) {
                if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                    error_log('DeepPoster Debug - Generiere Artikel ' . ($i + 1) . ' von ' . $count);
                }
                try {
                    $post = $this->generate_single_post($category, $should_publish, $custom_prompt);
                    if ($post) {
                        $generated_posts[] = $post;
                        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                            error_log('DeepPoster Debug - Artikel ' . ($i + 1) . ' erfolgreich generiert');
                            error_log('Post Details: ' . print_r($post, true));
                        }
                    } else {
                        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                            error_log('DeepPoster Debug - Artikel ' . ($i + 1) . ' konnte nicht generiert werden (leere Rückgabe)');
                        }
                        $errors[] = 'Artikel ' . ($i + 1) . ' konnte nicht generiert werden.';
                    }
                } catch (Exception $e) {
                    if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                        error_log('DeepPoster Debug - Fehler bei Artikel ' . ($i + 1) . ': ' . $e->getMessage());
                        error_log('DeepPoster Debug - Stack Trace: ' . $e->getTraceAsString());
                    }
                    $errors[] = 'Fehler bei Artikel ' . ($i + 1) . ': ' . $e->getMessage();
                }
            }

            if (empty($generated_posts)) {
                if (!empty($errors)) {
                    throw new Exception('Keine Artikel generiert. Fehler: ' . implode(', ', $errors));
                } else {
                    throw new Exception('Es konnten keine Artikel generiert werden.');
                }
            }

            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Generierung abgeschlossen. Anzahl Artikel: ' . count($generated_posts));
                error_log('DeepPoster Debug - Generierte Artikel: ' . print_r($generated_posts, true));
                if (!empty($errors)) {
                    error_log('DeepPoster Debug - Aufgetretene Fehler: ' . implode(', ', $errors));
                }
            }

            return $generated_posts;
        } catch (Exception $e) {
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Hauptfehler in generate_posts:');
                error_log('Message: ' . $e->getMessage());
                error_log('Stack Trace: ' . $e->getTraceAsString());
            }
            throw $e;
        }
    }

    /**
     * Generiert einen einzelnen Artikel
     */
    private function generate_single_post($category, $should_publish = false, $custom_prompt = '') {
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Starte Einzelartikelgenerierung');
            error_log('DeepPoster Debug - Kategorie: ' . print_r($category, true));
            error_log('DeepPoster Debug - Should Publish: ' . ($should_publish ? 'true' : 'false'));
            error_log('DeepPoster Debug - Custom Prompt: ' . $custom_prompt);
            error_log('DeepPoster Debug - API Key: ' . (empty($this->api_key) ? 'Nicht gesetzt' : 'Vorhanden'));
            error_log('DeepPoster Debug - Model: ' . $this->model);
            error_log('DeepPoster Debug - Max Tokens: ' . $this->max_tokens);
            error_log('DeepPoster Debug - Temperature: ' . $this->temperature);
        }

        try {
            // Validiere API Key
            if (empty($this->api_key)) {
                throw new Exception('OpenAI API Key ist nicht konfiguriert.');
            }

            // Validiere Model
            if (empty($this->model)) {
                throw new Exception('Kein OpenAI Modell ausgewählt.');
            }

            // Erstelle den Prompt für OpenAI
            $prompt = empty($custom_prompt) ? $this->create_prompt($category) : $this->create_custom_prompt($category, $custom_prompt);
            
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Prompt erstellt:');
                error_log('System Message: ' . $prompt[0]['content']);
                error_log('User Message: ' . $prompt[1]['content']);
            }

            // Sende Anfrage an OpenAI
            $response = $this->call_openai_api($prompt);
            
            if (empty($response)) {
                if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                    error_log('DeepPoster Debug - Keine Antwort von OpenAI erhalten');
                }
                throw new Exception('Keine Antwort von OpenAI erhalten.');
            }

            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - OpenAI Antwort erhalten:');
                error_log(print_r($response, true));
            }

            if (!isset($response['choices']) || empty($response['choices'])) {
                if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                    error_log('DeepPoster Debug - Keine Choices in der OpenAI Antwort');
                }
                throw new Exception('Ungültige Antwort von OpenAI (keine Choices)');
            }

            if (!isset($response['choices'][0]['message']['content'])) {
                if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                    error_log('DeepPoster Debug - Kein Content in der OpenAI Antwort');
                }
                throw new Exception('Ungültige Antwort von OpenAI (kein Content)');
            }

            // Extrahiere Titel und Inhalt aus der Antwort
            $content = $response['choices'][0]['message']['content'];
            
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Roher Content von OpenAI:');
                error_log($content);
            }

            list($title, $body) = $this->parse_content($content);

            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Extrahierter Titel: ' . $title);
                error_log('DeepPoster Debug - Extrahierter Body (Länge): ' . strlen($body));
                error_log('DeepPoster Debug - Body Anfang: ' . substr($body, 0, 100));
            }

            // Validiere Titel und Body
            if (empty($title)) {
                throw new Exception('OpenAI hat keinen Titel generiert.');
            }

            if (empty($body)) {
                throw new Exception('OpenAI hat keinen Inhalt generiert.');
            }

            if (strlen($body) < 100) {
                throw new Exception('Generierter Inhalt ist zu kurz (mindestens 100 Zeichen erforderlich)');
            }

            // Erstelle den WordPress-Artikel
            $post_data = array(
                'post_title'    => $title,
                'post_content'  => $body,
                'post_status'   => $should_publish ? 'publish' : 'draft',
                'post_type'     => 'post',
                'post_category' => array($category->term_id)
            );

            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Erstelle WordPress-Artikel mit Daten:');
                error_log(print_r($post_data, true));
            }

            $post_id = wp_insert_post($post_data);

            if (is_wp_error($post_id)) {
                $error_message = 'Fehler beim Erstellen des Artikels: ' . $post_id->get_error_message();
                if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                    error_log('DeepPoster Debug - ' . $error_message);
                }
                throw new Exception($error_message);
            }

            if (!$post_id || $post_id == 0) {
                if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                    error_log('DeepPoster Debug - Artikel konnte nicht erstellt werden (Post ID ist 0 oder leer)');
                }
                throw new Exception('Artikel konnte nicht erstellt werden');
            }

            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Artikel erstellt mit ID: ' . $post_id);
            }

            // Hole die URLs für den erstellten Artikel
            $edit_url = get_edit_post_link($post_id, 'raw');
            $view_url = get_permalink($post_id);

            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Edit URL: ' . $edit_url);
                error_log('DeepPoster Debug - View URL: ' . $view_url);
            }

            if (empty($edit_url) || empty($view_url)) {
                if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                    error_log('DeepPoster Debug - Warnung: URLs konnten nicht generiert werden');
                    error_log('Edit URL: ' . ($edit_url ?: 'leer'));
                    error_log('View URL: ' . ($view_url ?: 'leer'));
                }
            }

            $result = array(
                'id' => $post_id,
                'title' => $title,
                'category' => $category->name,
                'status' => $should_publish ? 'publish' : 'draft',
                'editUrl' => $edit_url,
                'viewUrl' => $view_url
            );

            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Rückgabe der generate_single_post Methode:');
                error_log(print_r($result, true));
            }

            return $result;
        } catch (Exception $e) {
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Fehler in generate_single_post:');
                error_log('Message: ' . $e->getMessage());
                error_log('Stack Trace: ' . $e->getTraceAsString());
            }
            throw $e;
        }
    }

    /**
     * Erstellt den Standard-Prompt für OpenAI
     */
    private function create_prompt($category) {
        return array(
            array(
                'role' => 'system',
                'content' => "Du bist ein professioneller Content-Ersteller für WordPress-Blogs. " .
                            "Erstelle einen gut strukturierten Artikel in der Kategorie '{$category->name}'. " .
                            "Beachte folgende Anforderungen:\n\n" .
                            "1. Der Artikel sollte informativ, gut recherchiert und SEO-optimiert sein.\n" .
                            "2. Verwende eine klare Struktur mit Einleitung, Hauptteil und Schluss.\n" .
                            "3. Nutze aussagekräftige Zwischenüberschriften (h2, h3).\n" .
                            "4. Schreibe in einem professionellen, aber verständlichen Stil.\n" .
                            "5. Formatiere den Text mit WordPress-kompatiblem HTML.\n" .
                            "6. Füge relevante interne Links ein, wo sinnvoll.\n" .
                            "7. Optimiere den Text für Suchmaschinen.\n\n" .
                            "WICHTIG - Formatierung:\n" .
                            "1. Erste Zeile: NUR der Titel des Artikels (ohne Markdown-Formatierung)\n" .
                            "2. Eine Leerzeile\n" .
                            "3. Dann der formatierte Artikelinhalt mit HTML-Tags\n\n" .
                            "Beispiel:\n" .
                            "10 Tipps für besseres SEO\n\n" .
                            "<h2>Einleitung</h2>\n" .
                            "<p>In diesem Artikel...</p>"
            ),
            array(
                'role' => 'user',
                'content' => "Erstelle einen SEO-optimierten Artikel für die Kategorie '{$category->name}'."
            )
        );
    }

    /**
     * Erstellt einen benutzerdefinierten Prompt für OpenAI
     */
    private function create_custom_prompt($category, $custom_prompt) {
        // Ersetze Platzhalter
        $prompt_text = str_replace(
            array('[KATEGORIE]', '[CATEGORY]'), 
            $category->name, 
            $custom_prompt
        );
        
        // Füge Formatierungshinweise hinzu, wenn nicht vorhanden
        if (stripos($prompt_text, 'format') === false) {
            $prompt_text .= "\n\nWICHTIG - Formatierung:\n" .
                          "1. Erste Zeile: NUR der Titel des Artikels (ohne Markdown-Formatierung)\n" .
                          "2. Eine Leerzeile\n" .
                          "3. Dann der formatierte Artikelinhalt mit HTML-Tags\n\n" .
                          "Beispiel:\n" .
                          "10 Tipps für besseres SEO\n\n" .
                          "<h2>Einleitung</h2>\n" .
                          "<p>In diesem Artikel...</p>";
        }
        
        return array(
            array(
                'role' => 'system',
                'content' => $prompt_text
            ),
            array(
                'role' => 'user',
                'content' => "Erstelle einen SEO-optimierten Artikel für die Kategorie '{$category->name}'."
            )
        );
    }

    /**
     * Sendet die Anfrage an die OpenAI API
     */
    private function call_openai_api($messages) {
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - OpenAI API Aufruf:');
            error_log('API URL: ' . $this->api_url);
            error_log('Model: ' . $this->model);
            error_log('Messages: ' . print_r($messages, true));
            error_log('API Key vorhanden: ' . (!empty($this->api_key) ? 'Ja' : 'Nein'));
            error_log('Max Tokens: ' . $this->max_tokens);
            error_log('Temperature: ' . $this->temperature);
        }

        try {
            // Validiere API Key
            if (empty($this->api_key)) {
                throw new Exception('OpenAI API Key ist nicht konfiguriert.');
            }

            // Validiere Messages
            if (empty($messages) || !is_array($messages)) {
                throw new Exception('Ungültiges Message-Format für OpenAI API.');
            }

            // Validiere Model
            if (empty($this->model)) {
                throw new Exception('Kein OpenAI Modell ausgewählt.');
            }

            // Validiere Max Tokens
            if (empty($this->max_tokens) || $this->max_tokens < 1) {
                throw new Exception('Ungültige Max Tokens Einstellung.');
            }

            // Validiere Temperature
            if (!isset($this->temperature) || $this->temperature < 0 || $this->temperature > 2) {
                throw new Exception('Ungültige Temperature Einstellung.');
            }

            $request_data = array(
                'model' => $this->model,
                'messages' => $messages,
                'max_tokens' => intval($this->max_tokens),
                'temperature' => floatval($this->temperature),
                'presence_penalty' => 0.1,
                'frequency_penalty' => 0.1,
                'stream' => false
            );

            $args = array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ),
                'body' => json_encode($request_data),
                'timeout' => 120,
                'sslverify' => true,
                'user-agent' => 'WordPress/DeepPoster-2.0'
            );

            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Request Daten:');
                error_log('Request URL: ' . $this->api_url);
                error_log('Request Headers: ' . print_r($args['headers'], true));
                error_log('Request Body: ' . print_r(json_decode($args['body'], true), true));
            }

            // Sende Anfrage
            $response = wp_remote_post($this->api_url, $args);

            // Prüfe auf WP_Error
            if (is_wp_error($response)) {
                $error_message = 'WordPress HTTP Fehler: ' . $response->get_error_message();
                error_log('DeepPoster Debug - ' . $error_message);
                throw new Exception($error_message);
            }

            // Hole Response Details
            $status_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            $response_headers = wp_remote_retrieve_headers($response);

            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Response Details:');
                error_log('Status Code: ' . $status_code);
                error_log('Response Headers: ' . print_r($response_headers, true));
                error_log('Response Body: ' . $response_body);
            }

            // Prüfe HTTP Status
            if ($status_code !== 200) {
                $error_data = json_decode($response_body, true);
                $error_message = 'OpenAI API Fehler: ';
                
                if (!empty($error_data['error']['message'])) {
                    $error_message .= $error_data['error']['message'];
                } else {
                    $error_message .= 'Status ' . $status_code;
                }

                error_log('DeepPoster Debug - ' . $error_message);
                throw new Exception($error_message);
            }

            // Dekodiere Antwort
            $body = json_decode($response_body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $error_message = 'JSON Dekodierungsfehler: ' . json_last_error_msg();
                error_log('DeepPoster Debug - ' . $error_message);
                throw new Exception($error_message);
            }

            if (empty($body)) {
                $error_message = 'Ungültige API-Antwort von OpenAI (leere Antwort)';
                error_log('DeepPoster Debug - ' . $error_message);
                throw new Exception($error_message);
            }

            if (!isset($body['choices']) || empty($body['choices'])) {
                $error_message = 'Ungültige API-Antwort von OpenAI (keine Choices)';
                error_log('DeepPoster Debug - ' . $error_message);
                error_log('DeepPoster Debug - Response Body: ' . print_r($body, true));
                throw new Exception($error_message);
            }

            if (!isset($body['choices'][0]['message']['content'])) {
                $error_message = 'Ungültige API-Antwort von OpenAI (kein Content)';
                error_log('DeepPoster Debug - ' . $error_message);
                error_log('DeepPoster Debug - Response Body: ' . print_r($body, true));
                throw new Exception($error_message);
            }

            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - API Antwort erfolgreich verarbeitet');
                error_log('Antwort: ' . print_r($body, true));
            }

            return $body;
        } catch (Exception $e) {
            error_log('DeepPoster Debug - Fehler in call_openai_api:');
            error_log('Message: ' . $e->getMessage());
            error_log('Stack Trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Extrahiert Titel und Inhalt aus der OpenAI-Antwort
     */
    private function parse_content($content) {
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Parse Content Start');
            error_log('Roher Content: ' . $content);
            error_log('Content Länge: ' . strlen($content));
        }

        try {
            // Normalisiere Zeilenumbrüche
            $content = str_replace(["\r\n", "\r"], "\n", $content);
            
            // Entferne BOM und andere unsichtbare Zeichen, aber behalte Zeilenumbrüche
            $content = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F-\xFF]/', '', $content);
            
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Content nach Normalisierung:');
                error_log($content);
            }
            
            // Teile den Content in Zeilen und entferne leere Zeilen am Anfang und Ende
            $lines = array_values(array_filter(explode("\n", $content), function($line) {
                return trim($line) !== '';
            }));
            
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Gefundene nicht-leere Zeilen:');
                error_log(print_r($lines, true));
                error_log('Anzahl Zeilen: ' . count($lines));
            }
            
            if (empty($lines)) {
                throw new Exception('Keine Textzeilen in der OpenAI-Antwort gefunden.');
            }
            
            // Erste nicht-leere Zeile ist der Titel
            $title = trim($lines[0]);
            
            // Alle weiteren Zeilen bilden den Body
            $body = trim(implode("\n", array_slice($lines, 1)));
            
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Extrahierte Daten:');
                error_log('Titel (roh): ' . $title);
                error_log('Body (roh): ' . $body);
            }

            // Entferne Markdown-Formatierung vom Titel
            $title = trim($title, '#* ');
            $title = preg_replace('/^#+\s*/', '', $title);
            
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Bereinigter Titel: ' . $title);
            }

            // Validiere Titel
            if (empty($title)) {
                throw new Exception('OpenAI hat keinen gültigen Titel generiert');
            }

            if (strlen($title) > 200) {
                $title = substr($title, 0, 197) . '...';
            }

            // Validiere Body
            if (empty($body)) {
                if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                    error_log('DeepPoster Debug - Body ist leer nach der Extraktion');
                }
                throw new Exception('OpenAI hat keinen gültigen Inhalt generiert');
            }

            if (strlen($body) < 100) {
                if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                    error_log('DeepPoster Debug - Body zu kurz: ' . strlen($body) . ' Zeichen');
                }
                throw new Exception('Generierter Inhalt ist zu kurz (mindestens 100 Zeichen erforderlich)');
            }

            // Bereinige HTML im Body
            $allowed_html = array(
                'p' => array(),
                'h2' => array(),
                'h3' => array(),
                'h4' => array(),
                'ul' => array(),
                'ol' => array(),
                'li' => array(),
                'strong' => array(),
                'em' => array(),
                'blockquote' => array(),
                'a' => array(
                    'href' => array(),
                    'title' => array(),
                    'target' => array()
                )
            );
            
            // Bereinige HTML
            $body = wp_kses($body, $allowed_html);

            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Finales Ergebnis:');
                error_log('Titel: ' . $title);
                error_log('Body Länge: ' . strlen($body));
                error_log('Body Anfang: ' . substr($body, 0, 100));
                error_log('Body Ende: ' . substr($body, -100));
            }

            return array($title, $body);
        } catch (Exception $e) {
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Fehler beim Parsen des Contents:');
                error_log('Message: ' . $e->getMessage());
                error_log('Stack Trace: ' . $e->getTraceAsString());
            }
            throw $e;
        }
    }
} 