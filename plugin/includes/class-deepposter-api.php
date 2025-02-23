<?php
/**
 * API Handler für DeepPoster
 */

class DeepPoster_API {
    private $api_key;
    private $api_provider;
    private $model;

    /**
     * Konstruktor
     */
    public function __construct() {
        $this->api_key = get_option('deepposter_api_key', '');
        $this->api_provider = get_option('deepposter_api_provider', 'openai');
        $this->model = get_option('deepposter_model', 'gpt-3.5-turbo');
    }

    /**
     * Generiere einen Artikel
     */
    public function generate_article($prompt) {
        if (empty($this->api_key)) {
            throw new Exception('API-Schlüssel nicht konfiguriert');
        }

        switch ($this->api_provider) {
            case 'openai':
                return $this->generate_with_openai($prompt);
            default:
                throw new Exception('Nicht unterstützter API-Provider');
        }
    }

    /**
     * Generiere Artikel mit OpenAI
     */
    private function generate_with_openai($prompt) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'model' => $this->model,
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => 'Du bist ein professioneller Content-Ersteller für WordPress-Blogs. ' .
                                   'Erstelle einen gut strukturierten Artikel mit WordPress-kompatiblem HTML. ' .
                                   'Verwende h2 und h3 Tags für die Überschriften. ' .
                                   'Der Artikel sollte informativ, gut recherchiert und SEO-optimiert sein.'
                    ),
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                ),
                'temperature' => 0.7,
                'max_tokens' => 2000
            )),
            'timeout' => 30
        );

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            throw new Exception('API-Fehler: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data['choices'][0]['message']['content'])) {
            throw new Exception('Ungültige API-Antwort');
        }

        return $data['choices'][0]['message']['content'];
    }

    /**
     * Hole verfügbare Modelle
     */
    public function get_available_models() {
        switch ($this->api_provider) {
            case 'openai':
                return array(
                    'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                    'gpt-4' => 'GPT-4 (wenn verfügbar)',
                    'gpt-4-turbo' => 'GPT-4 Turbo (wenn verfügbar)'
                );
            default:
                return array();
        }
    }

    /**
     * Teste API-Verbindung
     */
    public function test_connection() {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => 'API-Schlüssel nicht konfiguriert'
            );
        }

        try {
            $test_prompt = 'Erstelle einen kurzen Testabsatz.';
            $response = $this->generate_article($test_prompt);
            
            return array(
                'success' => true,
                'message' => 'API-Verbindung erfolgreich getestet'
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'API-Fehler: ' . $e->getMessage()
            );
        }
    }
} 