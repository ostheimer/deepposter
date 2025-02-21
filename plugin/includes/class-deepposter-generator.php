<?php
/**
 * Klasse für die Artikelgenerierung mit OpenAI
 */

// Setze UTF-8 Encoding
if (!defined('ABSPATH')) {
    header('Content-Type: text/html; charset=utf-8');
}

// Lade Abhängigkeiten
require_once plugin_dir_path(__FILE__) . 'class-deepposter-dependencies.php';

class DeepPoster_Generator {
    private $api_key;
    private $api_url = 'https://api.openai.com/v1/chat/completions';
    private $model;
    private $max_tokens;
    private $temperature;
    private static $generated_titles = array(); // Cache für generierte Titel

    /**
     * Konstruktor
     */
    public function __construct($api_key) {
        global $wpdb;
        
        // Setze Zeichensatz für Datenbankverbindung
        if (!empty($wpdb)) {
            $wpdb->query("SET NAMES 'utf8mb4'");
            $wpdb->query("SET CHARACTER SET 'utf8mb4'");
        }

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

            // Generiere die angeforderte Anzahl von Artikeln
            for ($i = 0; $i < $count; $i++) {
                if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                    error_log('DeepPoster Debug - Generiere Artikel ' . ($i + 1) . ' von ' . $count);
                }
                
                try {
                    $post = $this->generate_single_post($category, $should_publish, $custom_prompt);
                    
                    if (empty($post)) {
                        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                            error_log('DeepPoster Debug - Artikel ' . ($i + 1) . ' konnte nicht generiert werden (leere Rückgabe)');
                        }
                        $errors[] = 'Artikel ' . ($i + 1) . ' konnte nicht generiert werden (leere Rückgabe)';
                        continue; // Fahre mit dem nächsten Artikel fort
                    }
                    
                    $generated_posts[] = $post;
                    
                    if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                        error_log('DeepPoster Debug - Artikel ' . ($i + 1) . ' erfolgreich generiert:');
                        error_log('- Titel: ' . $post['title']);
                        error_log('- Kategorie: ' . $post['category']);
                        error_log('- Status: ' . $post['status']);
                        error_log('- Post ID: ' . $post['id']);
                        if (!empty($post['tags'])) {
                            error_log('- Schlagwörter: ' . implode(', ', $post['tags']));
                        }
                        error_log('------------------------');
                    }
                } catch (Exception $e) {
                    if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                        error_log('DeepPoster Debug - Fehler bei Artikel ' . ($i + 1) . ': ' . $e->getMessage());
                        error_log('DeepPoster Debug - Stack Trace: ' . $e->getTraceAsString());
                    }
                    $errors[] = 'Fehler bei Artikel ' . ($i + 1) . ': ' . $e->getMessage();
                    continue; // Fahre mit dem nächsten Artikel fort
                }
            }

            // Prüfe die Ergebnisse
            if (empty($generated_posts)) {
                if (!empty($errors)) {
                    throw new Exception('Keine Artikel generiert. Fehler: ' . implode(', ', $errors));
                } else {
                    throw new Exception('Es konnten keine Artikel generiert werden.');
                }
            }

            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Generierung abgeschlossen');
                error_log('- Anzahl generierter Artikel: ' . count($generated_posts));
                error_log('- Anzahl Fehler: ' . count($errors));
                if (!empty($errors)) {
                    error_log('- Aufgetretene Fehler: ' . implode(', ', $errors));
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
     * Prüft ob ein Titel bereits existiert oder zu ähnlich ist
     */
    private function is_title_duplicate($title) {
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Prüfe auf Duplikate für: ' . $title);
            error_log('DeepPoster Debug - Bereits generierte Titel: ' . implode(', ', self::$generated_titles));
        }

        // Prüfe zunächst den Cache
        $normalized_new = $this->normalize_title($title);
        foreach (self::$generated_titles as $cached_title) {
            $normalized_cached = $this->normalize_title($cached_title);
            similar_text($normalized_new, $normalized_cached, $percent);
            
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Vergleiche mit Cache:');
                error_log('- Neu: ' . $normalized_new);
                error_log('- Cache: ' . $normalized_cached);
                error_log('- Ähnlichkeit: ' . $percent . '%');
            }
            
            if ($percent > 80) {
                return 'Ähnlicher Titel im Cache gefunden: ' . $cached_title . ' (Ähnlichkeit: ' . round($percent, 2) . '%)';
            }
        }

        // Prüfe dann die Datenbank
        global $wpdb;
        $existing_posts = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT post_title FROM {$wpdb->posts} 
                 WHERE post_type = 'post' 
                 AND post_status IN ('publish', 'draft')
                 AND post_date >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
            )
        );

        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Gefundene existierende Titel: ' . implode(', ', $existing_posts));
        }

        foreach ($existing_posts as $existing_title) {
            $normalized_existing = $this->normalize_title($existing_title);
            similar_text($normalized_new, $normalized_existing, $percent);
            
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Vergleiche mit DB:');
                error_log('- Neu: ' . $normalized_new);
                error_log('- Existierend: ' . $normalized_existing);
                error_log('- Ähnlichkeit: ' . $percent . '%');
            }
            
            if ($percent > 80) {
                return 'Ähnlicher Titel in der Datenbank gefunden: ' . $existing_title . ' (Ähnlichkeit: ' . round($percent, 2) . '%)';
            }
        }

        // Titel ist einzigartig - zum Cache hinzufügen
        self::$generated_titles[] = $title;
        return false;
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
                error_log('DeepPoster Debug - Prompt erstellt');
            }

            // Sende Anfrage an OpenAI
            $response = $this->call_openai_api($prompt);
            
            if (empty($response)) {
                throw new Exception('Keine Antwort von OpenAI erhalten.');
            }

            if (!isset($response['choices']) || empty($response['choices'])) {
                throw new Exception('Ungültige Antwort von OpenAI (keine Choices)');
            }

            if (!isset($response['choices'][0]['message']['content'])) {
                throw new Exception('Ungültige Antwort von OpenAI (kein Content)');
            }

            // Extrahiere Titel und Inhalt aus der Antwort
            $content = $response['choices'][0]['message']['content'];
            list($title, $body, $tags) = $this->parse_content($content);

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

            // Prüfe auf Duplikate BEVOR der Artikel erstellt wird
            $duplicate_error = $this->is_title_duplicate($title);
            if ($duplicate_error) {
                throw new Exception($duplicate_error);
            }

            // Erstelle den WordPress-Artikel
            $post_data = array(
                'post_title'    => html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'post_content'  => html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'post_status'   => $should_publish ? 'publish' : 'draft',
                'post_type'     => 'post',
                'post_category' => array($category->term_id)
            );

            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Erstelle WordPress-Artikel mit Daten:');
                error_log(print_r($post_data, true));
            }

            // Stelle sicher, dass die Schlagwörter UTF-8 kodiert sind
            if (!empty($tags)) {
                $tags = array_map(function($tag) {
                    return html_entity_decode($tag, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }, $tags);
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

            // Füge Schlagwörter hinzu
            if (!empty($tags)) {
                if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                    error_log('DeepPoster Debug - Füge Schlagwörter hinzu: ' . implode(', ', $tags));
                }
                wp_set_post_tags($post_id, $tags);
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
                'viewUrl' => $view_url,
                'tags' => $tags
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
                            "1. Erste Zeile: NUR der Titel des Artikels (ohne Formatierung)\n" .
                            "2. Eine Leerzeile\n" .
                            "3. Der formatierte Artikelinhalt mit HTML-Tags\n" .
                            "4. Eine Leerzeile\n" .
                            "5. 'SCHLAGWORTE:' (genau so geschrieben)\n" .
                            "6. Eine neue Zeile mit 3-5 relevanten, durch Kommas getrennten Schlagwörtern\n\n" .
                            "Beispiel:\n" .
                            "10 Tipps für besseres SEO\n\n" .
                            "<h2>Einleitung</h2>\n" .
                            "<p>In diesem Artikel...</p>\n\n" .
                            "SCHLAGWORTE:\n" .
                            "SEO, WordPress, Optimierung, Content Marketing"
            ),
            array(
                'role' => 'user',
                'content' => "Erstelle einen SEO-optimierten Artikel für die Kategorie '{$category->name}'. Vergiss nicht die Schlagwörter am Ende!"
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
        
        // Füge Formatierungshinweise hinzu
        $prompt_text .= "\n\nWICHTIG - Gebe deine Antwort als JSON-Objekt in folgendem Format zurück:\n" .
                      "{\n" .
                      '  "title": "Der Titel des Artikels (ohne HTML-Tags)",\n' .
                      '  "content": "Der formatierte Artikelinhalt mit HTML-Tags",\n' .
                      '  "tags": ["tag1", "tag2", "tag3", "tag4", "tag5"]\n' .
                      "}\n\n" .
                      "Beachte dabei:\n" .
                      "1. Der Titel sollte SEO-optimiert sein\n" .
                      "2. Der Content muss mit HTML formatiert sein (h2, h3, p, etc.)\n" .
                      "3. Mindestens 3 und maximal 7 relevante Tags\n" .
                      "4. Die Tags müssen im Content vorkommen\n" .
                      "5. Mindestens 2 Tags müssen auch im Titel vorkommen\n" .
                      "6. Keine Markdown-Formatierung verwenden\n\n" .
                      "Beispiel:\n" .
                      "{\n" .
                      '  "title": "Die 10 wichtigsten KI-Trends für digitale Transformation",\n' .
                      '  "content": "<h2>Einleitung</h2><p>Die künstliche Intelligenz revolutioniert...</p>",\n' .
                      '  "tags": ["ki-trends", "digitale transformation", "künstliche intelligenz", "machine learning", "deep learning"]\n' .
                      "}";
        
        return array(
            array(
                'role' => 'system',
                'content' => $prompt_text
            ),
            array(
                'role' => 'user',
                'content' => "Erstelle einen SEO-optimierten Artikel für die Kategorie '{$category->name}'. WICHTIG: Gib die Antwort im spezifizierten JSON-Format zurück!"
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
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Accept' => 'application/json',
                    'Accept-Charset' => 'utf-8'
                ),
                'body' => json_encode($request_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
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

            // Stelle sicher, dass der Content UTF-8 kodiert ist
            if (!empty($body['choices'][0]['message']['content'])) {
                $content = $body['choices'][0]['message']['content'];
                if (!mb_check_encoding($content, 'UTF-8')) {
                    $content = mb_convert_encoding($content, 'UTF-8', mb_detect_encoding($content));
                }
                $body['choices'][0]['message']['content'] = $content;
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
        }

        try {
            // Stelle sicher, dass der Content UTF-8 kodiert ist
            if (!mb_check_encoding($content, 'UTF-8')) {
                $content = mb_convert_encoding($content, 'UTF-8', mb_detect_encoding($content));
            }

            // Versuche JSON zu parsen
            $data = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                    error_log('DeepPoster Debug - JSON Parsing Fehler: ' . json_last_error_msg());
                    error_log('DeepPoster Debug - Versuche Fallback-Parsing...');
                }
                // Fallback zum alten Parsing wenn kein valides JSON
                return $this->parse_content_fallback($content);
            }

            // Validiere JSON Struktur
            if (!isset($data['title']) || !isset($data['content']) || !isset($data['tags'])) {
                throw new Exception('Ungültige JSON-Struktur in der OpenAI-Antwort');
            }

            $title = trim($data['title']);
            $body = trim($data['content']);
            $tags = array_map('trim', $data['tags']);

            // Validiere Daten
            if (empty($title)) {
                throw new Exception('Kein Titel in der JSON-Antwort gefunden');
            }
            if (empty($body)) {
                throw new Exception('Kein Content in der JSON-Antwort gefunden');
            }
            if (empty($tags)) {
                throw new Exception('Keine Tags in der JSON-Antwort gefunden');
            }

            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Erfolgreich geparste JSON-Daten:');
                error_log('Titel: ' . $title);
                error_log('Content Länge: ' . strlen($body));
                error_log('Tags: ' . implode(', ', $tags));
            }

            return array($title, $body, $tags);
        } catch (Exception $e) {
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Fehler beim Parsen des Contents:');
                error_log('Message: ' . $e->getMessage());
                error_log('Stack Trace: ' . $e->getTraceAsString());
            }
            throw $e;
        }
    }

    /**
     * Fallback-Methode für das alte Format
     */
    private function parse_content_fallback($content) {
        // Normalisiere Zeilenumbrüche und entferne BOM
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        
        // Teile den Content in Zeilen
        $lines = array_values(array_filter(explode("\n", $content), function($line) {
            return trim($line) !== '';
        }));
        
        if (empty($lines)) {
            throw new Exception('Keine Textzeilen in der OpenAI-Antwort gefunden.');
        }
        
        // Erste nicht-leere Zeile ist der Titel
        $title = trim($lines[0]);
        
        // Entferne HTML-Tags und Formatierung
        $title = strip_tags($title);
        $title = preg_replace('/`+/', '', $title);
        $title = preg_replace('/^#+\s*/', '', $title);
        $title = preg_replace('/^["\'`]|["\'`]$/', '', $title);
        $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $title = preg_replace('/\s+/', ' ', $title);
        $title = trim($title);
        
        // Suche nach Schlagwörtern
        $tags = [];
        $body_lines = [];
        $found_tags = false;
        
        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            
            if (strtoupper($line) === 'SCHLAGWORTE:' || $line === 'SCHLAGWORTE:') {
                $found_tags = true;
                if (isset($lines[$i + 1])) {
                    $tags_line = trim($lines[$i + 1]);
                    $tags = array_map('trim', explode(',', $tags_line));
                    $tags = array_filter($tags);
                    break;
                }
            } else {
                $body_lines[] = $lines[$i];
            }
        }
        
        if (!$found_tags || empty($tags)) {
            throw new Exception('Keine Schlagwörter im generierten Content gefunden.');
        }
        
        $body = trim(implode("\n", $body_lines));
        
        if (empty($title) || empty($body) || strlen($body) < 100) {
            throw new Exception('Ungültiger Content (Titel oder Body fehlt oder zu kurz)');
        }

        return array($title, $body, $tags);
    }

    /**
     * Normalisiert einen Titel für den Vergleich
     */
    private function normalize_title($title) {
        // Entferne Sonderzeichen und überflüssige Leerzeichen
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', '', $title);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = trim($normalized);
        // Konvertiere zu Kleinbuchstaben
        $normalized = mb_strtolower($normalized, 'UTF-8');
        return $normalized;
    }
} 