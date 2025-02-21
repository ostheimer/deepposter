<?php
/**
 * Handler für AJAX-Anfragen
 */
class DeepPoster_Ajax {
    
    /**
     * Konstruktor
     */
    public function __construct() {
        add_action('wp_ajax_deepposter_save_logs', array($this, 'save_logs'));
        add_action('wp_ajax_deepposter_generate', array($this, 'generate_articles'));
        add_action('wp_ajax_deepposter_save_prompt', array($this, 'save_prompt'));
        add_action('wp_ajax_deepposter_get_prompt', array($this, 'get_prompt'));
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
     * Speichert den benutzerdefinierten Prompt
     */
    public function save_prompt() {
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

        // Hole und validiere den Prompt
        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';
        if (empty($prompt)) {
            wp_send_json_error('Kein Prompt angegeben.');
            return;
        }

        // Speichere den Prompt
        $result = update_option('deepposter_prompt', $prompt);

        if ($result) {
            wp_send_json_success('Prompt erfolgreich gespeichert.');
        } else {
            wp_send_json_error('Fehler beim Speichern des Prompts.');
        }
    }

    /**
     * Lädt den gespeicherten Prompt
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

        // Hole den gespeicherten Prompt
        $prompt = get_option('deepposter_prompt', '');

        if (!empty($prompt)) {
            wp_send_json_success($prompt);
        } else {
            wp_send_json_error('Kein Prompt gefunden.');
        }
    }
} 