<?php
class AI_Generator {
    
    public static function create_post($category_id, $status) {
        $prompt = self::build_prompt($category_id);
        $content = self::call_openai($prompt);
        
        if (!$content || empty($content['title']) || empty($content['body'])) {
            return new WP_Error('ai_error', 'Fehler bei der Generierung');
        }
        
        return wp_insert_post([
            'post_title'   => sanitize_text_field($content['title']),
            'post_content' => wp_kses_post($content['body']),
            'post_status'  => $status,
            'post_category' => [$category_id],
            'post_author'  => get_current_user_id()
        ]);
    }

    private static function build_prompt($category_id) {
        $category = get_category($category_id);
        $context = self::get_category_examples($category_id);
        $examples = self::get_example_titles($category_id);

        return <<<PROMPT
Erstelle einen professionellen Blogartikel in der Kategorie "{$category->name}".

**Kontext der Top-Beiträge:**
{$context}

**Beispieltitel:**
- {$examples}

**Anforderungen:**
- Länge: 1200-1500 Wörter
- Struktur: Einleitung, 3-5 H2-Abschnitte, Fazit
- Schreibstil: Informativ aber locker
- SEO: Primärkeyword im ersten Absatz

**Format:**
##TITLE##
[Titel hier]

##CONTENT##
[Inhalt in Markdown]
PROMPT;
    }

    private static function get_category_examples($category_id) {
        $cache_key = 'ai_analysis_' . $category_id;
        
        if ($cached = get_transient($cache_key)) {
            return $cached;
        }
        
        $posts = get_posts([
            'category' => $category_id,
            'posts_per_page' => 3,
            'meta_key' => 'post_views_count',
            'orderby' => 'meta_value_num',
            'order' => 'DESC'
        ]);

        if (empty($posts)) return 'Keine existierenden Beiträge';

        $corpus = array_map(function($post) {
            return self::clean_content($post->post_content);
        }, $posts);

        $tfidf = new \TfIdf\TfIdf();
        foreach ($corpus as $doc) {
            $tfidf->addDocument($doc);
        }
        
        $analysis = [
            'keywords' => implode(', ', $tfidf->getImportantTerms(5)),
            'avg_readability' => number_format(array_sum(array_map([self::class, 'calculate_flesch_score'], $corpus)) / count($corpus), 1),
            'media_count' => array_sum(array_map(function($post) {
                return count(get_attached_media('image', $post->ID));
            }, $posts))
        ];

        $output = "Top-Keywords: {$analysis['keywords']}\n"
                . "Durchschn. Lesbarkeit: {$analysis['avg_readability']}/100\n"
                . "Durchschn. Bilder pro Beitrag: " . round($analysis['media_count']/3);

        set_transient($cache_key, $output, HOUR_IN_SECONDS * 6);
        return $output;
    }

    private static function call_openai($prompt) {
        $api_key = get_option('openai_api_key');
        if (!$api_key) return false;

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30,
            'body' => json_encode([
                'model' => 'gpt-4',
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'temperature' => 0.7,
                'max_tokens' => 2000
            ])
        ]);

        if (is_wp_error($response)) {
            error_log('OpenAI API Error: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return self::parse_response($body['choices'][0]['message']['content']);
    }

    private static function parse_response($content) {
        preg_match('/##TITLE##(.*?)##CONTENT##(.*)/s', $content, $matches);
        return [
            'title' => isset($matches[1]) ? trim($matches[1]) : 'Automatisch generierter Beitrag',
            'body' => isset($matches[2]) ? trim($matches[2]) : 'Inhalt konnte nicht generiert werden.'
        ];
    }

    private static function clean_content($content) {
        return mb_substr(wp_strip_all_tags(preg_replace('/\[.*?\]/', '', $content)), 0, 5000);
    }

    private static function calculate_flesch_score($text) {
        $total_sentences = preg_match_all('/[.!?]+/', $text);
        $total_words = str_word_count($text);
        $total_syllables = self::count_syllables($text);

        if ($total_sentences > 0 && $total_words > 0) {
            return 206.835 - 1.015 * ($total_words/$total_sentences) 
                 - 84.6 * ($total_syllables/$total_words);
        }
        return 0;
    }

    private static function count_syllables($text) {
        $text = mb_strtolower($text);
        $count = 0;
        $vowels = ['a', 'e', 'i', 'o', 'u', 'y', 'ä', 'ö', 'ü'];
        $words = preg_split('/\s+/', $text);
        
        foreach ($words as $word) {
            $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);
            $prev_vowel = false;
            
            foreach ($chars as $char) {
                if (in_array($char, $vowels)) {
                    if (!$prev_vowel) $count++;
                    $prev_vowel = true;
                } else {
                    $prev_vowel = false;
                }
            }
            
            if (substr($word, -1) == 'e') $count--;
            if ($count == 0) $count = 1;
        }
        
        return $count;
    }
}
