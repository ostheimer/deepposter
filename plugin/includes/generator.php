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
    check_ajax_referer('deepposter_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Keine Berechtigung');
    }

    $category = intval($_POST['category']);
    $count = min(5, max(1, intval($_POST['count'])));
    $publish = !empty($_POST['publish']);

    $generator = new DeepPosterGenerator();
    $posts = $generator->generate_posts($category, $count, $publish);

    if (is_wp_error($posts)) {
        wp_send_json_error($posts->get_error_message());
    }

    wp_send_json_success($posts);
}); 