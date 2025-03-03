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
        add_action('wp_ajax_deepposter_get_prompt_content', array($this, 'get_prompt_content'));
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
     * Speichert einen Prompt
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
            $prompt_text = isset($_POST['prompt_text']) ? sanitize_textarea_field($_POST['prompt_text']) : '';
            $prompt_title = isset($_POST['prompt_title']) ? sanitize_text_field($_POST['prompt_title']) : '';
            $prompt_id = isset($_POST['prompt_id']) ? intval($_POST['prompt_id']) : 0;

            if (empty($prompt_text)) {
                throw new Exception('Kein Prompt-Text angegeben.');
            }

            if (empty($prompt_title)) {
                throw new Exception('Kein Prompt-Titel angegeben.');
            }

            // Erstelle oder aktualisiere den Post
            $post_data = array(
                'post_title'    => $prompt_title,
                'post_content'  => $prompt_text,
                'post_status'   => 'publish',
                'post_type'     => 'deepposter_prompt'
            );

            // Wenn eine ID vorhanden ist, aktualisiere den bestehenden Post
            if ($prompt_id > 0) {
                $post_data['ID'] = $prompt_id;
                $post = get_post($prompt_id);
                
                // Prüfe ob der Post existiert und vom richtigen Typ ist
                if (!$post || $post->post_type !== 'deepposter_prompt') {
                    throw new Exception('Prompt nicht gefunden oder ungültiger Typ.');
                }
            }

            // Speichere den Post
            $post_id = wp_insert_post($post_data);

            if (is_wp_error($post_id)) {
                throw new Exception($post_id->get_error_message());
            }

            // Sende Erfolgsantwort
            wp_send_json_success(array(
                'message' => $prompt_id > 0 ? 'Prompt erfolgreich aktualisiert' : 'Prompt erfolgreich gespeichert',
                'id' => $post_id,
                'title' => $prompt_title,
                'text' => $prompt_text
            ));

        } catch (Exception $e) {
            error_log('DeepPoster Error - Fehler beim Speichern des Prompts: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
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
     * Lädt alle gespeicherten Prompts
     */
    public function get_prompts() {
        // Debug-Logging
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - AJAX Handler - Start get_prompts');
            error_log('POST Daten: ' . print_r($_POST, true));
        }

        // Prüfe Nonce
        check_ajax_referer('deepposter_nonce', 'nonce');

        // Prüfe Berechtigungen
        if (!current_user_can('read')) {
            wp_send_json_error('Keine Berechtigung.');
            return;
        }

        // Hole alle Prompts aus der Datenbank
        $args = array(
            'post_type' => 'deepposter_prompt',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        );

        $prompts = get_posts($args);
        $result = array();

        foreach ($prompts as $prompt) {
            $result[$prompt->ID] = array(
                'id' => $prompt->ID,
                'post_id' => $prompt->ID,
                'title' => $prompt->post_title,
                'text' => $prompt->post_content
            );
        }

        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - AJAX Handler - Gefundene Prompts: ' . count($result));
            error_log('DeepPoster Debug - AJAX Handler - Prompts: ' . print_r($result, true));
        }

        wp_send_json_success(array('prompts' => $result));
    }
    
    /**
     * Lädt Prompts direkt aus der Datenbank
     * 
     * @return array Array mit Prompts
     */
    private function load_prompts_from_database() {
        global $wpdb;
        $prompts = array();
        
        try {
            error_log('DeepPoster Debug - Lade Prompts direkt aus der Datenbank');
            
            // Hole alle Posts vom Typ deepposter_prompt
            $query = $wpdb->prepare(
                "SELECT ID, post_title, post_content, post_date 
                 FROM {$wpdb->posts} 
                 WHERE post_type = %s 
                 AND post_status = 'publish'",
                'deepposter_prompt'
            );
            
            $results = $wpdb->get_results($query);
            error_log('DeepPoster Debug - DB-Abfrage ausgeführt: ' . $query);
            error_log('DeepPoster Debug - Gefundene Datensätze: ' . count($results));
            
            if ($results) {
                foreach ($results as $post) {
                    $prompt_id = 'db_prompt_' . $post->ID;
                    
                    error_log('DeepPoster Debug - Verarbeite DB-Post: ID=' . $post->ID . ', Titel=' . $post->post_title);
                    
                    $prompts[$prompt_id] = array(
                        'title' => $post->post_title,
                        'text' => $post->post_content,
                        'date_created' => $post->post_date,
                        'post_id' => $post->ID
                    );
                }
            }
            
            return $prompts;
        } catch (Exception $e) {
            error_log('DeepPoster Debug - Fehler beim direkten Laden aus der Datenbank: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * Hole Prompt-Inhalt für die Vorschau
     */
    public function get_prompt_content() {
        // Debug-Logging
        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - get_prompt_content aufgerufen');
            error_log('POST Daten: ' . print_r($_POST, true));
        }

        // Prüfe Nonce
        check_ajax_referer('deepposter_nonce', 'nonce');

        // Prüfe Berechtigungen
        if (!current_user_can('read')) {
            wp_send_json_error('Keine Berechtigung zum Lesen von Prompts.');
            return;
        }

        // Validiere Prompt-ID
        $prompt_id = isset($_POST['prompt_id']) ? intval($_POST['prompt_id']) : 0;
        if (!$prompt_id) {
            wp_send_json_error('Keine gültige Prompt-ID angegeben.');
            return;
        }

        // Hole den Prompt
        $prompt = get_post($prompt_id);
        if (!$prompt || $prompt->post_type !== 'deepposter_prompt') {
            wp_send_json_error('Prompt nicht gefunden.');
            return;
        }

        if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
            error_log('DeepPoster Debug - Prompt gefunden: ' . print_r($prompt, true));
        }

        // Sende den Prompt-Inhalt zurück
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

    /**
     * Repariert duplizierte IDs in der Datenbank
     * 
     * @since 1.0.0
     */
    /* Methode entfernt: repair_duplicate_ids */
} 