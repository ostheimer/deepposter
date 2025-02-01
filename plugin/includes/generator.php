<?php
defined('ABSPATH') || exit;

class DeepPosterGenerator {
    private $api_provider;
    private $api_key;
    private $model;
    private $max_tokens;

    public function __construct() {
        $this->api_provider = get_option('deepposter_api_provider', 'openai');
        $this->api_key = get_option('deepposter_' . $this->api_provider . '_key');
        $this->model = get_option('deepposter_model', 'gpt-4');
        $this->max_tokens = get_option('deepposter_max_tokens', 2000);
    }

    public function generate_posts($category_id, $count = 1, $publish = false) {
        $category = get_category($category_id);
        if (!$category) {
            return new WP_Error('invalid_category', 'Ungültige Kategorie');
        }

        $posts = [];
        for ($i = 0; $i < $count; $i++) {
            $content = $this->generate_content($category->name);
            if (is_wp_error($content)) {
                continue;
            }

            $post_data = [
                'post_title' => $content['title'],
                'post_content' => $content['content'],
                'post_status' => $publish ? 'publish' : 'draft',
                'post_category' => [$category_id]
            ];

            $post_id = wp_insert_post($post_data);
            if (!is_wp_error($post_id)) {
                $posts[] = [
                    'id' => $post_id,
                    'title' => $content['title'],
                    'category' => $category->name,
                    'status' => $publish ? 'Veröffentlicht' : 'Entwurf',
                    'editUrl' => get_edit_post_link($post_id, 'raw'),
                    'viewUrl' => get_permalink($post_id)
                ];
            }
        }

        return $posts;
    }

    private function generate_content($category) {
        $prompt = $this->build_prompt($category);
        
        switch ($this->api_provider) {
            case 'openai':
                return $this->generate_with_openai($prompt);
            case 'deepseek':
                return $this->generate_with_deepseek($prompt);
            default:
                return new WP_Error('invalid_provider', 'Ungültiger API Provider');
        }
    }

    private function build_prompt($category) {
        return "Generiere einen Blog-Artikel für die Kategorie '{$category}'. " .
               "Der Artikel sollte informativ, gut strukturiert und SEO-optimiert sein. " .
               "Formatiere den Output als JSON mit den Feldern 'title' und 'content'. " .
               "Der Content sollte HTML-Formatierung für bessere Lesbarkeit enthalten.";
    }

    private function generate_with_openai($prompt) {
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => $this->max_tokens,
                'temperature' => 0.7
            ])
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['choices'][0]['message']['content'])) {
            return new WP_Error('api_error', 'Keine Antwort von OpenAI');
        }

        return json_decode($body['choices'][0]['message']['content'], true);
    }

    private function generate_with_deepseek($prompt) {
        $response = wp_remote_post('https://api.deepseek.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => $this->max_tokens,
                'temperature' => 0.7
            ])
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['choices'][0]['message']['content'])) {
            return new WP_Error('api_error', 'Keine Antwort von DeepSeek');
        }

        return json_decode($body['choices'][0]['message']['content'], true);
    }
}

// AJAX Handler für die Content-Generierung
add_action('wp_ajax_deepposter_generate', function() {
    if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
        error_log('DeepPoster Debug - AJAX Handler gestartet');
        error_log('POST Daten: ' . print_r($_POST, true));
    }

    check_ajax_referer('deepposter_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Keine Berechtigung');
        }
        wp_send_json_error('Keine Berechtigung');
        return;
    }

    $category = intval($_POST['category']);
    $count = min(5, max(1, intval($_POST['count'])));
    $publish = !empty($_POST['publish']);
    $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';

    if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
        error_log('DeepPoster Debug - Verarbeitete Eingaben:');
        error_log('Kategorie: ' . $category);
        error_log('Anzahl: ' . $count);
        error_log('Veröffentlichen: ' . ($publish ? 'ja' : 'nein'));
        error_log('Prompt Länge: ' . strlen($prompt));
    }

    if (empty($category)) {
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Keine Kategorie ausgewählt');
        }
        wp_send_json_error('Bitte wählen Sie eine Kategorie aus.');
        return;
    }

    if (empty($prompt)) {
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Kein Prompt angegeben');
        }
        wp_send_json_error('Bitte geben Sie ein Prompt ein.');
        return;
    }

    $api_key = get_option('deepposter_openai_key');
    if (empty($api_key)) {
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Kein API Key konfiguriert');
        }
        wp_send_json_error('Bitte hinterlegen Sie zuerst Ihren OpenAI API Key in den Einstellungen.');
        return;
    }

    try {
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Starte Generator');
        }

        $generator = new DeepPosterGenerator();
        $posts = $generator->generate_posts($category, $count, $publish, $prompt);

        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Generierung erfolgreich:');
            error_log(print_r($posts, true));
        }

        wp_send_json_success($posts);
    } catch (Exception $e) {
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Fehler bei der Generierung: ' . $e->getMessage());
            error_log('Stack Trace: ' . $e->getTraceAsString());
        }
        wp_send_json_error($e->getMessage());
    }
});

// AJAX Handler für das Laden der OpenAI Modelle
add_action('wp_ajax_deepposter_get_models', function() {
    check_ajax_referer('deepposter_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Keine Berechtigung');
    }

    $api_key = get_option('deepposter_openai_key');
    if (!$api_key) {
        wp_send_json_error('Kein OpenAI API Key konfiguriert');
    }

    if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
        error_log('DeepPoster Debug - Starte API-Anfrage an OpenAI');
    }

    $response = wp_remote_get('https://api.openai.com/v1/models', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ],
        'timeout' => 15
    ]);

    if (is_wp_error($response)) {
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - API-Fehler: ' . $response->get_error_message());
        }
        wp_send_json_error('API-Fehler: ' . $response->get_error_message());
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Ungültiger Status Code: ' . $status_code);
        }
        wp_send_json_error('API-Fehler: Status ' . $status_code);
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($body['data']) || !is_array($body['data'])) {
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Ungültige API-Antwort: ' . print_r($body, true));
        }
        wp_send_json_error('Ungültige API-Antwort');
    }

    // Filtere nur die Chat-Modelle
    $chat_models = array_filter($body['data'], function($model) {
        return strpos($model['id'], 'gpt') !== false;
    });

    if (empty($chat_models)) {
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Keine GPT-Modelle gefunden');
        }
        wp_send_json_success([]); // Leeres Array, aber success=true
    }

    // Formatiere die Modelle für die Anzeige
    $formatted_models = array_map(function($model) {
        return [
            'id' => $model['id'],
            'name' => ucwords(str_replace('-', ' ', $model['id']))
        ];
    }, $chat_models);

    if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
        error_log('DeepPoster Debug - Gefundene Modelle: ' . print_r($formatted_models, true));
    }

    wp_send_json_success(array_values($formatted_models)); // Stellt sicher, dass es ein Array ist
}); 