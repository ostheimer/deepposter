<?php
/**
 * Handler für AJAX-Anfragen
 */
class DeepPoster_Ajax {
    
    /**
     * Konstruktor
     */
    public function __construct() {
        // Debug-Logging
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - AJAX Handler wird initialisiert');
        }

        // AJAX Actions für eingeloggte Benutzer
        add_action('wp_ajax_deepposter_get_prompts', array($this, 'get_prompts'));
        add_action('wp_ajax_deepposter_get_prompt', array($this, 'get_prompt'));
        add_action('wp_ajax_deepposter_save_prompt', array($this, 'save_prompt'));
        add_action('wp_ajax_deepposter_delete_prompt', array($this, 'delete_prompt'));
        add_action('wp_ajax_deepposter_generate', array($this, 'generate_articles'));
    }

    /**
     * Speichert Debug-Logs
     */
    public function save_logs() {
        check_ajax_referer('deepposter_nonce', 'nonce');

        $logs = isset($_POST['logs']) ? sanitize_text_field($_POST['logs']) : '';
        
        if (empty($logs)) {
            wp_send_json_error('Keine Logs empfangen');
            return;
        }

        // Erstelle Logs-Verzeichnis falls nicht vorhanden
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/deepposter-logs';
        
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }

        // Erstelle Logdatei mit Timestamp
        $timestamp = date('Y-m-d_H-i-s');
        $log_file = $log_dir . '/debug_log_' . $timestamp . '.json';
        
        // Speichere Logs
        $result = file_put_contents($log_file, $logs);
        
        if ($result === false) {
            wp_send_json_error('Fehler beim Speichern der Logs');
            return;
        }

        wp_send_json_success(array(
            'message' => 'Logs erfolgreich gespeichert',
            'file' => basename($log_file)
        ));
    }

    /**
     * Generiert Artikel basierend auf den Formularparametern
     */
    public function generate_articles() {
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - AJAX Handler - Start generate_articles');
            error_log('POST Daten: ' . print_r($_POST, true));
        }

        // Prüfe Nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'deepposter_nonce')) {
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - AJAX Handler - Ungültiges Nonce');
            }
            wp_send_json_error('Sicherheitsüberprüfung fehlgeschlagen.');
            return;
        }

        // Prüfe Berechtigungen
        if (!current_user_can('edit_posts')) {
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - AJAX Handler - Keine Berechtigung');
            }
            wp_send_json_error('Sie haben keine Berechtigung, Artikel zu erstellen.');
            return;
        }

        // Validiere Eingaben
        $category_id = isset($_POST['category']) ? intval($_POST['category']) : 0;
        $count = isset($_POST['count']) ? min(10, max(1, intval($_POST['count']))) : 1;
        $should_publish = isset($_POST['publish']) && $_POST['publish'] === 'true';
        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';

        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - AJAX Handler - Validierte Eingaben:');
            error_log('Kategorie: ' . $category_id);
            error_log('Anzahl: ' . $count);
            error_log('Veröffentlichen: ' . ($should_publish ? 'ja' : 'nein'));
            error_log('Prompt Länge: ' . strlen($prompt));
        }

        // Validiere Kategorie
        if ($category_id <= 0) {
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - AJAX Handler - Keine gültige Kategorie');
            }
            wp_send_json_error('Bitte wählen Sie eine Kategorie aus.');
            return;
        }

        // Validiere Prompt
        if (empty($prompt)) {
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - AJAX Handler - Kein Prompt');
            }
            wp_send_json_error('Bitte geben Sie einen Prompt ein.');
            return;
        }

        try {
            // Hole OpenAI API Key
            $api_key = get_option('deepposter_openai_key');
            if (empty($api_key)) {
                if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                    error_log('DeepPoster Debug - AJAX Handler - Kein API Key');
                }
                wp_send_json_error('Bitte hinterlegen Sie zuerst Ihren OpenAI API Key in den Einstellungen.');
                return;
            }

            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - AJAX Handler - Initialisiere Generator');
            }

            // Initialisiere Generator
            require_once DEEPPOSTER_PLUGIN_DIR . 'includes/class-deepposter-generator.php';
            $generator = new DeepPoster_Generator($api_key);
            
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - AJAX Handler - Generator initialisiert');
                error_log('DeepPoster Debug - AJAX Handler - Starte Artikelgenerierung');
            }

            // Generiere Artikel
            $posts = $generator->generate_posts($category_id, $count, $should_publish, $prompt);
            
            if (empty($posts)) {
                if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                    error_log('DeepPoster Debug - AJAX Handler - Keine Posts generiert');
                }
                wp_send_json_error('Es konnten keine Artikel generiert werden.');
                return;
            }

            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - AJAX Handler - Generierung erfolgreich');
                error_log('Generierte Posts: ' . print_r($posts, true));
            }

            wp_send_json_success($posts);
        } catch (Exception $e) {
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - AJAX Handler - Fehler bei der Generierung:');
                error_log('Message: ' . $e->getMessage());
                error_log('Stack Trace: ' . $e->getTraceAsString());
            }
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Speichert den benutzerdefinierten Prompt als Custom Post Type
     */
    public function save_prompt() {
        try {
            // Debug-Logging
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - save_prompt aufgerufen');
                error_log('POST Daten: ' . print_r($_POST, true));
            }

            // Überprüfe Nonce
            check_ajax_referer('deepposter_nonce', 'nonce');

            // Überprüfe Berechtigungen
            if (!current_user_can('edit_posts')) {
                throw new Exception('Keine Berechtigung zum Speichern von Prompts.');
            }

            // Validiere Eingaben
            $prompt_text = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';
            if (empty($prompt_text)) {
                throw new Exception('Kein Prompt-Text angegeben.');
            }

            // Erstelle den Prompt als Post
            $post_data = array(
                'post_title' => wp_trim_words($prompt_text, 10, '...'),
                'post_content' => $prompt_text,
                'post_status' => 'publish',
                'post_type' => 'deepposter_prompt'
            );

            // Speichere den Post
            $post_id = wp_insert_post($post_data, true);

            if (is_wp_error($post_id)) {
                throw new Exception('Fehler beim Speichern des Prompts: ' . $post_id->get_error_message());
            }

            // Setze diesen Prompt als aktiv
            update_option('deepposter_active_prompt', $post_id);

            wp_send_json_success(array(
                'message' => 'Prompt erfolgreich gespeichert.',
                'prompt' => array(
                    'id' => $post_id,
                    'title' => get_the_title($post_id),
                    'content' => $prompt_text
                )
            ));

        } catch (Exception $e) {
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Fehler in save_prompt: ' . $e->getMessage());
                error_log('DeepPoster Debug - Stack Trace: ' . $e->getTraceAsString());
            }
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Lädt den aktiven Prompt
     */
    public function get_prompt() {
        // Überprüfe Nonce
        if (!check_ajax_referer('deepposter_nonce', 'nonce', false)) {
            wp_send_json_error('Ungültiger Sicherheitstoken.');
            return;
        }

        // Überprüfe Berechtigungen
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Keine Berechtigung.');
            return;
        }

        // Hole den aktiven Prompt
        $active_prompt_id = get_option('deepposter_active_prompt', 0);
        
        if (!$active_prompt_id) {
            wp_send_json_error('Kein aktiver Prompt gefunden.');
            return;
        }

        $prompt = get_post($active_prompt_id);
        
        if (!$prompt || $prompt->post_type !== 'deepposter_prompt') {
            wp_send_json_error('Prompt nicht gefunden.');
            return;
        }

        wp_send_json_success(array(
            'id' => $prompt->ID,
            'title' => $prompt->post_title,
            'content' => $prompt->post_content
        ));
    }

    /**
     * Lädt die Liste aller Prompts
     */
    public function get_prompts() {
        try {
            // Debug-Logging
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - get_prompts aufgerufen');
            }

            // Überprüfe Nonce
            check_ajax_referer('deepposter_nonce', 'nonce');

            // Überprüfe Berechtigungen
            if (!current_user_can('edit_posts')) {
                throw new Exception('Keine Berechtigung zum Laden der Prompts.');
            }

            // Hole alle Prompts
            $args = array(
                'post_type' => 'deepposter_prompt',
                'posts_per_page' => -1,
                'orderby' => 'date',
                'order' => 'DESC'
            );

            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - WP_Query Args: ' . print_r($args, true));
            }

            $prompts = get_posts($args);
            $active_prompt_id = get_option('deepposter_active_prompt', 0);

            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Gefundene Prompts: ' . count($prompts));
                error_log('DeepPoster Debug - Aktiver Prompt: ' . $active_prompt_id);
            }

            // Formatiere die Prompts für die Ausgabe
            $formatted_prompts = array();
            foreach ($prompts as $prompt) {
                $formatted_prompts[] = array(
                    'id' => $prompt->ID,
                    'title' => $prompt->post_title,
                    'content' => $prompt->post_content
                );
            }

            wp_send_json_success(array(
                'prompts' => $formatted_prompts,
                'activePrompt' => intval($active_prompt_id)
            ));

        } catch (Exception $e) {
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Fehler in get_prompts: ' . $e->getMessage());
                error_log('DeepPoster Debug - Stack Trace: ' . $e->getTraceAsString());
            }
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Hole Prompt-Inhalt
     */
    public function get_prompt_content() {
        check_ajax_referer('deepposter_nonce', 'nonce');

        $prompt_id = intval($_POST['prompt_id']);
        if (!$prompt_id) {
            wp_send_json_error('Ungültige Prompt-ID');
        }

        $prompt = get_post($prompt_id);
        if (!$prompt) {
            wp_send_json_error('Prompt nicht gefunden');
        }

        wp_send_json_success($prompt->post_content);
    }

    /**
     * Löscht einen Prompt
     */
    public function delete_prompt() {
        try {
            // Debug-Logging
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - delete_prompt aufgerufen');
                error_log('POST Daten: ' . print_r($_POST, true));
            }

            // Überprüfe Nonce
            check_ajax_referer('deepposter_nonce', 'nonce');

            // Überprüfe Berechtigungen
            if (!current_user_can('delete_posts')) {
                throw new Exception('Keine Berechtigung zum Löschen von Prompts.');
            }

            // Validiere Eingaben
            $prompt_id = isset($_POST['prompt_id']) ? intval($_POST['prompt_id']) : 0;
            if (empty($prompt_id)) {
                throw new Exception('Keine Prompt-ID angegeben.');
            }

            // Überprüfe, ob der Prompt existiert und der richtige Typ ist
            $prompt = get_post($prompt_id);
            if (!$prompt || $prompt->post_type !== 'deepposter_prompt') {
                throw new Exception('Prompt nicht gefunden oder ungültiger Typ.');
            }

            // Prüfe, ob dies der aktive Prompt ist
            $active_prompt_id = get_option('deepposter_active_prompt', 0);
            if ($active_prompt_id == $prompt_id) {
                // Setze den aktiven Prompt zurück
                update_option('deepposter_active_prompt', 0);
            }

            // Lösche den Prompt
            $result = wp_delete_post($prompt_id, true);
            if (!$result) {
                throw new Exception('Fehler beim Löschen des Prompts.');
            }

            wp_send_json_success(array(
                'message' => 'Prompt erfolgreich gelöscht.',
                'deleted_id' => $prompt_id
            ));

        } catch (Exception $e) {
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Fehler in delete_prompt: ' . $e->getMessage());
                error_log('DeepPoster Debug - Stack Trace: ' . $e->getTraceAsString());
            }
            wp_send_json_error($e->getMessage());
        }
    }
} 