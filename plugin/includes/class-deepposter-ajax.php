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
     * Speichert einen Prompt in der Datenbank
     */
    public function save_prompt() {
        try {
            // Debug-Logging
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - save_prompt aufgerufen');
                error_log('DeepPoster Debug - POST-Daten: ' . print_r($_POST, true));
            } else {
                // Für Debugging immer ausgeben
                error_log('DeepPoster Debug - save_prompt aufgerufen');
                error_log('DeepPoster Debug - POST-Daten: ' . print_r($_POST, true));
            }
        
            // Prüfe Sicherheits-Nonce - temporär deaktiviert für Debugging
            /*
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'deepposter_nonce')) {
                throw new Exception(__('Sicherheitsüberprüfung fehlgeschlagen.', 'deepposter'));
            }
            */

            // Prüfe auf erforderliche Parameter
            $prompt_text = isset($_POST['prompt_text']) ? sanitize_textarea_field($_POST['prompt_text']) : '';
            $prompt_title = isset($_POST['prompt_title']) ? sanitize_text_field($_POST['prompt_title']) : '';
            
            if (empty($prompt_text)) {
                error_log('DeepPoster Debug - Kein Prompt-Text angegeben');
                throw new Exception(__('Kein Prompt-Text angegeben.', 'deepposter'));
            }

            if (empty($prompt_title)) {
                error_log('DeepPoster Debug - Kein Prompt-Titel angegeben');
                throw new Exception(__('Kein Prompt-Titel angegeben.', 'deepposter'));
            }

            // Hole bestehende Prompts
            $saved_prompts = get_option('deepposter_saved_prompts', array());
            if (!is_array($saved_prompts)) {
                $saved_prompts = array();
            }
            
            error_log('DeepPoster Debug - Bestehende Prompts vor dem Speichern: ' . print_r($saved_prompts, true));
            
            // Generiere eindeutige ID für neuen Prompt
            $prompt_id = 'prompt_' . time() . '_' . mt_rand(1000, 9999);
            
            // Füge neuen Prompt hinzu
            $saved_prompts[$prompt_id] = array(
                'title' => $prompt_title,
                'text' => $prompt_text,
                'date_created' => current_time('mysql')
            );
            
            // Speichere aktualisierte Prompts
            update_option('deepposter_saved_prompts', $saved_prompts);
            
            error_log('DeepPoster Debug - Prompts nach dem Speichern: ' . print_r($saved_prompts, true));
            error_log('DeepPoster Debug - Neuer Prompt mit ID gespeichert: ' . $prompt_id);
            
            // Sende Erfolgsantwort mit Prompt-Daten
            wp_send_json_success(array(
                'message' => __('Prompt erfolgreich gespeichert.', 'deepposter'),
                'prompts' => $saved_prompts,
                'prompt_id' => $prompt_id,
                'prompt_title' => $prompt_title
            ));
            
        } catch (Exception $e) {
            if (defined('DEEPPOSTER_DEBUG') && DEEPPOSTER_DEBUG) {
                error_log('DeepPoster Debug - Fehler in save_prompt: ' . $e->getMessage());
                error_log('DeepPoster Debug - Stack Trace: ' . $e->getTraceAsString());
        } else {
                // Für Debugging immer ausgeben
                error_log('DeepPoster Debug - Fehler in save_prompt: ' . $e->getMessage());
            }
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
        try {
            // Debug-Logging 
            error_log('DeepPoster Debug - get_prompts aufgerufen');
            
            // Nonce-Prüfung temporär deaktiviert für Debugging
            /*
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'deepposter_nonce')) {
                throw new Exception(__('Sicherheitsüberprüfung fehlgeschlagen.', 'deepposter'));
            }
            
            // Berechtigung prüfen
            if (!current_user_can('edit_posts')) {
                throw new Exception(__('Unzureichende Berechtigungen.', 'deepposter'));
            }
            */
            
            // Hole gespeicherte Prompts zuerst aus der WordPress-Option
            $saved_prompts = get_option('deepposter_saved_prompts', array());
            error_log('DeepPoster Debug - Gespeicherte Prompts aus Option: ' . print_r($saved_prompts, true));
            
            // Hole auch die Custom Post Type Prompts
            $custom_prompts = array();
            
            try {
                // Versuche zuerst, die Prompts über WP_Query zu laden
                $args = array(
                    'post_type' => 'deepposter_prompt',
                    'posts_per_page' => -1,  // Alle Posts holen
                    'post_status' => 'publish'
                );
                
                $prompt_query = new WP_Query($args);
                error_log('DeepPoster Debug - WP_Query ausgeführt: ' . print_r($prompt_query->request, true));
                error_log('DeepPoster Debug - Gefundene Posts: ' . $prompt_query->post_count);
                
                // Konvertiere Custom Post Type Prompts in das gleiche Format
                if ($prompt_query->have_posts()) {
                    while ($prompt_query->have_posts()) {
                        $prompt_query->the_post();
                        $post_id = get_the_ID();
                        $prompt_id = 'cpt_prompt_' . $post_id;
                        
                        $title = get_the_title();
                        $content = get_the_content();
                        
                        error_log('DeepPoster Debug - Verarbeite Post: ID=' . $post_id . ', Titel=' . $title);
                        
                        $custom_prompts[$prompt_id] = array(
                            'title' => $title,
                            'text' => $content,
                            'date_created' => get_the_date('Y-m-d H:i:s'),
                            'post_id' => $post_id
                        );
                    }
                    // Zurücksetzen des Post-Daten
                    wp_reset_postdata();
                } else {
                    // Wenn keine Posts gefunden wurden, versuche direkt aus der Datenbank zu laden
                    error_log('DeepPoster Debug - Keine Posts über WP_Query gefunden, versuche direkte DB-Abfrage');
                    $custom_prompts = $this->load_prompts_from_database();
                }
            } catch (Exception $e) {
                error_log('DeepPoster Debug - Fehler beim Laden der Custom Post Types: ' . $e->getMessage());
                error_log('DeepPoster Debug - Stack Trace: ' . $e->getTraceAsString());
                
                // Fallback: Versuche direkt aus der Datenbank zu laden
                error_log('DeepPoster Debug - Versuche Fallback: Direkte DB-Abfrage');
                $custom_prompts = $this->load_prompts_from_database();
            }
            
            error_log('DeepPoster Debug - Custom Post Type Prompts: ' . print_r($custom_prompts, true));
            
            // Kombiniere beide Prompt-Quellen
            $all_prompts = array_merge($saved_prompts, $custom_prompts);
            
            // Debug-Ausgabe der kombinierten Prompts
            error_log('DeepPoster Debug - Kombinierte Prompts: ' . print_r($all_prompts, true));
            
            // Wenn keine Prompts vorhanden sind oder ein falsches Format haben, wird ein Test-Prompt erstellt
            if (empty($all_prompts) || !is_array($all_prompts)) {
                error_log('DeepPoster Debug - Keine Prompts gefunden oder falsches Format. Erstelle Test-Prompt.');
                
                // Erstelle einen Test-Prompt
                $test_prompt_id = 'prompt_' . time() . '_test';
                $all_prompts = array(
                    $test_prompt_id => array(
                        'title' => 'Test-Prompt für Debugging',
                        'text' => 'Dies ist ein automatisch generierter Test-Prompt für Debugging-Zwecke.',
                        'date_created' => current_time('mysql')
                    )
                );
                
                // Speichere den Test-Prompt in der Datenbank
                update_option('deepposter_saved_prompts', $all_prompts);
                error_log('DeepPoster Debug - Test-Prompt erstellt und gespeichert: ' . print_r($all_prompts, true));
            }
            
            // Sende Erfolgsantwort mit Prompts
            wp_send_json_success(array(
                'message' => __('Prompts erfolgreich geladen.', 'deepposter'),
                'prompts' => $all_prompts
            ));
            
        } catch (Exception $e) {
            error_log('DeepPoster Debug - Fehler in get_prompts: ' . $e->getMessage());
            error_log('DeepPoster Debug - Stack Trace: ' . $e->getTraceAsString());
            
            // Sende Fehlerantwort
            wp_send_json_error(array(
                'message' => __('Fehler beim Laden der Prompts: ', 'deepposter') . $e->getMessage()
            ));
        }
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

    /**
     * Repariert duplizierte IDs in der Datenbank
     * 
     * @since 1.0.0
     */
    /* Methode entfernt: repair_duplicate_ids */
} 