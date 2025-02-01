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
        check_ajax_referer('deepposter_nonce', 'nonce');

        // Validiere Eingaben
        $category_id = isset($_POST['category']) ? intval($_POST['category']) : 0;
        $count = isset($_POST['count']) ? intval($_POST['count']) : 1;
        $should_publish = isset($_POST['publish']) ? (bool)$_POST['publish'] : false;
        $custom_prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';

        if ($category_id <= 0) {
            wp_send_json_error('Bitte wählen Sie eine Kategorie aus.');
            return;
        }

        if ($count < 1 || $count > 10) {
            wp_send_json_error('Die Anzahl der Artikel muss zwischen 1 und 10 liegen.');
            return;
        }

        if (empty($custom_prompt)) {
            wp_send_json_error('Bitte geben Sie ein Prompt ein.');
            return;
        }

        // Hole OpenAI API Key
        $api_key = get_option('deepposter_openai_key');
        if (empty($api_key)) {
            wp_send_json_error('Bitte hinterlegen Sie zuerst Ihren OpenAI API Key in den Einstellungen.');
            return;
        }

        try {
            $generator = new DeepPoster_Generator($api_key);
            $posts = $generator->generate_posts($category_id, $count, $should_publish, $custom_prompt);
            
            wp_send_json_success($posts);
        } catch (Exception $e) {
            wp_send_json_error('Fehler bei der Artikelgenerierung: ' . $e->getMessage());
        }
    }
} 