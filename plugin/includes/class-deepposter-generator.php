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
    public function generate_posts($category_id, $count, $should_publish) {
        $category = get_category($category_id);
        if (!$category) {
            throw new Exception('Kategorie nicht gefunden.');
        }

        $generated_posts = array();
        for ($i = 0; $i < $count; $i++) {
            $post = $this->generate_single_post($category, $should_publish);
            $generated_posts[] = $post;
        }

        return $generated_posts;
    }

    /**
     * Generiert einen einzelnen Artikel
     */
    private function generate_single_post($category, $should_publish = false) {
        // Erstelle den Prompt für OpenAI
        $prompt = $this->create_prompt($category);
        
        // Sende Anfrage an OpenAI
        $response = $this->call_openai_api($prompt);
        
        if (empty($response)) {
            throw new Exception('Keine Antwort von OpenAI erhalten.');
        }

        // Extrahiere Titel und Inhalt aus der Antwort
        $content = $response['choices'][0]['message']['content'];
        list($title, $body) = $this->parse_content($content);

        // Erstelle den WordPress-Artikel
        $post_data = array(
            'post_title'    => $title,
            'post_content'  => $body,
            'post_status'   => $should_publish ? 'publish' : 'draft',
            'post_type'     => 'post',
            'post_category' => array($category->term_id)
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            throw new Exception('Fehler beim Erstellen des Artikels: ' . $post_id->get_error_message());
        }

        return array(
            'id' => $post_id,
            'title' => $title,
            'excerpt' => wp_trim_words($body, 20),
            'status' => $should_publish ? 'publish' : 'draft',
            'edit_url' => get_edit_post_link($post_id, 'raw')
        );
    }

    /**
     * Erstellt den Prompt für OpenAI
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
     * Sendet die Anfrage an die OpenAI API
     */
    private function call_openai_api($messages) {
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

        $response = wp_remote_post($this->api_url, $args);

        if (is_wp_error($response)) {
            throw new Exception('API-Fehler: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data) || !isset($data['choices'][0]['message']['content'])) {
            throw new Exception('Ungültige Antwort von OpenAI');
        }

        return $data;
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