#!/bin/bash

# create_ai_blog_plugin.sh

# Projektstruktur erstellen
mkdir -p ai-blog-generator/{docker,plugin/includes,plugin/assets,plugin/templates,docs}

# 1. Docker-Compose Datei
cat << 'EOF' > ai-blog-generator/docker/docker-compose.yml
version: '3.8'

services:
  wordpress:
    image: wordpress:php8.2
    ports:
      - "8000:80"
    volumes:
      - ./../plugin:/var/www/html/wp-content/plugins/ai-blog-generator
      - wordpress_data:/var/www/html
    environment:
      WORDPRESS_DB_HOST: db
      WORDPRESS_DB_USER: wpuser
      WORDPRESS_DB_PASSWORD: wppass
      WORDPRESS_DB_NAME: wpdb
      WORDPRESS_DEBUG: 1
    depends_on:
      - db

  db:
    image: mysql:5.7
    volumes:
      - db_data:/var/lib/mysql
    environment:
      MYSQL_ROOT_PASSWORD: rootpass
      MYSQL_DATABASE: wpdb
      MYSQL_USER: wpuser
      MYSQL_PASSWORD: wppass

volumes:
  wordpress_data:
  db_data:
EOF

# 2. Haupt-Plugin-Datei
cat << 'EOF' > ai-blog-generator/plugin/ai-blog-generator.php
<?php
/**
 * Plugin Name: AI Blog Generator
 * Description: Generiert kategoriebasierte Blogbeiträge mit OpenAI
 * Version: 1.0
 * Author: Dein Name
 */

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . 'includes/settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/generator.php';

class AI_Blog_Generator {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_generate_articles', [$this, 'handle_generation']);
    }

    public function add_admin_page() {
        add_menu_page(
            'AI Blog Generator',
            'AI Generator',
            'manage_options',
            'ai-blog-generator',
            [$this, 'render_admin_ui'],
            'dashicons-edit-page'
        );
    }

    public function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_ai-blog-generator') return;
        
        wp_enqueue_style(
            'ai-generator-css', 
            plugins_url('assets/admin.css', __FILE__)
        );
        
        wp_enqueue_script(
            'ai-generator-js',
            plugins_url('assets/admin.js', __FILE__),
            ['jquery'],
            filemtime(plugin_dir_path(__FILE__) . 'assets/admin.js'),
            true
        );
        
        wp_localize_script('ai-generator-js', 'aiGenerator', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_blog_generator')
        ]);
    }

    public function render_admin_ui() {
        include plugin_dir_path(__FILE__) . 'templates/admin-form.php';
    }

    public function handle_generation() {
        check_ajax_referer('ai_blog_generator', 'nonce');
        
        if (!current_user_can('publish_posts')) {
            wp_send_json_error('Unzureichende Berechtigungen');
        }
        
        $category = absint($_POST['category']);
        $count = min(absint($_POST['count']), 5);
        $publish = $_POST['publish'] === 'true' ? 'publish' : 'draft';
        
        if (get_transient('ai_cooldown_' . get_current_user_id())) {
            wp_send_json_error('Bitte warten Sie 2 Minuten zwischen Generierungen');
        }
        set_transient('ai_cooldown_' . get_current_user_id(), 1, 120);
        
        $results = [];
        for ($i = 0; $i < $count; $i++) {
            $result = AI_Generator::create_post($category, $publish);
            if ($result && !is_wp_error($result)) {
                $results[] = get_post($result);
            }
        }
        
        wp_send_json_success($results);
    }
}

new AI_Blog_Generator();
EOF

# 3. Generator-Klasse
cat << 'EOF' > ai-blog-generator/plugin/includes/generator.php
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
EOF

# 4. Einstellungen
cat << 'EOF' > ai-blog-generator/plugin/includes/settings.php
<?php
add_action('admin_init', function() {
    register_setting('ai_blog_generator_settings', 'openai_api_key', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field'
    ]);

    add_settings_section(
        'ai_blog_generator_api',
        'API-Einstellungen',
        function() {
            echo '<p>API-Zugangsdaten konfigurieren</p>';
        },
        'ai-blog-generator'
    );

    add_settings_field(
        'openai_api_key_field',
        'OpenAI API Key',
        function() {
            $value = esc_attr(get_option('openai_api_key'));
            echo '<input type="password" name="openai_api_key" value="'.$value.'" class="regular-text">';
            echo '<p class="description">Erhalten Sie Ihren Key von <a href="https://platform.openai.com/" target="_blank">platform.openai.com</a></p>';
        },
        'ai-blog-generator',
        'ai_blog_generator_api'
    );
});

