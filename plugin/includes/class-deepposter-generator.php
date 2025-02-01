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
        $this->api_key = $api_key;
        $this->model = get_option('deepposter_model', 'gpt-4');
        $this->max_tokens = get_option('deepposter_max_tokens', 10000);
        $this->temperature = get_option('deepposter_temperature', 0.7);
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
            error_log('API Provider: ' . $this->api_provider);
            error_log('Model: ' . $this->model);
        }

        $category = get_category($category_id);
        if (!$category) {
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Kategorie nicht gefunden: ' . $category_id);
            }
            throw new Exception('Kategorie nicht gefunden.');
        }

        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Kategorie gefunden: ' . $category->name);
        }

        $generated_posts = array();
        for ($i = 0; $i < $count; $i++) {
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Generiere Artikel ' . ($i + 1) . ' von ' . $count);
            }
            try {
                $post = $this->generate_single_post($category, $should_publish, $custom_prompt);
                $generated_posts[] = $post;
            } catch (Exception $e) {
                error_log('DeepPoster Debug - Fehler bei Artikel ' . ($i + 1) . ': ' . $e->getMessage());
                continue;
            }
        }

        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Generierung abgeschlossen. Anzahl Artikel: ' . count($generated_posts));
            error_log('DeepPoster Debug - Generierte Artikel: ' . print_r($generated_posts, true));
        }

        return $generated_posts;
    }

    /**
     * Generiert einen einzelnen Artikel
     */
    private function generate_single_post($category, $should_publish = false, $custom_prompt = '') {
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Starte Einzelartikelgenerierung');
        }

        // Erstelle den Prompt für OpenAI
        $prompt = empty($custom_prompt) ? $this->create_prompt($category) : $this->create_custom_prompt($category, $custom_prompt);
        
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Prompt erstellt: ' . print_r($prompt, true));
        }

        // Sende Anfrage an OpenAI
        $response = $this->call_openai_api($prompt);
        
        if (empty($response)) {
            throw new Exception('Keine Antwort von OpenAI erhalten.');
        }

        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - OpenAI Antwort erhalten: ' . print_r($response, true));
        }

        // Extrahiere Titel und Inhalt aus der Antwort
        $content = $response['choices'][0]['message']['content'];
        list($title, $body) = $this->parse_content($content);

        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Extrahierter Titel: ' . $title);
            error_log('DeepPoster Debug - Extrahierter Body (Länge): ' . strlen($body));
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
            error_log('DeepPoster Debug - Erstelle WordPress-Artikel mit Daten: ' . print_r($post_data, true));
        }

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            $error_message = 'Fehler beim Erstellen des Artikels: ' . $post_id->get_error_message();
            error_log('DeepPoster Debug - ' . $error_message);
            throw new Exception($error_message);
        }

        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Artikel erstellt mit ID: ' . $post_id);
        }

        return array(
            'title' => $title,
            'category' => $category->name,
            'status' => $should_publish ? 'publish' : 'draft',
            'editUrl' => get_edit_post_link($post_id, 'raw'),
            'viewUrl' => get_permalink($post_id)
        );
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
                            "Der Artikel sollte informativ, gut recherchiert und SEO-optimiert sein. " .
                            "Formatiere den Artikel mit WordPress-kompatiblem HTML und " .
                            "strukturiere ihn mit Überschriften (h2, h3). " .
                            "Beginne mit dem Titel in der ersten Zeile, gefolgt von einer Leerzeile " .
                            "und dann dem Artikelinhalt."
            ),
            array(
                'role' => 'user',
                'content' => "Erstelle einen Artikel für die Kategorie '{$category->name}'."
            )
        );
    }

    /**
     * Erstellt einen benutzerdefinierten Prompt für OpenAI
     */
    private function create_custom_prompt($category, $custom_prompt) {
        // Ersetze Platzhalter
        $prompt_text = str_replace('[KATEGORIE]', $category->name, $custom_prompt);
        
        return array(
            array(
                'role' => 'system',
                'content' => $prompt_text
            ),
            array(
                'role' => 'user',
                'content' => "Erstelle einen Artikel für die Kategorie '{$category->name}'."
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
        }

        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'model' => $this->model,
                'messages' => $messages,
                'max_tokens' => $this->max_tokens,
                'temperature' => $this->temperature
            )),
            'timeout' => 60
        );

        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Request Daten:');
            error_log(print_r($args, true));
        }

        $response = wp_remote_post($this->api_url, $args);

        if (is_wp_error($response)) {
            $error_message = 'API-Fehler: ' . $response->get_error_message();
            error_log('DeepPoster Debug - ' . $error_message);
            throw new Exception($error_message);
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            $error_message = 'API-Fehler: Status ' . $status_code;
            error_log('DeepPoster Debug - ' . $error_message);
            error_log('DeepPoster Debug - Response: ' . wp_remote_retrieve_body($response));
            throw new Exception($error_message);
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body) || !isset($body['choices'][0]['message']['content'])) {
            $error_message = 'Ungültige API-Antwort';
            error_log('DeepPoster Debug - ' . $error_message);
            error_log('DeepPoster Debug - Response Body: ' . print_r($body, true));
            throw new Exception($error_message);
        }

        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - API Antwort erfolgreich:');
            error_log(print_r($body, true));
        }

        return $body;
    }

    /**
     * Extrahiert Titel und Inhalt aus der OpenAI-Antwort
     */
    private function parse_content($content) {
        $parts = explode("\n", trim($content), 2);
        
        if (count($parts) !== 2) {
            throw new Exception('Ungültiges Antwortformat von OpenAI');
        }

        return array(
            trim($parts[0]),  // Titel
            trim($parts[1])   // Inhalt
        );
    }
} 