add_action('admin_menu', function() {
    add_submenu_page(
        'ai-blog-generator',
        'Einstellungen',
        'Einstellungen',
        'manage_options',
        'ai-blog-generator-settings',
        function() {
            ?>
            <div class="wrap">
                <h1>AI Blog Generator Einstellungen</h1>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('ai_blog_generator_settings');
                    do_settings_sections('ai-blog-generator');
                    submit_button();
                    ?>
                </form>
            </div>
            <?php
        }
    );
});
EOF

# 5. Admin-Template
cat << 'EOF' > ai-blog-generator/plugin/templates/admin-form.php
<div class="wrap ai-generator">
    <h1>AI Blog Generator</h1>
    
    <form id="aiGeneratorForm">
        <div class="form-group">
            <label for="categorySelect">Kategorie auswählen:</label>
            <?php wp_dropdown_categories([
                'id' => 'categorySelect',
                'name' => 'category',
                'hide_empty' => 0,
                'hierarchical' => true,
                'orderby' => 'name',
                'show_option_none' => 'Kategorie wählen'
            ]); ?>
        </div>
        
        <div class="form-group">
            <label for="articleCount">Anzahl Artikel:</label>
            <select id="articleCount" name="count" class="regular-text">
                <?php foreach (range(1, 5) as $num): ?>
                    <option value="<?= $num ?>"><?= $num ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" id="publishImmediately" name="publish">
                Sofort veröffentlichen
            </label>
        </div>
        
        <?php submit_button('Artikel generieren'); ?>
    </form>
    
    <div id="generationResults"></div>
</div>
EOF

# 6. CSS
cat << 'EOF' > ai-blog-generator/plugin/assets/admin.css
.ai-generator {
    max-width: 1000px;
    padding: 20px;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
}

#generationResults {
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}

.generated-articles {
    display: grid;
    gap: 20px;
}

.article {
    padding: 15px;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    background: #fff;
}

.loading {
    text-align: center;
    padding: 30px;
    background: #f8f9fa;
    border-radius: 4px;
}

.spinner {
    margin: 0 auto 15px;
}

.post-actions {
    margin-top: 10px;
    display: flex;
    gap: 8px;
}
EOF

# 7. JavaScript
cat << 'EOF' > ai-blog-generator/plugin/assets/admin.js
jQuery(document).ready(function($) {
    const form = $('#aiGeneratorForm');
    const results = $('#generationResults');

    form.on('submit', function(e) {
        e.preventDefault();
        
        results.html(`
            <div class="loading">
                <div class="spinner is-active"></div>
                <p>Generiere ${$('#articleCount').val()} Artikel...</p>
            </div>
        `);

        $.ajax({
            url: aiGenerator.ajaxurl,
            method: 'POST',
            data: {
                action: 'generate_articles',
                nonce: aiGenerator.nonce,
                category: $('#categorySelect').val(),
                count: $('#articleCount').val(),
                publish: $('#publishImmediately').is(':checked')
            },
            success: function(response) {
                if (response.success) {
                    let html = '<div class="generated-articles">';
                    response.data.forEach(post => {
                        html += `
                            <div class="article">
                                <h3>${post.post_title}</h3>
                                <div class="post-actions">
                                    <a href="/wp-admin/post.php?post=${post.ID}&action=edit" 
                                       class="button" 
                                       target="_blank">
                                       Bearbeiten
                                    </a>
                                    <a href="${post.guid}" 
                                       class="button" 
                                       target="_blank">
                                       Ansehen
                                    </a>
                                </div>
                            </div>`;
                    });
                    results.html(html);
                }
            },
            error: function(xhr) {
                results.html(`
                    <div class="error notice">
                        <p>Fehler: ${xhr.responseJSON?.data || 'Unbekannter Fehler'}</p>
                    </div>
                `);
            }
        });
    });
});
EOF

# 8. Composer-Konfig
cat << 'EOF' > ai-blog-generator/plugin/composer.json
{
    "name": "ai-blog-generator",
    "require": {
        "davidgorges/php-tf-idf": "^2.0"
    },
    "config": {
        "vendor-dir": "includes/vendor",
        "platform": {
            "php": "7.4"
        }
    }
}
EOF

# 9. Git Ignore
cat << 'EOF' > ai-blog-generator/.gitignore
*.log
.env
wp-config.php
docker/.data
plugin/includes/vendor
.DS_Store
EOF

# Berechtigungen setzen
chmod +